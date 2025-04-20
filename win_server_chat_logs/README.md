# MTWI Windows Chat Log Server

Dieses Tool ermöglicht es, die Chat-Nachrichten aus den Motor Town Dedicated Server-Logfiles über einen kleinen Python-Webserver auszulesen und sicher im Webinterface (und anderen Tools) anzuzeigen.

## Features
- Liest die letzten 100 echten Chat-Zeilen (`[CHAT] ...`) aus beliebig vielen Logfiles
- Modernes, responsives Web-Frontend (Bootstrap)
- Sicherer Zugriff per HTTP Basic Auth (Benutzername/Passwort)
- REST-API-Endpoint (`/chatlog`) für PHP/andere Tools
- Einfach zu konfigurieren und zu starten

## Installation & Nutzung

1. **Python installieren**
   - Empfohlen: Python 3.10 oder neuer ([Download](https://www.python.org/downloads/windows/))
   - Während der Installation "Add Python to PATH" aktivieren!

2. **Abhängigkeiten installieren**
   ```bash
      pip install waitress
   ```

3. **Pfad und Zugangsdaten anpassen**
   - Öffne `chat_server.py` mit einem Editor
   - Passe folgende Zeilen an:
     ```python
     LOG_DIR = r'C:/Pfad/zu/deinen/Logs'  # <--- ANPASSEN!
     USERNAME = 'admin'                   # <--- ANPASSEN!
     PASSWORD = 'dein_geheimes_passwort'  # <--- ANPASSEN!
     ```

4. **Server starten**
   ```bash
     waitress-serve --listen=0.0.0.0:5005 chat_server:app
   ```
   - Der Server läuft dann z.B. auf `http://localhost:5005/`


5. **Webseite aufrufen**
   - Öffne im Browser: `http://localhost:5005/`
   - Login mit Benutzername und Passwort (wie oben eingestellt)
   - Mit den Daten kannst du auch das Webinterface nutzen (z.B. im MTWI)





## Hinweise
- Nur Zeilen mit `[CHAT]` werden angezeigt.
- Die Logfiles können beliebig viele sein, das Tool nimmt immer die neuesten zuerst.
- Du kannst das Frontend nach Belieben anpassen.

---

**Fragen oder Probleme?**
Einfach im Webinterface melden oder den Code anpassen!

---

# MTWI Windows Chat Log Server (English)

This tool allows you to read chat messages from Motor Town Dedicated Server log files via a small Python web server and display them securely in the web interface (and other tools).

## Features
- Reads the last 100 real chat lines (`[CHAT] ...`) from any number of log files
- Modern, responsive web frontend (Bootstrap)
- Secure access via HTTP Basic Auth (username/password)
- REST API endpoint (`/chatlog`) for PHP/other tools
- Easy to configure and start

## Installation & Usage

1. **Install Python**
   - Recommended: Python 3.10 or newer ([Download](https://www.python.org/downloads/windows/))
   - During installation, activate "Add Python to PATH"!

2. **Install dependencies**
   ```bash
      pip install waitress
   ```

3. **Adjust path and credentials**
   - Open `chat_server.py` in an editor
   - Adjust the following lines:
     ```python
     LOG_DIR = r'C:/path/to/your/logs'  # <--- CHANGE THIS!
     USERNAME = 'admin'                 # <--- CHANGE THIS!
     PASSWORD = 'your_secret_password'  # <--- CHANGE THIS!
     ```

4. **Start the server**
   ```bash
  waitress-serve --listen=0.0.0.0:5005 chat_server:app
   ```
   - The server will then run at e.g. `http://localhost:5005/`

5. **Open the web page**
   - Open in your browser: `http://localhost:5005/`
   - Login with username and password (as set above)
   - You can also use these credentials in the web interface (e.g. in MTWI)

## Notes
- Only lines containing `[CHAT]` are displayed.
- You can have as many log files as you want; the tool always uses the newest ones first.
- You can customize the frontend as you like.

---

**Questions or problems?**
Just report them via the web interface or modify the code yourself!

---


