# Motor Town Web Interface (MTWI)

## 🇩🇪 Deutsch

### Version 0.0.3.7r2 - Änderungsprotokoll (02.05.2025)

#### Neue Funktionen
- Live Chat ist jetzt direkt auf der Startseite integriert für schnellere Kommunikation
- Zusammengeführtes Ban-System und Spieleranzahl-Widget für bessere Übersicht
- Game Server Ressourcen-Monitoring (CPU, RAM und Festplattennutzung)
- Steam Namen und Profilbilder werden automatisch zu den Spielern geladen
- Verbesserte Spielerübersicht mit detaillierten Informationen (Spielername, Steam ID, Letzter Login, Firma, verschiedene Berufslevel)
- Verbesserter Setup-Assistent mit horizontaler Navigation für bessere Übersichtlichkeit
- Robustere Konfigurationsdateierstellung mit verbesserten Rückgabewerten und Fehlerbehandlung
- Bessere Anpassung der Benutzeroberfläche für eine intuitivere Bedienung

#### Bugfixes
- **Kritisch**: Behebt das Problem, bei dem Benutzer nach dem Speichern von Einstellungen zum Setup-Assistenten zurückgeleitet wurden
- Korrigiert die Speicherung des `setup_completed`-Status in der Konfigurationsdatei
- Verhindert das Zurücksetzen der Konfiguration bei partiellen Updates
- Verbesserte Robustheit bei der Speicherung von Einstellungen
- Behebt Darstellungsprobleme im Setup-Assistenten

#### Installation
1. Entpacken Sie die ZIP-Datei auf Ihrem Webserver
2. Navigieren Sie im Browser zu Ihrer MTWI-Installation
3. Folgen Sie dem Setup-Assistenten, um die Installation abzuschließen

#### WICHTIG: Neuinstallation empfohlen!
Aufgrund umfangreicher Änderungen im Setup-Assistenten und in der Konfigurationsverwaltung wird **dringend empfohlen, eine vollständige Neuinstallation durchzuführen**:

---

## 🇬🇧 English

### Version 0.0.3.7r2 - Changelog (May 2nd, 2025)

#### New Features
- Live Chat now directly integrated on the homepage for faster communication
- Merged ban system and player count widget for better overview
- Game server resource monitoring (CPU, RAM, and disk usage)
- Automatic loading of Steam names and profile pictures for players
- Enhanced player overview with detailed information (player name, Steam ID, last seen, company, various job levels)
- Improved setup assistant with horizontal navigation for better clarity
- More robust configuration file creation with improved return values and error handling
- Better UI adaptations for more intuitive usage

#### Bugfixes
- **Critical**: Fixes the issue where users were redirected to the setup assistant after saving settings
- Corrects the saving of the `setup_completed` status in the configuration file
- Prevents configuration reset during partial updates
- Improved robustness when saving settings
- Fixes display issues in the setup assistant

#### Installation
1. Extract the ZIP file to your web server
2. Navigate to your MTWI installation in your browser
3. Follow the setup assistant to complete the installation

#### IMPORTANT: Fresh Installation Recommended!
Due to significant changes in the setup assistant and configuration management, it is **strongly recommended to perform a complete fresh installation**:


## Features

- Dashboard with server control and statistics
- Player management (view, kick, ban)
- Live chat integration
- Ban list management
- Comprehensive role-based permission system
- Multi-language support (German/English)
- Light and dark theme
- Responsive design for mobile and desktop

## Requirements

- PHP 7.4+ (with required extensions: PDO, cURL, JSON, mbstring, and FileInfo)
- SQLite or MySQL database
- Web server (Apache, Nginx, etc.)
- Python 3.6+ for the chat server
