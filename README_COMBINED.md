Motor Town Web Interface (MTWI)
Deutsch
Übersicht
Das Motor Town Web Interface (MTWI) ist ein benutzerfreundliches, webbasiertes Admin-Panel zur Verwaltung deines Motor Town Dedicated Servers. Mit diesem Tool hast du die volle Kontrolle über deinen Server – direkt aus deinem Browser heraus.

Hauptfunktionen
✔ Live-Chat – Kommuniziere in Echtzeit mit Spielern auf deinem Server.
✔ Dashboard – Verwalte Online-Spieler, verbanne Nutzer oder erhalte Server-Statistiken.
✔ Bannverwaltung – Sperre oder entsperre Spieler mit wenigen Klicks.
✔ Mehrsprachigkeit – Unterstützt Deutsch und Englisch (einfach erweiterbar).
✔ Einstellungen – Konfiguriere Serververbindung, Sprache und weitere Optionen.
✔ Schnellnachrichten – Sende vordefinierte Chat-Nachrichten für effiziente Kommunikation.
✔ Windows Chat-Server – Eine mitgelieferte Python-Lösung (win_server_chat_logs) ermöglicht die Live-Chat-Integration (inkl. Installationsanleitung).

Installation
Dateien hochladen – Kopiere den Inhalt des release-Ordners auf deinen Webserver.
Datenbank – Die SQLite-Datenbank (data/mtwi.db) wird automatisch erstellt.
Berechtigungen – Stelle sicher, dass der Webserver Schreibzugriff auf data/ und config/ hat.
Chat-Server (optional) – Falls gewünscht, richte den Python-Chat-Server (win_server_chat_logs/chat_server.py) auf deinem Windows-Server ein für live chat.
Richte cronjob ein: in dem ordner cron führe in der bash: ./setup_cron.sh
Setup-Assistent – Öffne index.php im Browser und folge den Anweisungen.


Systemvoraussetzungen
PHP 7.4 oder höher
SQLite3
Webserver (Apache, Nginx, etc.)
Python 3.x oder höher,für den Windows Chat-Server

Sicherheit
🔒 CSRF-Schutz – Alle AJAX-Anfragen sind mit Tokens gesichert.
🔒 Admin-Login – Nur autorisierte Nutzer können Aktionen durchführen.

Entwickler
Entwickelt von ^Rainer^Zufall^ und ki

English Version
Motor Town Web Interface (MTWI)
Overview
The Motor Town Web Interface (MTWI) is a user-friendly, web-based admin panel for managing your Motor Town Dedicated Server. Control your server directly from your browser with ease.

Key Features
✔ Live Chat – Communicate in real-time with players on your server.
✔ Dashboard – Manage online players, ban users, or view server statistics.
✔ Ban Management – Ban or unban players with just a few clicks.
✔ Multi-Language – Supports German and English (easily extendable).
✔ Settings – Configure server connection, language, and other options.
✔ Quick Messages – Send predefined chat messages for efficient communication.
✔ Windows Chat Server – Includes a Python-based solution (win_server_chat_logs) for live chat integration (installation guide included).

Installation
Upload Files – Copy the contents of the release folder to your web server.
Database – The SQLite database (data/mtwi.db) will be created automatically.
Permissions – Ensure the web server has write access to data/ and config/.
Chat Server (Optional) – If needed, set up the Python chat server (win_server_chat_logs/chat_server.py) on your Windows server.
Set up cronjob: in the cron folder in the bash: ./setup_cron.sh
Setup Wizard – Open index.php in your browser and follow the instructions.

Requirements
PHP 7.4 or higher
SQLite3
Web server (Apache, Nginx, etc.)
Python 3.x for the Windows Chat Server

Security
🔒 CSRF Protection – All AJAX requests are secured with tokens.
🔒 Admin Authentication – Only authorized users can perform actions.

Credits
Developed by ^Rainer^Zufall^ and ki