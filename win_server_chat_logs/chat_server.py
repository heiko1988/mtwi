from flask import Flask, request, jsonify, render_template_string, redirect, url_for
from pathlib import Path
import re
import os
import json
import datetime
from functools import wraps
import base64
import subprocess
import psutil
import time
from collections import defaultdict

# === Konfiguration ===
LOG_DIR = r'C:\Program Files (x86)\Steam\steamapps\common\Motor Town Behind The Wheel - Dedicated Server\MotorTown\Saved\ServerLog'  # <--- ANPASSEN!
USERNAME = 'admin'                   # <--- ANPASSEN!
PASSWORD = 'admin'  # <--- ANPASSEN!
MAX_CHAT_LINES = 100
MAX_PLAYER_ACTIVITY_LINES = 50  # Maximale Anzahl der Aktivitäten pro Spieler
# Pfad zur Batch-Datei, die den Server startet
SERVER_BAT = r'C:/Program Files (x86)/Steam/steamapps/common/Motor Town Behind The Wheel - Dedicated Server/RunDedicatedServer.bat'  # <--- ANPASSEN!
# Name des Server-Prozesses
SERVER_PROCNAME = 'MotorTownServer-Win64-Shipping.exe' # <--- ANPASSEN Falls Verändert!
# Daten-Cache-Datei
CACHE_FILE = 'player_data_cache.json'

app = Flask(__name__)

# === Datenstrukturen ===
players_cache = {}
vehicles_cache = {}
player_activities = defaultdict(list)
companies = {}

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

