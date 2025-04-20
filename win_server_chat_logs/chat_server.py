from flask import Flask, request, jsonify, render_template_string, redirect, url_for
from pathlib import Path
import re
import os
from functools import wraps
import base64
import subprocess
import psutil
import time

# === Konfiguration ===
LOG_DIR = r'C:\Program Files (x86)\Steam\steamapps\common\Motor Town Behind The Wheel - Dedicated Server\MotorTown\Saved\ServerLog'  # <--- ANPASSEN!
USERNAME = 'admin'                   # <--- ANPASSEN!
PASSWORD = 'admin'  # <--- ANPASSEN!
MAX_CHAT_LINES = 100
# Pfad zur Batch-Datei, die den Server startet
SERVER_BAT = r'C:/Program Files (x86)/Steam/steamapps/common/Motor Town Behind The Wheel - Dedicated Server/RunDedicatedServer.bat'  # <--- ANPASSEN!
# Name des Server-Prozesses
SERVER_PROCNAME = 'MotorTownServer-Win64-Shipping.exe' # <--- ANPASSEN Falls Verändert!

app = Flask(__name__)

# === Basic Auth Decorator ===
def check_auth(auth):
    if not auth or not auth.startswith('Basic '):
        return False
    encoded = auth.split(' ', 1)[1].strip()
    decoded = base64.b64decode(encoded).decode('utf-8')
    username, pw = decoded.split(':', 1)
    return username == USERNAME and pw == PASSWORD

