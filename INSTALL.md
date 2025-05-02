# Motor Town Web Interface (MTWI) - Installationsanleitung

## Systemvoraussetzungen

### Webserver
- Apache 2.4+ oder Nginx
- PHP 7.4+ (empfohlen: PHP 8.0+)
- SQLite 3 oder MySQL 5.7+/MariaDB 10.3+

### PHP-Erweiterungen
- PDO (mit SQLite und/oder MySQL Treiber)
- cURL
- JSON
- FileInfo
- mbstring
- Session

### Dateiberechtigungen
- Schreibzugriff auf das Installationsverzeichnis
- Besonders wichtig: Schreibrechte für die Verzeichnisse `config/`, `data/` und alle Unterverzeichnisse

## Installation

### 1. Dateien hochladen

1. Laden Sie die ZIP-Datei auf Ihren Webserver hoch
2. Entpacken Sie die Dateien in Ihr gewünschtes Webverzeichnis (z.B. `/var/www/html/mtwi/`)
3. Stellen Sie sicher, dass der Webserver-Benutzer (z.B. `www-data`) Schreibzugriff auf das Verzeichnis hat:
   ```bash
   chown -R www-data:www-data /var/www/html/mtwi/
   chmod -R 755 /var/www/html/mtwi/
   chmod -R 775 /var/www/html/mtwi/data/
   chmod -R 775 /var/www/html/mtwi/config/
   ```

### 2. Webinterface einrichten

1. Rufen Sie im Browser die URL Ihrer Installation auf (z.B. `http://ihr-server.de/mtwi/`)
2. Sie werden automatisch zum Setup-Assistenten weitergeleitet
3. Folgen Sie den Anweisungen des Setup-Assistenten:
   - Systemvoraussetzungen prüfen
   - Datenbank einrichten (SQLite oder MySQL)
   - Verbindung zum Chat-Server konfigurieren
   - Admin-Konto erstellen

### 3. Chat-Server Einrichtung

#### Voraussetzungen für den Chat-Server
- Python 3.6+ mit pip
- Betriebssystem: Linux, Windows oder macOS
- Offene Ports (standardmäßig 5005)

#### Installation des Chat-Servers
1. Laden Sie den Motor Town Chat Server von der offiziellen Quelle herunter
2. Entpacken Sie die Dateien in ein separates Verzeichnis
3. Installieren Sie die Abhängigkeiten mit pip:
   ```bash
   cd chat-server/
   pip install -r requirements.txt
   # oder auf manchen Systemen:
   python3 -m pip install -r requirements.txt
   ```
4. Konfigurieren Sie den Server in der Datei `config.py` oder `settings.ini`:
   ```python
   # Beispiel für config.py
   SERVER_PORT = 5005
   ADMIN_USERNAME = "admin"
   ADMIN_PASSWORD = "IhrSicheresPasswort"
   LOG_LEVEL = "INFO"
   ```
5. Starten Sie den Chat-Server:
   ```bash
   python3 server.py
   # oder
   python3 main.py
   ```
   Für einen dauerhaften Betrieb unter Linux empfehlen wir die Verwendung von systemd:
   ```bash
   # Erstellen Sie eine Datei /etc/systemd/system/motortown-chat.service
   [Unit]
   Description=Motor Town Chat Server
   After=network.target
   
   [Service]
   User=www-data
   WorkingDirectory=/pfad/zum/chat-server
   ExecStart=/usr/bin/python3 /pfad/zum/chat-server/server.py
   Restart=always
   
   [Install]
   WantedBy=multi-user.target
   ```
   
   Aktivieren und starten Sie den Dienst:
   ```bash
   sudo systemctl enable motortown-chat
   sudo systemctl start motortown-chat
   ```

### 4. Verbindung zwischen Webinterface und Chat-Server

1. Im Setup-Assistenten oder unter Einstellungen geben Sie die Verbindungsdaten Ihres Chat-Servers ein:
   - URL: Die vollständige URL Ihres Servers (z.B. `http://ihr-server.de`)
   - Port: Der Port des Chat-Servers (standardmäßig 5005)
   - Benutzername: Admin-Benutzername (standardmäßig "admin")
   - Passwort: Das konfigurierte Admin-Passwort

## Fehlerbehebung

### Webinterface

**Problem: Seite wird nicht angezeigt**
- Überprüfen Sie, ob der Webserver läuft
- Prüfen Sie die PHP-Fehlerprotokolle (üblicherweise in `/var/log/apache2/error.log` oder `/var/log/nginx/error.log`)
- Stellen Sie sicher, dass alle benötigten PHP-Erweiterungen installiert sind

**Problem: Keine Verbindung zur Datenbank**
- Bei SQLite: Prüfen Sie die Schreibrechte für das `data/`-Verzeichnis
- Bei MySQL: Überprüfen Sie Benutzername, Passwort und Datenbankname

**Problem: Keine Verbindung zum Chat-Server**
- Überprüfen Sie, ob der Chat-Server läuft
- Stellen Sie sicher, dass die Firewall den Port nicht blockiert
- Prüfen Sie die eingegebenen Zugangsdaten

### Chat-Server

**Problem: Server startet nicht**
- Überprüfen Sie die NodeJS-Version (`node -v`)
- Prüfen Sie, ob alle Abhängigkeiten installiert sind (`npm install`)
- Schauen Sie in die Logdateien

**Problem: Port bereits in Benutzung**
- Ändern Sie den Port in der `config.json`
- Überprüfen Sie, welcher Prozess den Port belegt: `netstat -tuln | grep 5005`

## Aktualisierung

1. Sichern Sie Ihre aktuelle Installation und Datenbank
2. Laden Sie die neue Version hoch
3. Ersetzen Sie alle Dateien außer:
   - `config/config.php`
   - `data/`-Verzeichnis (wenn SQLite verwendet wird)
4. Rufen Sie die Seite auf - die Datenbank wird automatisch aktualisiert

## Support

Bei Fragen oder Problemen wenden Sie sich an den Support unter [support@example.com](mailto:support@example.com) oder besuchen Sie das [Support-Forum](https://example.com/forum).
