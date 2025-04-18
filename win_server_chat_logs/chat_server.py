from flask import Flask, request, jsonify, render_template_string
from pathlib import Path
import re
import os
from functools import wraps
import base64

# === Konfiguration ===
LOG_DIR = r'C:/Pfad/zu/deinen/Logs'  # <--- ANPASSEN!
USERNAME = 'admin'                   # <--- ANPASSEN!
PASSWORD = 'dein_geheimes_passwort'  # <--- ANPASSEN!
MAX_CHAT_LINES = 100

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
    </style>
</head>
<body>
<div class="chat-box">
    <h3>MTWI Chat Log <span id="loading" class="text-primary" style="font-size:0.7em;display:none;">(Loading...)</span></h3>
    <div id="chatlog"></div>
</div>
<script>
    async function loadChat() {
        document.getElementById('loading').style.display = 'inline';
        let resp = await fetch('/chatlog', {headers: { 'Authorization': 'Basic ' + btoa('{{USERNAME}}:{{PASSWORD}}') }});
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
    loadChat();
    setInterval(loadChat, 10000);
</script>
</body>
</html>
''', USERNAME=USERNAME, PASSWORD=PASSWORD)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5005, debug=False)