# === Log-Parsing ===
def parse_server_logs(check_only_latest=False):
    print(f"DEBUG: parse_server_logs called (check_only_latest={check_only_latest})")
    # AKTUALISIERTE Regex-Patterns für die tatsächlichen Log-Formate
    # Chat-Nachrichten
    chat_pattern_user = re.compile(r'\[(.*?)\] \[CHAT\] (.*?) \(([0-9]+)\): (.*)')
    chat_pattern_admin = re.compile(r'\[(.*?)\] \[CHAT\] (.+)')
    
    # Login/Logout
    login_pattern = re.compile(r'\[(.*?)\] Player Login: (.*?) \(([0-9]+)\)')
    logout_pattern = re.compile(r'\[(.*?)\] Player Logout: (.*)')
    
    # Fahrzeuge
    vehicle_enter_pattern = re.compile(r'\[(.*?)\] Player entered vehicle\. Player=(.*?) \(([0-9]+)\) Vehicle=(.+?)\(([0-9]+)\)')
    vehicle_exit_pattern = re.compile(r'\[(.*?)\] Player exited vehicle\. Player=(.*?) \(([0-9]+)\) Vehicle=(.+?)\(([0-9]+)\)')
    vehicle_buy_pattern = re.compile(r'\[(.*?)\] Player bought vehicle\. Player=(.*?) \(([0-9]+)\) Vehicle=(.+?)\(([0-9]+)\)')
    
    # Leveländerungen
    level_pattern = re.compile(r'\[(.*?)\] Player level changed\. Player=(.*?) \(([0-9]+)\) Level=(.+?)\(([0-9]+)\)')
    
    # Unternehmen
    company_add_pattern = re.compile(r'\[(.*?)\] Company added\. Name=(.+?)\(Corp\?(?:true|false)\) Owner=(.+?)\(([0-9]+)\)')
    company_remove_pattern = re.compile(r'\[(.*?)\] Company removed\. Name=(.+?)\(Corp\?(?:true|false)\) Owner=(.+?)\(([0-9]+)\)')
    
    chat_lines = []
    
    # OPTIMIERT: Logfiles chronologisch sortieren (älteste zuerst)
    print(f"Durchsuche Log-Verzeichnis: {LOG_DIR}")
    try:
        log_dir = Path(LOG_DIR)
        if not log_dir.exists():
            print(f"WARNUNG: Log-Verzeichnis existiert nicht: {LOG_DIR}")
            return []
            
        files = list(log_dir.glob('*'))
        print(f"{len(files)} Logdateien gefunden.")
        
        if check_only_latest and files:
            # Nur die neueste Datei prüfen bei wiederholten Durchläufen
            files = sorted(files, key=lambda x: x.stat().st_mtime, reverse=True)[:1]
            print(f"Prüfe nur neueste Datei: {files[0].name}")
        else:
            # Chronologisch sortieren bei vollständigem Durchlauf
            files = sorted(files, key=lambda x: x.stat().st_mtime)
            print(f"Verarbeite {len(files)} Dateien in chronologischer Reihenfolge")
    except Exception as e:
        print(f"Fehler beim Auflisten der Logdateien: {e}")
        import traceback
        print(traceback.format_exc())
        return []
    for file in files:
        if not file.is_file():
            continue
        try:
            with open(file, encoding='utf-8', errors='ignore') as f:
                lines = f.readlines()
        except Exception:
            continue
        
        for line in lines:
            # Chat-Nachrichten parsen
            if '[CHAT]' in line:
                m = chat_pattern_user.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    message = m.group(4)
                    
                    # Spieler dem Cache hinzufügen, falls noch nicht vorhanden
                    if steam_id not in players_cache:
                        players_cache[steam_id] = {
                            'name': username,
                            'steam_id': steam_id,
                            'last_seen': timestamp,
                            'online': True,
                            'taxi_level': 0,
                            'bus_level': 0,
                            'wrecker_level': 0,
                            'police_level': 0,
                            'driver_level': 0,
                            'truck_level': 0,
                            'racer_level': 0,
                            'company': None
                        }
                    
                    chat_lines.append({
                        'time': timestamp,
                        'user': username,
                        'message': message
                    })
                    # Limit prüfen
                    if len(chat_lines) >= MAX_CHAT_LINES:
                        chat_lines = chat_lines[-MAX_CHAT_LINES:]
                else:
                    m2 = chat_pattern_admin.search(line)
                    if m2:
                        chat_lines.append({
                            'time': m2.group(1),
                            'user': 'admin',
                            'message': m2.group(2)
                        })
                        # Limit prüfen
                        if len(chat_lines) >= MAX_CHAT_LINES:
                            chat_lines = chat_lines[-MAX_CHAT_LINES:]
            
            # Login parsen
            if 'Player Login:' in line:
                m = login_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    print(f"LOGIN: {username} ({steam_id}) at {timestamp}")
                    
                    # Spielerdaten aktualisieren
                    if steam_id not in players_cache:
                        players_cache[steam_id] = {
                            'name': username,
                            'steam_id': steam_id,
                            'first_seen': timestamp,
                            'last_seen': timestamp,
                            'online': True,
                            'taxi_level': 0,
                            'bus_level': 0,
                            'wrecker_level': 0,
                            'police_level': 0,
                            'driver_level': 0,
                            'truck_level': 0,
                            'racer_level': 0,
                            'company': None
                        }
                    else:
                        players_cache[steam_id]['name'] = username
                        players_cache[steam_id]['last_seen'] = timestamp
                        players_cache[steam_id]['online'] = True
                    
                    # Aktivität hinzufügen
                    add_player_activity(steam_id, timestamp, 'login')
            
            # Logout parsen
            if 'Player Logout:' in line:
                m = logout_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    
                    # Steam-ID suchen
                    steam_id = None
                    for sid, player in players_cache.items():
                        if player['name'] == username:
                            steam_id = sid
                            break
                    
                    if steam_id:
                        print(f"LOGOUT: {username} ({steam_id}) at {timestamp}")
                        # Spielerdaten aktualisieren
                        players_cache[steam_id]['last_seen'] = timestamp
                        players_cache[steam_id]['online'] = False
                        
                        # Aktivität hinzufügen
                        add_player_activity(steam_id, timestamp, 'logout')
            
            # Fahrzeug-Einsteigen parsen
            if 'Player entered vehicle' in line:
                m = vehicle_enter_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    vehicle_name = m.group(4)
                    vehicle_id = m.group(5)
                    
                    # Debug-Ausgabe für Fahrzeugdaten
                    print(f"DEBUG: Vehicle Entry - {username} ({steam_id}) entered {vehicle_name} ({vehicle_id}) at {timestamp}")
                    print(f"DEBUG: Vehicle Data - {vehicle_name} ({vehicle_id})")
                    
                    # Fahrzeugdaten aktualisieren
                    if steam_id not in vehicles_cache:
                        vehicles_cache[steam_id] = []
                    
                    # Prüfen, ob das Fahrzeug bereits existiert
                    vehicle_exists = False
                    for vehicle in vehicles_cache[steam_id]:
                        if vehicle['id'] == vehicle_id:
                            vehicle['last_used'] = timestamp
                            vehicle_exists = True
                            break
                    
                    # Wenn nicht, hinzufügen
                    if not vehicle_exists:
                        vehicles_cache[steam_id].append({
                            'id': vehicle_id,
                            'name': vehicle_name,
                            'last_used': timestamp
                        })
                    
                    # Aktivität hinzufügen
                    add_player_activity(steam_id, timestamp, 'vehicle_used', vehicle_name)
                    
            # Fahrzeug-Aussteigen parsen
            if 'Player exited vehicle' in line:
                m = vehicle_exit_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    vehicle_name = m.group(4)
                    vehicle_id = m.group(5)
                    
                    # Bei Aussteigen keine besondere Aktion notwendig
                    
            # Fahrzeug-Kauf parsen
            if 'Player bought vehicle' in line:
                m = vehicle_buy_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    vehicle_name = m.group(4)
                    vehicle_id = m.group(5)
                    
                    # Fahrzeugdaten aktualisieren
                    if steam_id not in vehicles_cache:
                        vehicles_cache[steam_id] = []
                    
                    # Prüfen, ob das Fahrzeug bereits existiert
                    vehicle_exists = False
                    for vehicle in vehicles_cache[steam_id]:
                        if vehicle['id'] == vehicle_id:
                            vehicle_exists = True
                            break
                    
                    # Wenn nicht, hinzufügen
                    if not vehicle_exists:
                        vehicles_cache[steam_id].append({
                            'id': vehicle_id,
                            'name': vehicle_name,
                            'last_used': timestamp
                        })
                    
                    # Aktivität hinzufügen
                    add_player_activity(steam_id, timestamp, 'vehicle_bought', vehicle_name)
            
            # Level-Änderungen parsen
            if 'Player level changed' in line:
                m = level_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    username = m.group(2)
                    steam_id = m.group(3)
                    level_type = m.group(4)
                    level = int(m.group(5))
                    
                    # Level-Typ normalisieren
                    if 'CL_Taxi' in level_type:
                        job_type = 'taxi'
                    elif 'CL_Bus' in level_type:
                        job_type = 'bus'
                    elif 'CL_Wrecker' in level_type:
                        job_type = 'wrecker'
                    elif 'CL_Police' in level_type:
                        job_type = 'police'
                    elif 'CL_Driver' in level_type:
                        job_type = 'driver'
                    elif 'CL_Truck' in level_type:
                        job_type = 'truck'
                    elif 'CL_Racer' in level_type:
                        job_type = 'racer'
                    else:
                        job_type = level_type.lower().replace('cl_', '')
                    
                    # Spielerdaten aktualisieren
                    if steam_id in players_cache:
                        if job_type == 'taxi':
                            players_cache[steam_id]['taxi_level'] = level
                        elif job_type == 'bus':
                            players_cache[steam_id]['bus_level'] = level
                        elif job_type == 'wrecker':
                            players_cache[steam_id]['wrecker_level'] = level
                        elif job_type == 'police':
                            players_cache[steam_id]['police_level'] = level
                        elif job_type == 'driver':
                            players_cache[steam_id]['driver_level'] = level
                        elif job_type == 'truck':
                            players_cache[steam_id]['truck_level'] = level
                        elif job_type == 'racer':
                            players_cache[steam_id]['racer_level'] = level
                    
                    # Aktivität hinzufügen
                    add_player_activity(steam_id, timestamp, 'level_up', f'{job_type} {level}')
            
            # Unternehmen hinzugefügt parsen
            if 'Company added' in line:
                m = company_add_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    company_name = m.group(2)
                    owner_name = m.group(3)
                    owner_id = m.group(4)
                    
                    # Spieler dem Unternehmen zuordnen
                    if owner_id in players_cache:
                        players_cache[owner_id]['company'] = company_name
                    
                    # Unternehmen zur Liste hinzufügen
                    if company_name not in companies:
                        companies[company_name] = []
                    if owner_id not in companies[company_name]:
                        companies[company_name].append(owner_id)
                    
                    # Aktivität hinzufügen
                    add_player_activity(owner_id, timestamp, 'company', f'created {company_name}')
                    
            # Unternehmen entfernt parsen
            if 'Company removed' in line:
                m = company_remove_pattern.search(line)
                if m:
                    timestamp = m.group(1)
                    company_name = m.group(2)
                    owner_name = m.group(3)
                    owner_id = m.group(4)
                    
                    # Spieler vom Unternehmen entfernen
                    if owner_id in players_cache:
                        players_cache[owner_id]['company'] = None
                    
                    # Unternehmen aus der Liste entfernen
                    if company_name in companies:
                        if owner_id in companies[company_name]:
                            companies[company_name].remove(owner_id)
                        # Wenn keine Mitglieder mehr, Unternehmen entfernen
                        if not companies[company_name]:
                            del companies[company_name]
                    
                    # Aktivität hinzufügen
                    add_player_activity(owner_id, timestamp, 'company', f'removed {company_name}')
    
    # Cache-Datei aktualisieren und geladene Spieler loggen
    save_cache()
    print(f"Cache aktualisiert. {len(players_cache)} Spieler im Cache, {len(chat_lines)} Chat-Nachrichten.")
    if players_cache:
        print("Gefundene Spieler:")
        for steam_id, player in players_cache.items():
            print(f"  - {player['name']} (Steam-ID: {steam_id}, Online: {player['online']})")
    
    return list(reversed(chat_lines[-MAX_CHAT_LINES:]))