def requires_auth(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        auth = request.headers.get('Authorization', None)
        if not check_auth(auth):
            return ('Unauthorized', 401, {'WWW-Authenticate': 'Basic realm="Login Required"'})
        return f(*args, **kwargs)
    return decorated

# === Chat-Log-Auslese ===
def get_latest_chat_lines():
    chat_lines = []
    chat_pattern_user = re.compile(r'\[(.*?)\] \[CHAT\] (.*?): (.*)')
    chat_pattern_admin = re.compile(r'\[(.*?)\] \[CHAT\] (.+)')
    # Alle Logfiles sortiert nach Datum (neuste zuerst)
    files = sorted(Path(LOG_DIR).glob('*'), reverse=True)
    for file in files:
        if not file.is_file():
            continue
        try:
            with open(file, encoding='utf-8', errors='ignore') as f:
                lines = f.readlines()
        except Exception:
            continue
        for line in reversed(lines):
            if '[CHAT]' in line:
                m = chat_pattern_user.search(line)
                if m:
                    chat_lines.append({
                        'time': m.group(1),
                        'user': m.group(2),
                        'message': m.group(3)
                    })
                else:
                    m2 = chat_pattern_admin.search(line)
                    if m2:
                        chat_lines.append({
                            'time': m2.group(1),
                            'user': 'admin',
                            'message': m2.group(2)
                        })
                if len(chat_lines) >= MAX_CHAT_LINES:
                    return list(reversed(chat_lines))
    return list(reversed(chat_lines))

# === API-Endpoint ===
@app.route('/chatlog')
@requires_auth
def chatlog():
    lines = get_latest_chat_lines()
    return jsonify(lines)

# === Server-Prozesssteuerung ===
def is_server_running():
    for proc in psutil.process_iter(['name']):
        try:
            if proc.info['name'] and SERVER_PROCNAME.lower() in proc.info['name'].lower():
                return proc
        except (psutil.NoSuchProcess, psutil.AccessDenied):
            continue
    return None

def start_server():
    if is_server_running():
        return False, 'Server läuft bereits.'
    try:
        bat_dir = os.path.dirname(SERVER_BAT)
        # Batch-Datei als String, shell=True, CWD setzen
        subprocess.Popen(SERVER_BAT, shell=True, cwd=bat_dir, creationflags=subprocess.CREATE_NEW_CONSOLE)
        time.sleep(2)  # Kurz warten, damit Prozess starten kann
        if is_server_running():
            return True, 'Server wurde gestartet.'
        else:
            return False, 'Startbefehl ausgeführt, aber Prozess nicht gefunden.'
    except Exception as e:
        return False, f'Fehler beim Start: {e}'

def stop_server():
    proc = is_server_running()
    if not proc:
        return False, 'Server läuft nicht.'
    try:
        proc.terminate()
        try:
            proc.wait(timeout=10)
        except psutil.TimeoutExpired:
            proc.kill()
        return True, 'Server wurde gestoppt.'
    except Exception as e:
        return False, f'Fehler beim Stoppen: {e}'

def restart_server():
    stop_server()
    time.sleep(2)
    return start_server()

# === API-Endpunkte für Steuerung ===
@app.route('/server/status', methods=['GET', 'POST'])
@requires_auth
def server_status():
    server_laeuft = is_server_running() is not None
    return jsonify({
        "success": True,
        "status": "running" if server_laeuft else "stopped",
        "message": "Server läuft" if server_laeuft else "Server gestoppt",
        "detail": ""
    })

@app.route('/server/start', methods=['POST'])
@requires_auth
def server_start():
    ok, msg = start_server()
    return jsonify({'success': ok, 'message': msg})

@app.route('/server/stop', methods=['POST'])
@requires_auth
def server_stop():
    ok, msg = stop_server()
    return jsonify({'success': ok, 'message': msg})

@app.route('/server/restart', methods=['POST'])
@requires_auth
def server_restart():
    ok, msg = restart_server()
    return jsonify({'success': ok, 'message': msg})

# === Minimales Frontend ===
@app.route('/')
@requires_auth
def index():
    return render_template_string('''
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTWI Chat Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .chat-box { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 24px; }
        .chat-line { border-bottom: 1px solid #eee; padding: 8px 0; }
        .chat-user { font-weight: bold; }
        .chat-time { color: #888; font-size: 0.9em; margin-right: 8px; }
        .chat-message { white-space: pre-line; }
        .server-status { font-weight:bold; }
    </style>
</head>
<body>
<div class="chat-box">
    <h3>MTWI Chat Log <span id="loading" class="text-primary" style="font-size:0.7em;display:none;">(Loading...)</span></h3>
    <div class="mb-3">
        <span>Server Status: <span id="serverStatus" class="server-status">...</span></span>
        <button class="btn btn-success btn-sm ms-2" onclick="serverAction('start')">Start</button>
        <button class="btn btn-warning btn-sm ms-1" onclick="serverAction('restart')">Restart</button>
        <button class="btn btn-danger btn-sm ms-1" onclick="serverAction('stop')">Stop</button>
        <span id="serverMsg" class="ms-2"></span>
    </div>
    <div id="chatlog"></div>
</div>
<script>
    const authHeader = { 'Authorization': 'Basic ' + btoa('{{USERNAME}}:{{PASSWORD}}') };
    async function loadChat() {
        document.getElementById('loading').style.display = 'inline';
        let resp = await fetch('/chatlog', {headers: authHeader});
        if (resp.ok) {
            let data = await resp.json();
            let html = '';
            for (let line of data) {
                html += `<div class='chat-line'><span class='chat-time'>[${line.time}]</span> <span class='chat-user'>${line.user}:</span> <span class='chat-message'>${line.message}</span></div>`;
            }
            document.getElementById('chatlog').innerHTML = html || '<div class="text-muted">No chat messages found.</div>';
        } else {
            document.getElementById('chatlog').innerHTML = '<div class="text-danger">Fehler beim Laden der Chatdaten (Auth?)</div>';
        }
        document.getElementById('loading').style.display = 'none';
    }
    async function loadStatus() {
        let resp = await fetch('/server/status', {headers: authHeader});
        if (resp.ok) {
            let data = await resp.json();
            let el = document.getElementById('serverStatus');
            el.textContent = data.running ? 'LÄUFT' : 'GESTOPPT';
            el.style.color = data.running ? 'green' : 'red';
        }
    }
    async function serverAction(action) {
        document.getElementById('serverMsg').textContent = 'Bitte warten...';
        let resp = await fetch('/server/' + action, {method:'POST', headers: authHeader});
        if (resp.ok) {
            let data = await resp.json();
            document.getElementById('serverMsg').textContent = data.message;
        } else {
            document.getElementById('serverMsg').textContent = 'Fehler bei der Aktion.';
        }
        loadStatus();
    }
    loadChat();
    loadStatus();
    setInterval(loadChat, 10000);
    setInterval(loadStatus, 5000);
</script>
</body>
</html>
''', USERNAME=USERNAME, PASSWORD=PASSWORD)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5005, debug=False)
