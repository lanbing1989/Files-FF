# 文件同步客户端与服务器端（断点续传 + MD5智能同步）

本项目为一个简单的**文件分发同步系统**，包含**客户端（PySide6 GUI）**和**服务器端（Flask API）**。客户端可自动/手动同步服务器上的文件，支持断点续传、MD5校验、托盘最小化等功能。

---

## 一、服务器端

### 1. 主要功能

- 提供文件列表及每个文件的 MD5
- 提供文件下载接口
- 支持任意静态文件分发

### 2. 目录结构

```text
server/
├── files/                  # 需分发的文件存放目录
├── app.py                  # Flask主程序
├── requirements.txt        # 依赖包列表
```

### 3. 安装与运行

#### (1) 安装依赖

```bash
cd server
pip install -r requirements.txt
```

#### (2) 启动服务器

```bash
python app.py
```

#### (3) 默认配置

- 文件分发API: `http://服务器IP:5000/files/<文件名>`
- 文件列表API: `http://服务器IP:5000/api_list`

### 4. 服务器端示例代码（Flask）

```python name=server/app.py
import os
import hashlib
from flask import Flask, send_from_directory, jsonify

app = Flask(__name__)
FILES_DIR = os.path.join(os.path.dirname(__file__), "files")

def get_md5(filepath):
    md5 = hashlib.md5()
    with open(filepath, "rb") as f:
        while True:
            chunk = f.read(8192)
            if not chunk:
                break
            md5.update(chunk)
    return md5.hexdigest()

@app.route("/api_list")
def api_list():
    result = []
    for fname in os.listdir(FILES_DIR):
        path = os.path.join(FILES_DIR, fname)
        if os.path.isfile(path):
            result.append({
                "filename": fname,
                "md5": get_md5(path)
            })
    return jsonify(result)

@app.route("/files/<filename>")
def download_file(filename):
    return send_from_directory(FILES_DIR, filename, as_attachment=True)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
```

**说明：** 默认分发 `files/` 目录下所有文件。

---

## 二、客户端

### 1. 主要功能

- 支持**手动同步**和**定时自动同步**（每30分钟一次）
- 支持断点续传、MD5校验
- 支持最小化到系统托盘
- 进度条与状态提示
- 文件列表展示与手动下载
- 版权信息底部显示

### 2. 目录结构

```text
client/
├── client_gui_md5_sync.py  # 主程序
├── icon.ico                # 托盘及程序图标（需自备或下载）
```

### 3. 安装依赖

```bash
cd client
pip install PySide6 requests
```

### 4. 运行客户端

```bash
python client_gui_md5_sync.py
```

### 5. 打包为EXE

推荐使用 PyInstaller：

```bash
pyinstaller --noconsole --onefile --icon=icon.ico client_gui_md5_sync.py
```

详细打包方法请见本 README 末尾。

### 6. 客户端主要代码

```python name=client/client_gui_md5_sync.py
# 见本项目 client_gui_md5_sync.py 文件，已集成断点续传、MD5校验、托盘等功能
```

---

## 三、使用说明

### 1. 服务端配置

- 将需分发的文件放入 `server/files` 目录
- 启动 Flask 服务，确保外网或局域网能访问

### 2. 客户端配置

- 修改 `SERVER_LIST_API` 和 `SERVER_DOWNLOAD_URL` 为你服务器实际地址
  ```python
  SERVER_LIST_API = "http://服务器IP:5000/api_list"
  SERVER_DOWNLOAD_URL = "http://服务器IP:5000/files/{}"
  ```
- 确保 `icon.ico` 文件存在于客户端目录下

### 3. 功能使用

- **手动同步**：点击“手动同步”按钮，立即同步一次
- **定时自动同步**：点击“开启自动同步”，每30分钟自动同步一次，点击“停止自动同步”关闭之
- **下载单个文件**：在列表选中文件后点击“下载选中文件”
- **最小化到托盘**：点击“最小化到托盘”按钮，窗口隐藏到托盘
- **关闭程序**：直接点击窗口右上角“X”号
- **恢复窗口**：双击托盘图标或托盘菜单“显示窗口”

---

## 四、打包客户端为 EXE

1. 安装 PyInstaller
   ```bash
   pip install pyinstaller
   ```
2. 打包命令
   ```bash
   pyinstaller --noconsole --onefile --icon=icon.ico --add-data "icon.ico;." client_gui_md5_sync.py
   ```
   - 打包后 EXE 文件在 `dist/` 目录
   - 确保 `icon.ico` 文件与 EXE 在同一目录
3. 若需打包其它资源，参考 PyInstaller `--add-data` 选项

---

## 五、常见问题

- **托盘没有图标？**  
  请确认 `icon.ico` 文件有效且存在于程序目录。
- **local_files 文件夹不存在？**  
  客户端自动创建，无需手动建立。
- **同步失败/下载失败？**  
  请检查服务器地址及网络连通性，或服务端是否正常运行。

---

## 六、版权说明

版权所有 © 灯火通明（济宁）网络有限公司

---

## 七、联系方式

如需定制开发、技术支持，请联系作者。