# Hilfsfunktion zum Hinzufügen von Spieleraktivitäten
def add_player_activity(steam_id, timestamp, action, details=None):
    player_activities[steam_id].append({
        'timestamp': timestamp,
        'action': action,
        'details': details
    })
    
    # Limit einhalten
    if len(player_activities[steam_id]) > MAX_PLAYER_ACTIVITY_LINES:
        player_activities[steam_id] = player_activities[steam_id][-MAX_PLAYER_ACTIVITY_LINES:]

# Cache-Funktionen
def save_cache():
    cache_data = {
        'players': players_cache,
        'vehicles': vehicles_cache,
        'activities': dict(player_activities),
        'companies': companies
    }
    
    try:
        with open(CACHE_FILE, 'w') as f:
            json.dump(cache_data, f)
    except Exception as e:
        print(f"Fehler beim Speichern des Caches: {e}")

def load_cache():
    global players_cache, vehicles_cache, player_activities, companies
    
    try:
        if os.path.exists(CACHE_FILE):
            with open(CACHE_FILE, 'r') as f:
                cache_data = json.load(f)
                players_cache = cache_data.get('players', {})
                vehicles_cache = cache_data.get('vehicles', {})
                player_activities = defaultdict(list, cache_data.get('activities', {}))
                companies = cache_data.get('companies', {})
    except Exception as e:
        print(f"Fehler beim Laden des Caches: {e}")

