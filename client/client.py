import sys
import os
import requests
import hashlib
from PySide6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QPushButton, QListWidget, QHBoxLayout, QMessageBox,
    QSystemTrayIcon, QMenu, QProgressBar, QLabel
)
from PySide6.QtGui import QIcon, QAction
from PySide6.QtCore import Qt, QThread, Signal, Slot, QTimer

SERVER_LIST_API = "http://ff.zhuli.pro/api_list.php"
SERVER_DOWNLOAD_URL = "http://ff.zhuli.pro/files/{}"
LOCAL_DIR = "local_files"

def get_file_md5(filepath):
    if not os.path.isfile(filepath):
        return None
    md5 = hashlib.md5()
    with open(filepath, 'rb') as f:
        while True:
            data = f.read(8192)
            if not data:
                break
            md5.update(data)
    return md5.hexdigest()

class DownloadThread(QThread):
    progress = Signal(int)
    status = Signal(str)

    def __init__(self, filename):
        super().__init__()
        self.filename = filename
        self._abort = False

    def stop(self):
        self._abort = True

    def run(self):
        url = SERVER_DOWNLOAD_URL.format(self.filename)
        local_path = os.path.join(LOCAL_DIR, self.filename)
        try:
            os.makedirs(LOCAL_DIR, exist_ok=True)  # 保证文件夹存在
            r = requests.head(url)
            if r.status_code != 200 or "Content-Length" not in r.headers:
                self.status.emit(f"{self.filename} 获取文件信息失败")
                self.progress.emit(0)
                return
            total_size = int(r.headers["Content-Length"])
            downloaded = 0
            if os.path.exists(local_path):
                downloaded = os.path.getsize(local_path)
                if downloaded == total_size:
                    self.status.emit(f"{self.filename} 已下载完成，无需重复下载")
                    self.progress.emit(100)
                    return
            headers = {}
            if downloaded > 0:
                headers["Range"] = f"bytes={downloaded}-"
            resp = requests.get(url, stream=True, headers=headers)
            if resp.status_code in (200, 206):
                mode = "ab" if downloaded > 0 else "wb"
                with open(local_path, mode) as f:
                    for chunk in resp.iter_content(chunk_size=8192):
                        if self._abort:
                            self.status.emit(f"{self.filename} 下载已停止")
                            self.progress.emit(0)
                            return
                        if chunk:
                            f.write(chunk)
                            downloaded += len(chunk)
                            percent = int((downloaded/total_size)*100)
                            self.progress.emit(percent)
                self.status.emit(f"{self.filename} 下载完成")
                self.progress.emit(100)
            else:
                self.status.emit(f"{self.filename} 下载失败，状态码：{resp.status_code}")
                self.progress.emit(0)
        except Exception as e:
            self.status.emit(f"{self.filename} 下载异常: {e}")
            self.progress.emit(0)

class SyncThread(QThread):
    progress = Signal(int)
    status = Signal(str)
    finished = Signal()

    def __init__(self):
        super().__init__()
        self._abort = False

    def stop(self):
        self._abort = True

    def run(self):
        try:
            os.makedirs(LOCAL_DIR, exist_ok=True)
            resp = requests.get(SERVER_LIST_API)
            resp.raise_for_status()
            server_files = resp.json()
            total_files = len(server_files)
            count = 0
            for f in server_files:
                if self._abort:
                    self.status.emit("同步已停止")
                    self.progress.emit(0)
                    return
                fname = f.get("filename")
                remote_md5 = f.get("md5")
                local_path = os.path.join(LOCAL_DIR, fname)
                local_md5 = get_file_md5(local_path) if os.path.exists(local_path) else None
                if local_md5 != remote_md5:
                    self.status.emit(f"自动下载: {fname}")
                    dt = DownloadThread(fname)
                    dt.status.connect(self.status.emit)
                    dt.progress.connect(lambda p: self.progress.emit(int((count + p/100)/(total_files)*100)))
                    dt.start()
                    while dt.isRunning():
                        if self._abort:
                            dt.stop()
                            dt.wait()
                            self.status.emit("同步已停止")
                            self.progress.emit(0)
                            return
                        QApplication.processEvents()
                    del dt
                else:
                    self.status.emit(f"{fname} 已是最新，无需下载")
                count += 1
                self.progress.emit(int(count/total_files*100))
            self.status.emit("同步完成")
            self.progress.emit(100)
        except Exception as e:
            self.status.emit(f"同步异常: {e}")
            self.progress.emit(0)
        self.finished.emit()

