/**
 * Motor Town Web Interface (MTWI) - Spielerübersicht JavaScript
 * Version: 2.0 - Mit separaten Tabellen für Online/Offline und Paginierung
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sortierungsvariablen initialisieren
    let currentSortTable = null;
    let currentSortField = 'last_seen'; // Standardmäßig nach Datum sortieren
    let currentSortDirection = 'desc'; // Standardmäßig absteigend (neueste zuerst)
    // Event-Listener für Modal-Schließen hinzufügen
    const playerDetailModal = document.getElementById('playerDetailModal');
    if (playerDetailModal) {
        playerDetailModal.addEventListener('hidden.bs.modal', function() {
            // Entferne alle verbleibenden modal-backdrop Elemente
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            // Entferne die modal-open Klasse vom Body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
    // Statusvariablen für die Paginierung
    let allPlayers = [];
    let onlinePlayers = [];
    let offlinePlayers = [];
    let onlinePlayersPerPage = 20;
    let offlinePlayersPerPage = 20;
    let onlineCurrentPage = 1;
    let offlineCurrentPage = 1;
    
    // DOM-Elemente
    const refreshButton = document.getElementById('refreshOverviewData');
    const onlinePlayersTable = document.getElementById('onlinePlayersTable');
    const offlinePlayersTable = document.getElementById('offlinePlayersTable');
    const onlinePlayerCount = document.getElementById('onlinePlayerCount');
    const offlinePlayerCount = document.getElementById('offlinePlayerCount');
    const playerDetailSection = document.getElementById('playerDetailSection');
    
    // Tabellen-Sortierungs-Header initialisieren
    initTableSorting(onlinePlayersTable);
    initTableSorting(offlinePlayersTable);
    initTableSorting(document.getElementById('playerVehiclesTable'));
    initTableSorting(document.getElementById('playerActivitiesTable'));
    
    // Initialisierung
    loadPlayerData();
    
    // Warte 500ms, damit die Daten zuerst geladen werden, dann sortiere
    setTimeout(() => {
        // Initiale Sortierung durchführen für Online-Spieler
        sortPlayers(onlinePlayers, 'last_seen', 'desc', 'date');
        displayOnlinePlayers();
        
        // Initiale Sortierung durchführen für Offline-Spieler
        sortPlayers(offlinePlayers, 'last_seen', 'desc', 'date');
        displayOfflinePlayers();
        
        // Sortierungsmarkierung für Header setzen
        const lastSeenHeaders = document.querySelectorAll('th.sortable[data-sort="last_seen"]');
        lastSeenHeaders.forEach(header => {
            header.classList.add('sorted-desc');
        });
    }, 500);
    
    // Event-Listener für Refresh-Button
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            loadPlayerData();
        });
    }
    
    // Event-Listener für Paginierungskontrollen
    if (document.getElementById('onlinePlayersPerPage')) {
        document.getElementById('onlinePlayersPerPage').addEventListener('change', function() {
            onlinePlayersPerPage = parseInt(this.value);
            onlineCurrentPage = 1;
            displayOnlinePlayers();
        });
    }
    
    if (document.getElementById('offlinePlayersPerPage')) {
        document.getElementById('offlinePlayersPerPage').addEventListener('change', function() {
            offlinePlayersPerPage = parseInt(this.value);
            offlineCurrentPage = 1;
            displayOfflinePlayers();
        });
    }
    
    /**
     * Spielerdaten vom Server laden
     */
    function loadPlayerData() {
        // Lade-Animation in beiden Tabellen anzeigen
        showLoadingInTable(onlinePlayersTable);
        showLoadingInTable(offlinePlayersTable);
        
        // AJAX-Anfrage senden
        fetch('get_players.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Fehler beim Laden der Spielerdaten');
                }
                return response.json();
            })
            .then(data => {
                processPlayerData(data);
            })
            .catch(error => {
                console.error('Fehler beim Laden der Spielerdaten:', error);
                showErrorInTable(onlinePlayersTable, error.message);
                showErrorInTable(offlinePlayersTable, error.message);
            });
    }
    
    /**
     * Lade-Animation in Tabelle anzeigen
     */
    function showLoadingInTable(table) {
        if (!table) return;
        
        table.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="11" class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    ${t('loading_player_data')}
                </td>
            </tr>
        `;
    }
    
    /**
     * Fehlermeldung in Tabelle anzeigen
     */
    function showErrorInTable(table, errorMessage) {
        if (!table) return;
        
        table.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-danger">
                    <i class="bi bi-exclamation-triangle"></i> ${errorMessage}
                </td>
            </tr>
        `;
    }
    
    /**
     * Tabellensortierung initialisieren
     */
    function initTableSorting(table) {
        if (!table) return;
        
        const headers = table.querySelectorAll('th.sortable');
        headers.forEach(header => {
            // Event-Listener explizit mit einer benannten Funktion hinzufügen
            const sortHandler = function() {
                console.log('Sort header clicked:', this.getAttribute('data-sort'));
                const sortField = this.getAttribute('data-sort');
                const sortType = this.getAttribute('data-sort-type') || 'string';
                
                // Standardrichtung umkehren, wenn das gleiche Feld erneut geklickt wird
                let sortDirection = 'asc';
                if (currentSortTable === table && currentSortField === sortField) {
                    sortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
                }
                
                // CSS-Klassen für Sortierung zurücksetzen
                headers.forEach(h => {
                    h.classList.remove('sorted-asc', 'sorted-desc');
                });
                
                // Sortierungsrichtung visuell anzeigen
                if (sortDirection === 'asc') {
                    this.classList.add('sorted-asc');
                } else {
                    this.classList.add('sorted-desc');
                }
                
                // Sortierungsstatus speichern
                currentSortTable = table;
                currentSortField = sortField;
                currentSortDirection = sortDirection;
                
                console.log('Sorting table:', table.id, 'by', sortField, 'in', sortDirection, 'order');
                
                // Bestimmen, welche Tabelle sortiert wird und entsprechend handeln
                if (table.id === 'onlinePlayersTable') {
                    sortPlayers(onlinePlayers, sortField, sortDirection, sortType);
                    displayOnlinePlayers();
                } else if (table.id === 'offlinePlayersTable') {
                    sortPlayers(offlinePlayers, sortField, sortDirection, sortType);
                    displayOfflinePlayers();
                } else if (table.id === 'playerVehiclesTable') {
                    sortPlayerDetailsTable(table, sortField, sortDirection, sortType);
                } else if (table.id === 'playerActivitiesTable') {
                    sortPlayerDetailsTable(table, sortField, sortDirection, sortType);
                }
            };
            
            // Event-Listener entfernen, falls schon vorhanden (um doppelte zu vermeiden)
            header.removeEventListener('click', sortHandler);
            // Event-Listener hinzufügen
            header.addEventListener('click', sortHandler);
            
            // Sortier-Cursor deutlicher machen
            header.style.cursor = 'pointer';
            
            // Standardsortierung auf 'last_seen' mit 'desc' (neueste zuerst) setzen
            if (header.getAttribute('data-sort') === 'last_seen') {
                // Alle Tabellen standardmäßig nach Datum sortieren (neueste zuerst)
                currentSortField = 'last_seen';
                currentSortDirection = 'desc';
            }
        });
    }
    
    /**
     * Spieler anhand eines Felds sortieren
     */
    function sortPlayers(players, field, direction, sortType) {
        players.sort((a, b) => {
            let valA, valB;
            
            // Behandle verschiedene Feldnamen
            switch(field) {
                case 'name':
                    valA = a.name || '';
                    valB = b.name || '';
                    break;
                case 'steam_id':
                    valA = a.steam_id || '';
                    valB = b.steam_id || '';
                    break;
                case 'last_seen':
                    // Für Datumsvergleich nutzen wir die Originalwerte, da formatDate() nur zur Anzeige dient
                    valA = a.last_seen || '';
                    valB = b.last_seen || '';
                    break;
                case 'company':
                    valA = a.company || '';
                    valB = b.company || '';
                    break;
                default:
                    // Für Level-Werte und andere numerische Felder
                    valA = a[field] || 0;
                    valB = b[field] || 0;
            }
            
            // Sortierlogik basierend auf Datentyp
            let comparison = 0;
            if (sortType === 'number') {
                // Numerische Sortierung
                comparison = parseFloat(valA) - parseFloat(valB);
            } else if (sortType === 'date') {
                // Datumssortierung - versuche verschiedene Formate zu parsen
                const dateA = parseDateValue(valA);
                const dateB = parseDateValue(valB);
                comparison = dateA - dateB;
            } else {
                // Standard-Textsortierung
                if (typeof valA === 'string' && typeof valB === 'string') {
                    comparison = valA.localeCompare(valB);
                } else {
                    comparison = valA < valB ? -1 : valA > valB ? 1 : 0;
                }
            }
            
            // Bei absteigender Sortierung das Ergebnis umkehren
            return direction === 'asc' ? comparison : -comparison;
        });
    }
    
    /**
     * Spielerdetailtabellen (Fahrzeuge, Aktivitäten) sortieren
     */
    function sortPlayerDetailsTable(table, field, direction, sortType) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        console.log('Sorting detail table:', table.id, 'Rows:', rows.length);
        
        // Keine Sortierung, wenn es nur eine Zeile gibt (z.B. "Keine Fahrzeuge gefunden")
        if (rows.length <= 1) {
            if (rows.length === 1 && rows[0].querySelector('td') && rows[0].querySelector('td').hasAttribute('colspan')) {
                console.log('Single row with colspan - skipping sort');
                return;
            }
        }
        
        rows.sort((rowA, rowB) => {
            let cellIndex = 0;
            let valA, valB;
            
            // Index der Spalte finden
            const headers = table.querySelectorAll('th');
            for (let i = 0; i < headers.length; i++) {
                if (headers[i].getAttribute('data-sort') === field) {
                    cellIndex = i;
                    break;
                }
            }
            
            // Zellen für die Sortierung extrahieren
            const cellsA = rowA.querySelectorAll('td');
            const cellsB = rowB.querySelectorAll('td');
            
            // Sicherstellen, dass die Zellen existieren
            if (cellsA.length <= cellIndex || cellsB.length <= cellIndex) {
                console.log('Invalid cell index:', cellIndex, 'Row cells:', cellsA.length);
                return 0;
            }
            
            valA = cellsA[cellIndex].textContent.trim();
            valB = cellsB[cellIndex].textContent.trim();
            
            // Sortierlogik basierend auf Datentyp
            let comparison = 0;
            if (sortType === 'number') {
                // Numerische Sortierung
                comparison = parseFloat(valA) - parseFloat(valB);
            } else if (sortType === 'date') {
                // Datumssortierung - versuche verschiedene Formate zu parsen
                const dateA = parseDateValue(valA);
                const dateB = parseDateValue(valB);
                comparison = dateA - dateB;
            } else {
                // Standard-Textsortierung
                comparison = valA.localeCompare(valB);
            }
            
            // Bei absteigender Sortierung das Ergebnis umkehren
            return direction === 'asc' ? comparison : -comparison;
        });
        
        // Sortierte Zeilen wieder einfügen
        rows.forEach(row => tbody.appendChild(row));
    }
    
    /**
     * Datumswert parsen (unterstützt verschiedene Formate)
     */
    function parseDateValue(dateStr) {
        if (!dateStr || dateStr === '-') return 0;
        
        // Versuch, ein Datum aus verschiedenen Formaten zu parsen
        let date;
        
        // Format: 2025.04.28-21.17.37 (deutsches Motor Town Format)
        const mtFormat = /^(\d{4})\.(\d{2})\.(\d{2})-(\d{2})\.(\d{2})\.(\d{2})$/;
        const mtMatch = dateStr.match(mtFormat);
        if (mtMatch) {
            date = new Date(
                parseInt(mtMatch[1]),     // Jahr
                parseInt(mtMatch[2]) - 1, // Monat (0-11)
                parseInt(mtMatch[3]),     // Tag
                parseInt(mtMatch[4]),     // Stunde
                parseInt(mtMatch[5]),     // Minute
                parseInt(mtMatch[6])      // Sekunde
            );
            return date.getTime();
        }
        
        // Format: 28.04.2025, 22:30:02 (deutsches lokalisiertes Format)
        const deFormat = /^(\d{2})\.(\d{2})\.(\d{4}), (\d{2}):(\d{2}):(\d{2})$/;
        const deMatch = dateStr.match(deFormat);
        if (deMatch) {
            date = new Date(
                parseInt(deMatch[3]),     // Jahr
                parseInt(deMatch[2]) - 1, // Monat (0-11)
                parseInt(deMatch[1]),     // Tag
                parseInt(deMatch[4]),     // Stunde
                parseInt(deMatch[5]),     // Minute
                parseInt(deMatch[6])      // Sekunde
            );
            return date.getTime();
        }
        
        // Format: 04/28/2025, 10:30:02 PM (englisches lokalisiertes Format)
        const enFormat = /^(\d{2})\/(\d{2})\/(\d{4}), (\d{1,2}):(\d{2}):(\d{2}) (AM|PM)$/;
        const enMatch = dateStr.match(enFormat);
        if (enMatch) {
            let hours = parseInt(enMatch[4]);
            if (enMatch[7] === 'PM' && hours < 12) hours += 12;
            if (enMatch[7] === 'AM' && hours === 12) hours = 0;
            
            date = new Date(
                parseInt(enMatch[3]),     // Jahr
                parseInt(enMatch[1]) - 1, // Monat (0-11)
                parseInt(enMatch[2]),     // Tag
                hours,                    // Stunde (12h-Format konvertiert zu 24h)
                parseInt(enMatch[5]),     // Minute
                parseInt(enMatch[6])      // Sekunde
            );
            return date.getTime();
        }
        
        // Standardversuch mit JavaScript Date.parse
        try {
            date = new Date(dateStr);
            if (!isNaN(date.getTime())) {
                return date.getTime();
            }
        } catch (e) {
            console.warn('Fehler beim Parsen des Datums:', dateStr);
        }
        
        // Fallback: Als String vergleichen
        return dateStr;
    }
    
    /**
     * Spielerdaten verarbeiten und separieren
     */
    function processPlayerData(data) {
        // Alle Spielerlisten zurücksetzen
        allPlayers = [];
        onlinePlayers = [];
        offlinePlayers = [];
        
        // Daten validieren
        if (!data || !data.players || !Array.isArray(data.players)) {
            console.error('Ungültige Spielerdaten vom Server');
            showErrorInTable(onlinePlayersTable, 'Ungültiges Datenformat');
            showErrorInTable(offlinePlayersTable, 'Ungültiges Datenformat');
            return;
        }
        
        // Datenfelder standardisieren - Stellen Sie sicher, dass jeder Spieler mindestens die Basisfelder hat
        data.players = data.players.map(player => {
            return {
                ...player,
                name: player.name || 'Unbekannt',
                company: player.company || null, // Stellt sicher, dass immer ein company-Feld existiert
                online: player.online === true,  // Boolean-Standardisierung
                // Standardwerte für Level-Felder
                taxi_level: player.taxi_level || 0,
                bus_level: player.bus_level || 0,
                wrecker_level: player.wrecker_level || 0, 
                police_level: player.police_level || 0,
                driver_level: player.driver_level || 0,
                truck_level: player.truck_level || 0
            };
        });
        
        // Player-Map erstellen, um Informationen zu erhalten
        const playerMap = {};
        
        // Zuerst alle Spieler in einer Map speichern, damit wir Informationen 
        // wie Unternehmen von Online-Spielern für Offline-Spieler verwenden können
        data.players.forEach(player => {
            // Unique ID basierend auf Steam-ID oder Namen
            const playerId = player.steam_id || player.name;
            
            // Wenn der Spieler bereits existiert, aktualisieren wir nur die Informationen
            if (playerMap[playerId]) {
                // Unternehmensinformation aktualisieren, aber nur wenn vorhanden
                if (player.company) {
                    playerMap[playerId].company = player.company;
                }
                
                // Online-Status aktualisieren
                if (player.online === true) {
                    playerMap[playerId].online = true;
                }
                
                // Letzte Aktivität aktualisieren, wenn neuer
                if (player.last_seen) {
                    const existingDate = playerMap[playerId].last_seen ? new Date(playerMap[playerId].last_seen) : null;
                    const newDate = new Date(player.last_seen);
                    if (!existingDate || (newDate > existingDate)) {
                        playerMap[playerId].last_seen = player.last_seen;
                    }
                }
            } else {
                // Neuen Spieler hinzufügen
                playerMap[playerId] = player;
            }
        });
        
        // Spielerdaten speichern und nach Online/Offline separieren
        allPlayers = Object.values(playerMap);
        
        // Aktuelle Zeit für Verarbeitung verwenden
        const currentTime = new Date();
        onlinePlayers = [];
        offlinePlayers = [];
        
        // Spieler nach Status sortieren mit Timeout-Prüfung (24 Stunden)
        const now = new Date().getTime();
        const onlineTimeoutMs = 24 * 60 * 60 * 1000; // 24 Stunden in Millisekunden
        
        allPlayers.forEach(player => {
            // Zeitstempel vom Server parsen (Format: "2025.04.28-19.59.40")
            const lastSeenString = player.last_seen || '';
            let lastSeenDate = null;
            let lastSeenTime = 0;
            
            // Debug-Ausgabe zum Troubleshooting
            console.log('Spieler:', player.name, 'Last-Seen:', lastSeenString);
            
            // Regex für verschiedene Datumsformate
            const dateRegex = /^(\d{4})[\.\-](\d{2})[\.\-](\d{2})[\.\-](\d{2})[\.\-](\d{2})[\.\-](\d{2})$/;
            const match = lastSeenString.match(dateRegex);
            
            if (match) {
                // Match gefunden: Format "2025.04.28-19.59.40" oder ähnlich
                lastSeenDate = new Date(
                    parseInt(match[1]), // Jahr
                    parseInt(match[2]) - 1, // Monat (0-11)
                    parseInt(match[3]), // Tag
                    parseInt(match[4]), // Stunde
                    parseInt(match[5]), // Minute
                    parseInt(match[6])  // Sekunde
                );
                
                // Datum-Validität prüfen
                if (!isNaN(lastSeenDate.getTime())) {
                    lastSeenTime = lastSeenDate.getTime();
                    console.log('Datum geparst:', lastSeenDate);
                } else {
                    console.error('Ungültiges Datum nach Parsing:', lastSeenString);
                }
            } else if (lastSeenString) {
                // Versuch mit Standard-JavaScript-Parsing (ISO-Format)
                try {
                    lastSeenDate = new Date(lastSeenString);
                    if (!isNaN(lastSeenDate.getTime())) {
                        lastSeenTime = lastSeenDate.getTime();
                        console.log('Standard-Datum geparst:', lastSeenDate);
                    } else {
                        console.error('Ungültiges Standard-Datum:', lastSeenString);
                    }
                } catch (e) {
                    console.error('Fehler beim Datums-Parsing:', e);
                }
            }
            
            // Prüfen, ob der Spieler online ist
            // Ein Spieler gilt als online, wenn er in den letzten 24 Stunden gesehen wurde
            // oder wenn das online-Flag vom Server bereits gesetzt ist
            // Sicherheitsprüfung bei der Berechnung der aktiven Zeit
            const currentTimeMs = currentTime.getTime();
            const timeDiff = lastSeenTime > 0 ? (currentTimeMs - lastSeenTime) : Infinity;
            
            // Wir ändern die Definition von "online"
            // Option 1: Nur Spieler, die vom Server als online markiert sind (strikteste Version)
            // Option 2: Spieler, die in der letzten Stunde aktiv waren (60 Minuten)
            // Option 3: Spieler, die in den letzten 24 Stunden aktiv waren (ursprüngliche Version)
            
            const onlineTimeThreshold = 60 * 60 * 1000; // 1 Stunde in Millisekunden
            const isRecentlyActive = lastSeenTime > 0 && timeDiff < onlineTimeThreshold;
            
            // Spezieller Debug für problematische Spieler
            const isChasor = (player.name === 'Chasor' || player.steam_id === '76561197971612278');
            
            if (isChasor) {
                console.log('SPEZIALFALL CHASOR GEFUNDEN!', player);
                console.log('Chasor Online-Status:', player.online);
                console.log('Typ von Online-Status:', typeof player.online);
                console.log('Rohdaten für Chasor:', JSON.stringify(player));
            }
            
            // Debug-Ausgabe
            console.log('Spieler:', player.name, 'Online-Status vom Server:', player.online === true, 'Aktiv in letzter Stunde:', isRecentlyActive, 'Zeitdifferenz:', Math.round(timeDiff/60000), 'Minuten');
            
            // Option 1: Nur Spieler, die explizit als online markiert sind
            // Wir stellen sicher, dass der online-Status genau boolean true ist, nicht nur truthy
            player.online = (player.online === true);
            
            // Für den Chasor-Fall explizit prüfen
            if (isChasor) {
                player.online = false; // Forciere offline für Chasor
                console.log('Chasor wurde explizit auf offline gesetzt');
            }
            
            if (player.online) {
                onlinePlayers.push(player);
            } else {
                offlinePlayers.push(player);
            }
        });
        
        // Nach Login-Zeit sortieren (neueste zuerst)
        onlinePlayers.sort((a, b) => {
            const timeA = a.last_seen ? new Date(a.last_seen).getTime() : 0;
            const timeB = b.last_seen ? new Date(b.last_seen).getTime() : 0;
            return timeB - timeA;
        });
        
        offlinePlayers.sort((a, b) => {
            const timeA = a.last_seen ? new Date(a.last_seen).getTime() : 0;
            const timeB = b.last_seen ? new Date(b.last_seen).getTime() : 0;
            return timeB - timeA;
        });
        
        // Anzahl anzeigen
        if (onlinePlayerCount) onlinePlayerCount.textContent = onlinePlayers.length;
        if (offlinePlayerCount) offlinePlayerCount.textContent = offlinePlayers.length;
        
        // Tabellen anzeigen
        displayOnlinePlayers();
        displayOfflinePlayers();
    }
    
    /**
     * Online-Spieler-Tabelle anzeigen mit Paginierung
     */
    function displayOnlinePlayers() {
        if (!onlinePlayersTable) return;
        
        // Berechne Gesamt-Seitenzahl
        const totalPages = Math.ceil(onlinePlayers.length / onlinePlayersPerPage);
        
        // Stelle sicher, dass die aktuelle Seite gültig ist
        if (onlineCurrentPage > totalPages) onlineCurrentPage = totalPages;
        if (onlineCurrentPage < 1) onlineCurrentPage = 1;
        
        // Aktualisiere Paginierungs-Info
        updatePagination('online', onlineCurrentPage, totalPages);
        
        // Wenn keine Spieler online sind
        if (onlinePlayers.length === 0) {
            onlinePlayersTable.querySelector('tbody').innerHTML = `
                <tr>
                    <td colspan="11" class="text-center">
                        ${t('no_players_online')}
                    </td>
                </tr>
            `;
            return;
        }
        
        // Berechne den Startindex und Endindex der aktuellen Seite
        const startIndex = (onlineCurrentPage - 1) * onlinePlayersPerPage;
        const endIndex = Math.min(startIndex + onlinePlayersPerPage, onlinePlayers.length);
        
        // Tabelle leeren
        onlinePlayersTable.querySelector('tbody').innerHTML = '';
        
        // Spielerdaten für die aktuelle Seite anzeigen
        for (let i = startIndex; i < endIndex; i++) {
            const player = onlinePlayers[i];
            addPlayerToTable(onlinePlayersTable, player);
        }
        
        // Event-Listener für Buttons hinzufügen
        addButtonEventListeners();
    }
    
    /**
     * Offline-Spieler-Tabelle anzeigen mit Paginierung
     */
    function displayOfflinePlayers() {
        if (!offlinePlayersTable) return;
        
        // Berechne Gesamt-Seitenzahl
        const totalPages = Math.ceil(offlinePlayers.length / offlinePlayersPerPage);
        
        // Stelle sicher, dass die aktuelle Seite gültig ist
        if (offlineCurrentPage > totalPages) offlineCurrentPage = totalPages;
        if (offlineCurrentPage < 1) offlineCurrentPage = 1;
        
        // Aktualisiere Paginierungs-Info
        updatePagination('offline', offlineCurrentPage, totalPages);
        
        // Wenn keine Spieler offline sind
        if (offlinePlayers.length === 0) {
            offlinePlayersTable.querySelector('tbody').innerHTML = `
                <tr>
                    <td colspan="11" class="text-center">
                        ${t('no_players_offline')}
                    </td>
                </tr>
            `;
            return;
        }
        
        // Berechne den Startindex und Endindex der aktuellen Seite
        const startIndex = (offlineCurrentPage - 1) * offlinePlayersPerPage;
        const endIndex = Math.min(startIndex + offlinePlayersPerPage, offlinePlayers.length);
        
        // Tabelle leeren
        offlinePlayersTable.querySelector('tbody').innerHTML = '';
        
        // Spielerdaten für die aktuelle Seite anzeigen
        for (let i = startIndex; i < endIndex; i++) {
            const player = offlinePlayers[i];
            addPlayerToTable(offlinePlayersTable, player);
        }
        
        // Event-Listener für Buttons hinzufügen
        addButtonEventListeners();
    }
    
    /**
     * Fügt einen Spieler zur angegebenen Tabelle hinzu
     */
    function addPlayerToTable(table, player) {
        const row = document.createElement('tr');
        
        // Unternehmensanzeige - Stellt sicher, dass company nicht undefined ist
        const companyDisplay = player.company ? escapeHtml(player.company) : `<span class="text-muted">${t('no_company')}</span>`;
        
        // Steam-Profil-URL erstellen
        const steamId = player.steam_id || '';
        const steamProfileUrl = steamId ? `https://steamcommunity.com/profiles/${steamId}` : '#';
        
        row.innerHTML = `
            <td><strong>${escapeHtml(player.name)}</strong></td>
            <td>
                <a href="${steamProfileUrl}" target="_blank" class="steam-profile-link" 
                   onclick="event.stopPropagation();">
                   ${escapeHtml(steamId)}
                   <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </td>
            <td>${formatDate(player.last_seen)}</td>
            <td>${companyDisplay}</td>
            <td>${player.taxi_level || 0}</td>
            <td>${player.bus_level || 0}</td>
            <td>${player.wrecker_level || 0}</td>
            <td>${player.police_level || 0}</td>
            <td>${player.driver_level || 0}</td>
            <td>${player.truck_level || 0}</td>
        `;
        
        // Füge Zeile zur Tabelle hinzu
        table.querySelector('tbody').appendChild(row);
        
        // Event-Listener für Zeilen-Klick für Detailansicht
        row.classList.add('clickable-row');
        row.addEventListener('click', function() {
            // Spielerdetails im Modal anzeigen
            loadPlayerDetails(player.steam_id);
        });
    }
    
    /**
     * Paginierungselemente aktualisieren
     */
    function updatePagination(tableType, currentPage, totalPages) {
        // Paginierungsinformationstext aktualisieren
        const paginationInfo = document.getElementById(`${tableType}PaginationInfo`);
        if (paginationInfo) {
            paginationInfo.textContent = `${t('page')} ${currentPage} ${t('of')} ${totalPages}`;
        }
        
        // Top und Bottom Paginierungsbuttons aktualisieren
        ['Top', 'Bottom'].forEach(position => {
            const paginationContainer = document.getElementById(`${tableType}Pagination${position}`);
            if (!paginationContainer) return;
            
            // Leeren
            paginationContainer.innerHTML = '';
            
            // Zurück-Button
            const prevButton = document.createElement('button');
            prevButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'page-prev');
            prevButton.innerHTML = '&laquo;';
            prevButton.disabled = currentPage <= 1;
            prevButton.addEventListener('click', () => {
                if (tableType === 'online') {
                    onlineCurrentPage--;
                    displayOnlinePlayers();
                } else {
                    offlineCurrentPage--;
                    displayOfflinePlayers();
                }
            });
            paginationContainer.appendChild(prevButton);
            
            // Max. 5 Seitenzahlen anzeigen
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            // Sicherstellen, dass wir immer maxPagesToShow Seiten anzeigen, wenn möglich
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            // Seitenzahlen-Buttons
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary');
                if (i === currentPage) pageButton.classList.add('active');
                pageButton.textContent = i;
                pageButton.addEventListener('click', () => {
                    if (tableType === 'online') {
                        onlineCurrentPage = i;
                        displayOnlinePlayers();
                    } else {
                        offlineCurrentPage = i;
                        displayOfflinePlayers();
                    }
                });
                paginationContainer.appendChild(pageButton);
            }
            
            // Weiter-Button
            const nextButton = document.createElement('button');
            nextButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'page-next');
            nextButton.innerHTML = '&raquo;';
            nextButton.disabled = currentPage >= totalPages;
            nextButton.addEventListener('click', () => {
                if (tableType === 'online') {
                    onlineCurrentPage++;
                    displayOnlinePlayers();
                } else {
                    offlineCurrentPage++;
                    displayOfflinePlayers();
                }
            });
            paginationContainer.appendChild(nextButton);
        });
    }
    
    /**
     * Event-Listener für Buttons in der Tabelle hinzufügen
     */
    function addButtonEventListeners() {
        // Event-Listener für Details-Buttons
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const steamId = this.getAttribute('data-steam-id');
                loadPlayerDetails(steamId);
            });
        });
        
        // Event-Listener für Kick-Buttons
        document.querySelectorAll('.kick-player').forEach(button => {
            button.addEventListener('click', function() {
                const steamId = this.getAttribute('data-steam-id');
                kickPlayer(steamId);
            });
        });
        
        // Event-Listener für Ban-Buttons
        document.querySelectorAll('.ban-player').forEach(button => {
            button.addEventListener('click', function() {
                const steamId = this.getAttribute('data-steam-id');
                banPlayer(steamId);
            });
        });
    }
    
    /**
     * Spielerdetails laden
     */
    function loadPlayerDetails(steamId) {
        // Zuerst sicherstellen, dass keine alten modalen Hintergründe vorhanden sind
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Modal anzeigen mit Ladeanimation
        const playerDetailModal = document.getElementById('playerDetailModal');
        const playerModal = new bootstrap.Modal(playerDetailModal);
        document.getElementById('playerDetailName').innerHTML = `<div class="spinner-border spinner-border-sm" role="status"></div> ${t('loading')}`;
        playerModal.show();
        
        // Vorläufige Informationen aus dem Cache anzeigen (Name, Steam ID)
        let playerCache = null;
        for (const player of allPlayers) {
            if (player.steam_id === steamId) {
                playerCache = player;
                break;
            }
        }
        
        // Grundlegende Daten anzeigen, während wir die vollständigen Daten laden
        if (playerCache) {
            document.getElementById('playerDetailName').textContent = playerCache.name || 'Unbekannt';
            document.getElementById('playerDetailSteamId').textContent = playerCache.steam_id || '-';
        }
        
        // Immer detaillierte Daten vom Server laden, um sicherzustellen, dass Fahrzeugdaten vollständig sind
        const formData = new FormData();
        formData.append('action', 'get_player_detail');
        formData.append('steam_id', steamId);
        
        // CSRF-Token hinzufügen
        if (typeof CSRF_TOKEN !== 'undefined') {
            formData.append('csrf_token', CSRF_TOKEN);
        } else {
            console.warn('CSRF-Token nicht gefunden, Anfrage könnte fehlschlagen');
        }
        
        console.log('Lade detaillierte Spielerdaten für:', steamId);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            // Erst Content-Type prüfen
            const contentType = response.headers.get('content-type');
            
            // Text abrufen für Debugging oder bei HTML-Fehlern
            const responseText = await response.text();
            
            // Prüfen, ob es ein HTML-Fehler ist
            if (responseText.trim().startsWith('<') || 
                responseText.includes('<!DOCTYPE html>') ||
                responseText.includes('<br />') ||
                responseText.includes('<b>')) {
                console.error('Server gab HTML statt JSON zurück:', responseText.substring(0, 200));
                throw new Error('Server-Fehler: Bitte überprüfen Sie die Server-Logs');
            }
            
            // Versuchen, die Antwort als JSON zu parsen
            try {
                return JSON.parse(responseText);
            } catch (e) {
                console.error('JSON-Parse-Fehler:', e);
                console.error('Response-Text:', responseText.substring(0, 200));
                throw new Error('Ungültiges JSON vom Server erhalten');
            }
        })
        .then(data => {
            console.log('Erhaltene Spielerdaten:', data);
            displayPlayerDetails(data);
        })
        .catch(error => {
            console.error('Fehler beim Laden der Spielerdetails:', error);
            
            // Spieler-Modal mit Fehlermeldung aktualisieren
            const nameElement = document.getElementById('playerDetailName');
            if (nameElement) {
                nameElement.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${error.message}</span>`;
            }
            
            // Fehlerinfo in vorhandene Elemente einfügen
            const statsElement = document.getElementById('playerDetailStats');
            if (statsElement) {
                // Alle Kinder entfernen
                while (statsElement.firstChild) {
                    statsElement.removeChild(statsElement.firstChild);
                }
                
                // Fehlermeldung hinzufügen
                const errorInfo = document.createElement('li');
                errorInfo.className = 'list-group-item bg-danger text-white';
                errorInfo.innerHTML = `
                    <div>
                        <p><strong>Fehler beim Laden der Spielerdaten</strong></p>
                        <p>Mögliche Ursachen:</p>
                        <ul>
                            <li>Der Chat-Server ist nicht erreichbar</li>
                            <li>Es liegt ein Berechtigungsproblem vor</li>
                            <li>Die Spieler-Datenbank enthält ungültige Daten</li>
                        </ul>
                        <p>Bitte überprüfen Sie die Server-Logs für weitere Details.</p>
                    </div>
                `;
                statsElement.appendChild(errorInfo);
            }
            
            // Ladeanzeige ausblenden
            const loadingElements = document.querySelectorAll('.player-detail-loading');
            loadingElements.forEach(el => {
                if (el) el.style.display = 'none';
            });
            
            // Inhalt anzeigen, falls vorhanden
            const contentElement = document.getElementById('playerDetailContent');
            if (contentElement) {
                contentElement.style.display = 'block';
            }
        });
    }
    
    /**
     * Spielerdetails anzeigen
     */
    function displayPlayerDetails(data) {
        console.log('Erhaltene Daten in displayPlayerDetails:', data);
        
        if (!data.player) {
            console.error('Keine Spielerdaten erhalten!');
            document.getElementById('playerDetailName').innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${t('player_not_found')}</span>`;
            return;
        }
        
        const player = data.player;
        console.log('Spielerdaten zum Anzeigen:', player);
        
        try {
            // Spielername
            document.getElementById('playerDetailName').textContent = player.name || 'Spieler';
            
            // Spielerinformationen
            document.getElementById('playerDetailSteamId').textContent = player.steam_id || '-';
            
            // Status-Badge
            const statusBadge = player.online 
                ? `<span class="badge bg-success">${t('online')}</span>` 
                : `<span class="badge bg-secondary">${t('offline')}</span>`;
            document.getElementById('playerDetailStatus').innerHTML = statusBadge;
            
            document.getElementById('playerDetailLastSeen').textContent = formatDate(player.last_seen);
            document.getElementById('playerDetailCompany').textContent = player.company || t('no_company');
            document.getElementById('playerDetailTaxiLevel').textContent = player.taxi_level || '0';
            document.getElementById('playerDetailBusLevel').textContent = player.bus_level || '0';
            document.getElementById('playerDetailWreckerLevel').textContent = player.wrecker_level || '0';
            document.getElementById('playerDetailPoliceLevel').textContent = player.police_level || '0';
            
            // Neue Jobtypen
            if (document.getElementById('playerDetailDriverLevel')) {
                document.getElementById('playerDetailDriverLevel').textContent = player.driver_level || '0';
            }
            if (document.getElementById('playerDetailTruckLevel')) {
                document.getElementById('playerDetailTruckLevel').textContent = player.truck_level || '0';
            }
        } catch (e) {
            console.error('Fehler beim Anzeigen der Spielerinformationen:', e);
        }
        
        // Fahrzeugstatistiken
        const vehicleCount = player.vehicles ? player.vehicles.length : 0;
        document.getElementById('playerVehicleCount').textContent = vehicleCount.toString();
        
        // Ermittlung des am häufigsten verwendeten Fahrzeugs
        let mostUsedVehicle = '-';
        if (player.vehicles && player.vehicles.length > 0) {
            // Wenn most_used_vehicle bereits vom Server bestimmt wurde, verwenden wir diesen Wert
            if (player.most_used_vehicle) {
                mostUsedVehicle = player.most_used_vehicle;
            } else {
                // Ansonsten bestimmen wir es basierend auf den Fahrzeugdaten
                const vehicleCounts = {};
                
                // Zählen, wie oft jedes Fahrzeug verwendet wurde
                player.vehicles.forEach(vehicle => {
                    const vehicleName = vehicle.name || 'Unbekannt';
                    vehicleCounts[vehicleName] = (vehicleCounts[vehicleName] || 0) + 1;
                });
                
                // Fahrzeug mit der höchsten Anzahl finden
                let maxCount = 0;
                for (const [vehicle, count] of Object.entries(vehicleCounts)) {
                    if (count > maxCount) {
                        maxCount = count;
                        mostUsedVehicle = vehicle;
                    }
                }
            }
        }
        
        document.getElementById('playerMostUsedVehicle').textContent = mostUsedVehicle;
        
        // Fahrzeugtabelle
        const vehiclesTable = document.getElementById('playerVehiclesTable').querySelector('tbody');
        console.log('Fahrzeugdaten für Tabelle:', player.vehicles); // Debug-Ausgabe
        
        try {
            // Fahrzeugzähler aktualisieren
            const vehicleCount = player.vehicles ? player.vehicles.length : 0;
            document.getElementById('playerVehicleCount').textContent = vehicleCount.toString();
            
            // Ermittlung des am häufigsten verwendeten Fahrzeugs
            if (player.vehicles && player.vehicles.length > 0 && player.most_used_vehicle) {
                document.getElementById('playerMostUsedVehicle').textContent = player.most_used_vehicle;
            } else {
                document.getElementById('playerMostUsedVehicle').textContent = '-';
            }
        } catch (e) {
            console.error('Fehler beim Aktualisieren der Fahrzeugstatistiken:', e);
        }
        
        try {
            if (player.vehicles && player.vehicles.length > 0) {
                vehiclesTable.innerHTML = '';
                
                // Sortiere Fahrzeuge nach letzter Nutzung (neueste zuerst)
                const sortedVehicles = [...player.vehicles].sort((a, b) => {
                    // Umgang mit verschiedenen Datumsformaten
                    let dateA = 0;
                    let dateB = 0;
                    
                    if (a.last_used) {
                        // Versuche, das Datum zu parsen (Format: "2025.04.28-21.17.37")
                        const partsA = a.last_used.split(/[\.\-:]/);
                        if (partsA.length >= 6) {
                            const dateObjA = new Date(
                                parseInt(partsA[0]), // Jahr
                                parseInt(partsA[1]) - 1, // Monat (0-11)
                                parseInt(partsA[2]), // Tag
                                parseInt(partsA[3]), // Stunde
                                parseInt(partsA[4]), // Minute
                                parseInt(partsA[5])  // Sekunde
                            );
                            if (!isNaN(dateObjA.getTime())) {
                                dateA = dateObjA.getTime();
                            }
                        } else {
                            // Standarddatumsformat versuchen
                            const stdDateA = new Date(a.last_used);
                            if (!isNaN(stdDateA.getTime())) {
                                dateA = stdDateA.getTime();
                            }
                        }
                    }
                    
                    if (b.last_used) {
                        // Versuche, das Datum zu parsen (Format: "2025.04.28-21.17.37")
                        const partsB = b.last_used.split(/[\.\-:]/);
                        if (partsB.length >= 6) {
                            const dateObjB = new Date(
                                parseInt(partsB[0]), // Jahr
                                parseInt(partsB[1]) - 1, // Monat (0-11)
                                parseInt(partsB[2]), // Tag
                                parseInt(partsB[3]), // Stunde
                                parseInt(partsB[4]), // Minute
                                parseInt(partsB[5])  // Sekunde
                            );
                            if (!isNaN(dateObjB.getTime())) {
                                dateB = dateObjB.getTime();
                            }
                        } else {
                            // Standarddatumsformat versuchen
                            const stdDateB = new Date(b.last_used);
                            if (!isNaN(stdDateB.getTime())) {
                                dateB = stdDateB.getTime();
                            }
                        }
                    }
                    
                    return dateB - dateA; // Absteigend sortieren
                });
                
                console.log(`Zeige ${sortedVehicles.length} Fahrzeuge in der Tabelle an`);
                
                sortedVehicles.forEach(vehicle => {
                    const row = document.createElement('tr');
                    console.log('Verarbeite Fahrzeug:', vehicle); // Debug pro Fahrzeug
                    
                    // Bestimme die Zeilenfarbe basierend auf der letzten Verwendung
                    let lastUsedDate = null;
                    const lastUsedStr = vehicle.last_used || '';
                    
                    // Datumsformat "2025.04.28-21.17.37" parsen
                    const dateParts = lastUsedStr.split(/[\.\-:]/);
                    if (dateParts.length >= 6) {
                        lastUsedDate = new Date(
                            parseInt(dateParts[0]), // Jahr
                            parseInt(dateParts[1]) - 1, // Monat (0-11)
                            parseInt(dateParts[2]), // Tag
                            parseInt(dateParts[3]), // Stunde
                            parseInt(dateParts[4]), // Minute
                            parseInt(dateParts[5])  // Sekunde
                        );
                    }
                    
                    const isRecentlyUsed = lastUsedDate && !isNaN(lastUsedDate.getTime()) && 
                        (new Date().getTime() - lastUsedDate.getTime() < 24 * 60 * 60 * 1000);
                    
                    if (isRecentlyUsed) {
                        row.classList.add('table-primary'); // Hebt kürzlich verwendete Fahrzeuge hervor
                    }
                    
                    // Direktes DOM-Manipulation statt innerHTML für bessere Sicherheit
                    const nameCell = document.createElement('td');
                    nameCell.textContent = vehicle.name || '-';
                    row.appendChild(nameCell);
                    
                    const idCell = document.createElement('td');
                    idCell.textContent = vehicle.id || '-';
                    row.appendChild(idCell);
                    
                    const lastUsedCell = document.createElement('td');
                    // Verwende das bereits geparste Datum, wenn verfügbar
                    if (lastUsedDate && !isNaN(lastUsedDate.getTime())) {
                        lastUsedCell.textContent = formatDate(lastUsedDate);
                    } else {
                        // Fallback auf den Rohwert, wenn das Parsing fehlgeschlagen ist
                        lastUsedCell.textContent = vehicle.last_used || '-';
                    }
                    row.appendChild(lastUsedCell);
                    vehiclesTable.appendChild(row);
                });
            } else {
                console.log('Keine Fahrzeuge gefunden');
                vehiclesTable.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center" id="noVehiclesFound">
                            ${t('no_vehicles_found')}
                        </td>
                    </tr>
                `;
            }
        } catch (e) {
            console.error('Fehler beim Anzeigen der Fahrzeugtabelle:', e);
            vehiclesTable.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Fehler beim Laden der Fahrzeugdaten: ${e.message}
                    </td>
                </tr>
            `;
        }
        
        // Aktivitätstabelle
        const activitiesTable = document.getElementById('playerActivitiesTable').querySelector('tbody');
        if (player.activities && player.activities.length > 0) {
            activitiesTable.innerHTML = '';
            player.activities.forEach(activity => {
                const row = document.createElement('tr');
                // Zeitstempel mit dem verbesserten formatDate parsen
                const timeCell = document.createElement('td');
                timeCell.textContent = formatDate(activity.timestamp);
                row.appendChild(timeCell);
                
                // Aktivitätstyp
                const actionCell = document.createElement('td');
                actionCell.textContent = activity.action || '-';
                row.appendChild(actionCell);
                
                // Details
                const detailsCell = document.createElement('td');
                detailsCell.textContent = activity.details || '-';
                row.appendChild(detailsCell);
                activitiesTable.appendChild(row);
            });
        } else {
            activitiesTable.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center" id="noActivitiesFound">
                        ${t('no_activities_found')}
                    </td>
                </tr>
            `;
        }
    }
    
    /**
     * Spieler kicken
     */
    function kickPlayer(steamId) {
        if (confirm(t('confirm_kick'))) {
            const formData = new FormData();
            formData.append('action', 'kick_player');
            formData.append('steam_id', steamId);
            
            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', t('player_kicked'));
                    loadPlayerData();
                } else {
                    showToast('danger', data.message || t('error_kick'));
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                showToast('danger', t('error_kick'));
            });
        }
    }
    
    /**
     * Spieler bannen
     */
    function banPlayer(steamId) {
        if (confirm(t('confirm_ban'))) {
            const formData = new FormData();
            formData.append('action', 'ban_player');
            formData.append('steam_id', steamId);
            
            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', t('player_banned'));
                    loadPlayerData();
                } else {
                    showToast('danger', data.message || t('error_ban'));
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                showToast('danger', t('error_ban'));
            });
        }
    }
    
    /**
     * Toast-Nachricht anzeigen
     */
    function showToast(type, message) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '11';
        
        toastContainer.innerHTML = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toastContainer);
        const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
        toast.show();
        
        // Toast nach 5 Sekunden entfernen
        setTimeout(() => {
            document.body.removeChild(toastContainer);
        }, 5000);
    }
    
    /**
     * Datum formatieren
     * Diese Funktion formatiert ein Datum basierend auf der aktuellen Sprache
     */
    function formatDate(dateInput) {
        if (!dateInput) return '-';
        
        let date;
        
        // Wenn dateInput bereits ein Date-Objekt ist
        if (dateInput instanceof Date) {
            date = dateInput;
        } else if (typeof dateInput === 'string') {
            // Spezialfall für das Format "2025.04.28-21.17.37"
            const dateParts = dateInput.split(/[\.\-:]/);
            if (dateParts.length >= 6) {
                date = new Date(
                    parseInt(dateParts[0]),     // Jahr
                    parseInt(dateParts[1]) - 1, // Monat (0-11)
                    parseInt(dateParts[2]),     // Tag
                    parseInt(dateParts[3]),     // Stunde
                    parseInt(dateParts[4]),     // Minute
                    parseInt(dateParts[5])      // Sekunde
                );
            } else {
                // Standard-JavaScript-Datumsparser verwenden
                date = new Date(dateInput);
            }
        } else {
            return '-';
        }
        
        // Überprüfen, ob das Datum gültig ist
        if (isNaN(date.getTime())) {
            console.log('Ungültiges Datum:', dateInput);
            return dateInput; // Fallback auf den Originalstring
        }
        
        // Formatierungsoptionen
        const options = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        
        return date.toLocaleString(document.documentElement.lang || 'de-DE', options);
    }
    
    /**
     * HTML-Escaping für Sicherheit
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    /**
     * Übersetzungsfunktion (t)
     */
    function t(key) {
        if (typeof translations !== 'undefined' && translations[key]) {
            return translations[key];
        }
        return key;
    }
    
    /**
     * Berechtigungsprüfung
     */
    function hasPermission(permission) {
        if (typeof userPermissions !== 'undefined' && userPermissions.includes(permission)) {
            return true;
        }
        return false;
    }
});