# Laden des Caches beim Start
load_cache()

# Funktion zum Abrufen der neuesten Chat-Nachrichten
def get_latest_chat_lines():
    return parse_server_logs()

# === API-Endpoints ===
@app.route('/chatlog')
@requires_auth
def chatlog():
    lines = get_latest_chat_lines()
    return jsonify(lines)

# Spielerliste abrufen
@app.route('/api/players')
@requires_auth
def get_players():
    # Server-Logs parsen, um die neuesten Daten zu erhalten
    parse_server_logs()
    
    # Liste der Spieler zurückgeben
    player_list = []
    for steam_id, player in players_cache.items():
        player_list.append(player)
    
    return jsonify({
        'success': True,
        'players': player_list
    })

# Spielerdetails abrufen
@app.route('/api/player/<steam_id>')
@requires_auth
def get_player(steam_id):
    # Server-Logs parsen, um die neuesten Daten zu erhalten
    parse_server_logs()
    
    if steam_id not in players_cache:
        return jsonify({
            'success': False,
            'message': 'Spieler nicht gefunden'
        })
    
    # Spieler mit zusätzlichen Informationen zurückgeben
    player = players_cache[steam_id].copy()
    
    # Fahrzeuge hinzufügen
    # Fahrzeugdaten aus dem Cache holen
    player_vehicles = vehicles_cache.get(steam_id, [])
    player['vehicles'] = player_vehicles
    
    # Most used vehicle ermitteln basierend auf der Häufigkeit der Nutzung
    if player_vehicles:
        # Zählen, wie oft jedes Fahrzeug verwendet wurde
        vehicle_usage_count = {}
        for vehicle in player_vehicles:
            vehicle_name = vehicle.get('name', '')
            if vehicle_name in vehicle_usage_count:
                vehicle_usage_count[vehicle_name] += 1
            else:
                vehicle_usage_count[vehicle_name] = 1
        
        # Fahrzeug mit der höchsten Nutzung finden
        if vehicle_usage_count:
            most_used_vehicle = max(vehicle_usage_count.items(), key=lambda x: x[1])[0]
            player['most_used_vehicle'] = most_used_vehicle
        else:
            player['most_used_vehicle'] = None
    else:
        player['most_used_vehicle'] = None
    
    # Aktivitäten hinzufügen
    player['activities'] = player_activities.get(steam_id, [])
    
    return jsonify({
        'success': True,
        'player': player
    })