class MainWindow(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("文件同步客户端")
        # 强制使用有效图标，icon.ico必须存在
        icon_path = "icon.ico"
        if not os.path.exists(icon_path):
            # 可选：用默认Qt图标
            app_icon = QIcon.fromTheme("application-exit")
        else:
            app_icon = QIcon(icon_path)
        self.setWindowIcon(app_icon)
        self.resize(520, 490)
        self.layout = QVBoxLayout(self)

        self.listWidget = QListWidget()
        self.layout.addWidget(self.listWidget)

        btn_layout = QHBoxLayout()
        self.download_button = QPushButton("下载选中文件")
        self.manual_sync_button = QPushButton("手动同步")
        self.stop_manual_sync_button = QPushButton("停止手动同步")
        self.auto_sync_button = QPushButton("开启自动同步")
        self.stop_auto_sync_button = QPushButton("停止自动同步")
        self.minimize_to_tray_button = QPushButton("最小化到托盘")
        self.refresh_button = QPushButton("刷新文件列表")
        self.stop_manual_sync_button.setEnabled(False)
        self.stop_auto_sync_button.setEnabled(False)
        btn_layout.addWidget(self.download_button)
        btn_layout.addWidget(self.manual_sync_button)
        btn_layout.addWidget(self.stop_manual_sync_button)
        btn_layout.addWidget(self.auto_sync_button)
        btn_layout.addWidget(self.stop_auto_sync_button)
        btn_layout.addWidget(self.minimize_to_tray_button)
        btn_layout.addWidget(self.refresh_button)
        self.layout.addLayout(btn_layout)

        self.download_button.clicked.connect(self.manual_download)
        self.manual_sync_button.clicked.connect(self.start_manual_sync)
        self.stop_manual_sync_button.clicked.connect(self.stop_manual_sync)
        self.auto_sync_button.clicked.connect(self.start_auto_sync)
        self.stop_auto_sync_button.clicked.connect(self.stop_auto_sync)
        self.minimize_to_tray_button.clicked.connect(self.hide_to_tray)
        self.refresh_button.clicked.connect(self.refresh_list)

        self.status_button = QPushButton("")
        self.status_button.setEnabled(False)
        self.layout.addWidget(self.status_button)

        self.progress_bar = QProgressBar()
        self.progress_bar.setMinimum(0)
        self.progress_bar.setMaximum(100)
        self.progress_bar.setValue(0)
        self.layout.addWidget(self.progress_bar)

        # 版权信息底部显示
        self.copyright_label = QLabel("版权所有 © 灯火通明（济宁）网络有限公司")
        self.copyright_label.setAlignment(Qt.AlignCenter)
        self.layout.addWidget(self.copyright_label)

        self.manual_sync_thread = None
        self.auto_sync_thread = None
        self.auto_sync_timer = QTimer(self)
        self.auto_sync_timer.timeout.connect(self.auto_sync_task)
        self.auto_sync_enabled = False

        self.refresh_list()

        # 托盘相关
        self.tray_icon = QSystemTrayIcon(app_icon, self)
        tray_menu = QMenu()
        restore_action = QAction("显示窗口")
        quit_action = QAction("退出")
        tray_menu.addAction(restore_action)
        tray_menu.addAction(quit_action)
        self.tray_icon.setContextMenu(tray_menu)
        restore_action.triggered.connect(self.show_window)
        quit_action.triggered.connect(self.close)
        self.tray_icon.activated.connect(self.tray_activated)
        self.tray_icon.show()

    def hide_to_tray(self):
        self.tray_icon.showMessage("文件同步客户端", "已最小化到任务栏托盘")
        self.hide()

    def closeEvent(self, event):
        # X号关闭直接退出程序
        event.accept()

    def tray_activated(self, reason):
        if reason == QSystemTrayIcon.ActivationReason.Trigger:
            self.show_window()

    def show_window(self):
        self.showNormal()
        self.raise_()
        self.activateWindow()

    def refresh_list(self):
        self.listWidget.clear()
        try:
            resp = requests.get(SERVER_LIST_API)
            resp.raise_for_status()
            files = resp.json()
            for f in files:
                self.listWidget.addItem(f["filename"])
            self.status_button.setText(f"文件列表刷新成功，共{len(files)}项")
        except Exception as e:
            self.status_button.setText(f"文件列表刷新失败: {e}")

    def manual_download(self):
        selected = self.listWidget.selectedItems()
        if not selected:
            QMessageBox.warning(self, "提示", "请选择要下载的文件")
            return
        filename = selected[0].text()
        self.status_button.setText(f"正在下载: {filename}")
        self.progress_bar.setValue(0)
        self.download_thread = DownloadThread(filename)
        self.download_thread.status.connect(self.status_button.setText)
        self.download_thread.progress.connect(self.progress_bar.setValue)
        self.download_thread.start()

    def start_manual_sync(self):
        if self.manual_sync_thread and self.manual_sync_thread.isRunning():
            return
        self.status_button.setText("正在手动同步所有缺失或已更新文件…")
        self.progress_bar.setValue(0)
        self.manual_sync_thread = SyncThread()
        self.manual_sync_thread.status.connect(self.status_button.setText)
        self.manual_sync_thread.progress.connect(self.progress_bar.setValue)
        self.manual_sync_thread.finished.connect(self.on_manual_sync_finished)
        self.manual_sync_thread.start()
        self.manual_sync_button.setEnabled(False)
        self.stop_manual_sync_button.setEnabled(True)

    def stop_manual_sync(self):
        if self.manual_sync_thread and self.manual_sync_thread.isRunning():
            self.manual_sync_thread.stop()
            self.status_button.setText("正在停止手动同步...")
            self.stop_manual_sync_button.setEnabled(False)
            self.manual_sync_button.setEnabled(True)

    @Slot()
    def on_manual_sync_finished(self):
        self.manual_sync_button.setEnabled(True)
        self.stop_manual_sync_button.setEnabled(False)

    def start_auto_sync(self):
        if self.auto_sync_enabled:
            return
        self.status_button.setText("已开启自动同步（每30分钟一次）")
        self.progress_bar.setValue(0)
        self.auto_sync_timer.start(30 * 60 * 1000)
        self.auto_sync_enabled = True
        self.auto_sync_button.setEnabled(False)
        self.stop_auto_sync_button.setEnabled(True)
        # 立即执行一次同步
        self.auto_sync_task()

    def stop_auto_sync(self):
        if not self.auto_sync_enabled:
            return
        self.auto_sync_timer.stop()
        self.auto_sync_enabled = False
        self.status_button.setText("已关闭自动同步")
        self.auto_sync_button.setEnabled(True)
        self.stop_auto_sync_button.setEnabled(False)
        # 停止当前自动同步线程
        if self.auto_sync_thread and self.auto_sync_thread.isRunning():
            self.auto_sync_thread.stop()
            self.status_button.setText("正在停止自动同步...")

    def auto_sync_task(self):
        # 防止并发自动同步
        if self.auto_sync_thread and self.auto_sync_thread.isRunning():
            return
        self.status_button.setText("自动同步中（每30分钟）…")
        self.progress_bar.setValue(0)
        self.auto_sync_thread = SyncThread()
        self.auto_sync_thread.status.connect(self.status_button.setText)
        self.auto_sync_thread.progress.connect(self.progress_bar.setValue)
        self.auto_sync_thread.finished.connect(self.on_auto_sync_finished)
        self.auto_sync_thread.start()

    @Slot()
    def on_auto_sync_finished(self):
        if self.auto_sync_enabled:
            self.status_button.setText("自动同步完成（等待下次定时同步）")
        else:
            self.status_button.setText("自动同步已停止")
        self.progress_bar.setValue(0)

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.show()
    sys.exit(app.exec())