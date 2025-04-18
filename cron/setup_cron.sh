#!/bin/bash
#!/bin/bash
# Motor Town Web Interface (MTWI) - Cron Setup Script

# Verzeichnispfad ermitteln, in dem dieses Skript liegt
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

# Datenverzeichnis für Logs erstellen
DATA_DIR="$ROOT_DIR/data"
if [ ! -d "$DATA_DIR" ]; then
    mkdir -p "$DATA_DIR"
    echo "Datenverzeichnis erstellt: $DATA_DIR"
fi

# Cron Einträge erstellen
(crontab -l 2>/dev/null || echo "") | grep -v "manage_bans.php" > /tmp/current_cron
echo "# Motor Town Web Interface - Ban Management (jede Minute)" >> /tmp/current_cron
echo "* * * * * cd $SCRIPT_DIR && php manage_bans.php >> $SCRIPT_DIR/ban_cron.log 2>&1" >> /tmp/current_cron
crontab /tmp/current_cron
rm /tmp/current_cron

echo "Cron Job für Ban-Management eingerichtet (wird jede Minute ausgeführt)"

# Log-Dateien erstellen und Berechtigungen setzen
if [ ! -f "$SCRIPT_DIR/ban_cron.log" ]; then
    touch "$SCRIPT_DIR/ban_cron.log"
    echo "Log-Datei erstellt: $SCRIPT_DIR/ban_cron.log"
fi

# Berechtigungen setzen
chmod 755 "$SCRIPT_DIR/manage_bans.php"
chmod 666 "$SCRIPT_DIR/ban_cron.log"
chmod -R 777 "$DATA_DIR"

echo "Berechtigungen gesetzt für:"
echo " - PHP-Skript: $SCRIPT_DIR/manage_bans.php"
echo " - Log-Datei: $SCRIPT_DIR/ban_cron.log"
echo " - Datenverzeichnis: $DATA_DIR"

# Test-Ausführung des Skripts
echo ""
echo "Führe Test-Ausführung des Ban-Management-Skripts durch..."
php "$SCRIPT_DIR/manage_bans.php"
echo "Test-Ausführung abgeschlossen."

echo ""
echo "Cron-Job-Setup abgeschlossen. Die Ban-Liste wird jetzt jede Minute synchronisiert."