# Fahrzeuge eines Spielers abrufen
@app.route('/api/player/<steam_id>/vehicles')
@requires_auth
def get_player_vehicles(steam_id):
    # Server-Logs parsen, um die neuesten Daten zu erhalten
    parse_server_logs()
    
    if steam_id not in players_cache:
        return jsonify({
            'success': False,
            'message': 'Spieler nicht gefunden'
        })
    
    # Fahrzeuge zurückgeben
    return jsonify({
        'success': True,
        'vehicles': vehicles_cache.get(steam_id, [])
    })

# Aktivitäten eines Spielers abrufen
@app.route('/api/player/<steam_id>/activities')
@requires_auth
def get_player_activities(steam_id):
    # Server-Logs parsen, um die neuesten Daten zu erhalten
    parse_server_logs()
    
    if steam_id not in players_cache:
        return jsonify({
            'success': False,
            'message': 'Spieler nicht gefunden'
        })
    
    # Aktivitäten zurückgeben
    return jsonify({
        'success': True,
        'activities': player_activities.get(steam_id, [])
    })

# Unternehmen abrufen
@app.route('/api/companies')
@requires_auth
def get_companies():
    # Server-Logs parsen, um die neuesten Daten zu erhalten
    parse_server_logs()
    
    # Unternehmensliste mit Mitgliedern zurückgeben
    company_list = []
    for company_name, members in companies.items():
        company_data = {
            'name': company_name,
            'members': []
        }
        
        for steam_id in members:
            if steam_id in players_cache:
                company_data['members'].append({
                    'steam_id': steam_id,
                    'name': players_cache[steam_id]['name']
                })
        
        company_list.append(company_data)
    
    return jsonify({
        'success': True,
        'companies': company_list
    })

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

@app.route('/server/resources', methods=['GET'])
@requires_auth
def server_resources():
    proc = is_server_running()
    if not proc:
        return jsonify({
            'success': False,
            'message': 'Server nicht gestartet',
            'cpu_percent': 0,
            'memory_percent': 0,
            'disk_percent': 0
        })
    cpu_percent = psutil.cpu_percent(interval=0.1)
    memory_percent = psutil.virtual_memory().percent
    disk_percent = psutil.disk_usage('/').percent
    return jsonify({
        'success': True,
        'cpu_percent': cpu_percent,
        'memory_percent': memory_percent,
        'disk_percent': disk_percent
    })

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
            el.textContent = data.status;
            el.style.color = data.status === 'running' ? 'green' : 'red';
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

# Regelmäßiges Parsen der Logs im Hintergrund
def background_log_parsing():
    import threading
    def parse_logs_periodically():
        initial_parse_done = False
        while True:
            try:
                if not initial_parse_done:
                    print("Initial parsing of all server logs...")
                    parse_server_logs()
                    initial_parse_done = True
                    print(f"Initial parsing done. {len(players_cache)} players found.")
                else:
                    # Bei späteren Durchläufen nur die neuesten Logs prüfen
                    parse_server_logs(check_only_latest=True)
            except Exception as e:
                print(f"Fehler beim Parsen der Logs: {e}")
                import traceback
                print(traceback.format_exc())
            time.sleep(30)  # Alle 30 Sekunden aktualisieren
    
    thread = threading.Thread(target=parse_logs_periodically)
    thread.daemon = True
    thread.start()

# Hintergrundprozess starten
background_log_parsing()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5005, debug=False)
