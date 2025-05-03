/**
 * Motor Town Web Interface (MTWI) - Chat-Skript
 */

$(document).ready(function() {
    // Nachrichten-Historie aus dem LocalStorage laden
    loadMessageHistory();
    
    // Event-Listener für das Nachrichtenformular
    $('#chatMessageForm').on('submit', function(e) {
        e.preventDefault();
        
        const message = $('#chatMessage').val().trim();
        
        if (!message) {
            return;
        }
        
        sendChatMessage(message);
    });
    
    // Funktion zum Senden einer Chat-Nachricht
    function sendChatMessage(message) {
        // AJAX-Anfrage zum Senden der Nachricht
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'send_chat_message',
                message: message,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#sendMessageButton').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        // Nachricht zur Historie hinzufügen
                        addMessageToHistory(message);
                        
                        // Erfolgsmeldung
                        showNotification(data.message, 'success');
                        
                        // Formular zurücksetzen
                        $('#chatMessage').val('');
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (e) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $('#sendMessageButton').prop('disabled', false);
            }
        });
    }
    
    // Vorherige Nachricht wiederverwenden
    $('.message-history').on('click', '.message-item', function() {
        const message = $(this).text();
        $('#chatMessage').val(message);
    });
    
    // Quick-Nachrichten - Anklicken lädt in Eingabefeld
    $('.quick-message').on('click', function() {
        const message = $(this).data('message');
        $('#chatMessage').val(message);
    });
    
    // Schnellnachrichten direkt senden
    $(document).on('click', '.send-quick-message', function() {
        const message = $(this).data('message');
        sendChatMessage(message);
    });
    
    // =============================================
    // Schnellnachrichten-Verwaltungsfunktionen
    // =============================================
    
    // Neue Schnellnachricht hinzufügen
    $('#addQuickMessageBtn').on('click', function() {
        $('#quickMessageId').val('');
        $('#quickMessageText').val('');
        $('#editModalTitle').text(translations.add_quick_message);
        $('#editQuickMessageModal').modal('show');
    });
    
    // Schnellnachricht bearbeiten
    $(document).on('click', '.edit-quick-message', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const message = row.data('message');
        
        $('#quickMessageId').val(id);
        $('#quickMessageText').val(message);
        $('#editModalTitle').text(translations.edit_quick_message);
        $('#editQuickMessageModal').modal('show');
    });
    
    // Schnellnachricht löschen
    $(document).on('click', '.delete-quick-message', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const message = row.data('message');
        
        // In der Anzeige wird der übersetzte Text verwendet
        const displayMessage = row.find('td:first').text();
        
        $('#deleteMessageId').val(id);
        $('#deleteMessageText').text(displayMessage);
        $('#deleteQuickMessageModal').modal('show');
    });
    
    // Schnellnachricht speichern (hinzufügen oder aktualisieren)
    $('#quickMessageForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#quickMessageId').val();
        const message = $('#quickMessageText').val().trim();
        
        if (!message) {
            return;
        }
        
        const action = id ? 'update_quick_message' : 'add_quick_message';
        
        // AJAX-Anfrage zum Speichern der Schnellnachricht
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: action,
                id: id,
                message: message,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#saveQuickMessageBtn').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        // Statt Seite neu zu laden, aktualisieren wir die Ansicht
                        // AJAX-Anfrage, um die aktuellen Schnellnachrichten zu laden
                        $.ajax({
                            url: 'ajax_handler.php',
                            type: 'POST',
                            data: {
                                action: 'get_quick_messages',
                                csrf_token: CSRF_TOKEN
                            },
                            success: function(qmResponse) {
                                try {
                                    const qmData = JSON.parse(qmResponse);
                                    
                                    if (qmData.success && qmData.data && qmData.data.quick_messages) {
                                        updateQuickMessagesDisplay(qmData.data.quick_messages);
                                    }
                                } catch (e) {
                                    console.error('Fehler beim Aktualisieren der Schnellnachrichten', e);
                                }
                            }
                        });
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (e) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $('#saveQuickMessageBtn').prop('disabled', false);
                $('#editQuickMessageModal').modal('hide');
            }
        });
    });
    
    // Schnellnachricht löschen bestätigen
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#deleteMessageId').val();
        
        // AJAX-Anfrage zum Löschen der Schnellnachricht
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'delete_quick_message',
                id: id,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#confirmDeleteBtn').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        // Statt Seite neu zu laden, aktualisieren wir die Ansicht
                        // AJAX-Anfrage, um die aktuellen Schnellnachrichten zu laden
                        $.ajax({
                            url: 'ajax_handler.php',
                            type: 'POST',
                            data: {
                                action: 'get_quick_messages',
                                csrf_token: CSRF_TOKEN
                            },
                            success: function(qmResponse) {
                                try {
                                    const qmData = JSON.parse(qmResponse);
                                    
                                    if (qmData.success && qmData.data && qmData.data.quick_messages) {
                                        updateQuickMessagesDisplay(qmData.data.quick_messages);
                                    }
                                } catch (e) {
                                    console.error('Fehler beim Aktualisieren der Schnellnachrichten', e);
                                }
                            }
                        });
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (e) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $('#confirmDeleteBtn').prop('disabled', false);
                $('#deleteQuickMessageModal').modal('hide');
            }
        });
    });
});

// Nachrichten-Historie laden
function loadMessageHistory() {
    const history = JSON.parse(localStorage.getItem('mtwi_message_history') || '[]');
    const historyContainer = $('.message-history');
    
    historyContainer.empty();
    
    if (history.length === 0) {
        historyContainer.append(`
            <div class="text-center text-muted p-3">
                ${translations.no_messages}
            </div>
        `);
        return;
    }
    
    // Neueste Nachrichten zuerst anzeigen
    history.reverse().forEach(function(item) {
        historyContainer.append(`
            <div class="message-item p-2 border-bottom" title="${formatTimestamp(item.timestamp)}">
                ${item.message}
            </div>
        `);
    });
}

// Funktion zum Aktualisieren der Schnellnachrichten-Anzeige
function updateQuickMessagesDisplay(quickMessages) {
    console.log('Aktualisiere Schnellnachrichten:', quickMessages);
    
    // Schnellnachrichten-Container in der Chat-Ansicht aktualisieren
    const quickMessagesContainer = $('#quickMessagesContainer');
    quickMessagesContainer.empty();
    
    // Tabelle in der Verwaltungsansicht aktualisieren
    const tableBody = $('#quickMessagesTableBody');
    tableBody.empty();
    
    if (!quickMessages || quickMessages.length === 0) {
        console.log('Keine Schnellnachrichten gefunden');
        return;
    }
    
    quickMessages.forEach(function(qm) {
        // Prüfen, ob es sich um einen Übersetzungsschlüssel handelt
        const isTranslated = qm.message && qm.message.substring(0, 2) === 't:';
        const message = qm.message || '';
        const displayMessage = isTranslated 
            ? translations[message.substring(2)] || message.substring(2)
            : message;
        
        // Schnellnachricht-Button für die Chat-Ansicht hinzufügen
        quickMessagesContainer.append(`
            <button class="btn btn-outline-secondary btn-sm send-quick-message" 
                    data-message="${displayMessage}" 
                    data-id="${qm.id}">
                ${displayMessage}
            </button>
        `);
        
        // Zeile für die Tabelle in der Verwaltungsansicht hinzufügen
        tableBody.append(`
            <tr data-id="${qm.id}" data-message="${qm.message}">
                <td>${displayMessage}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary edit-quick-message">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-quick-message">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Nachricht zur Historie hinzufügen
function addMessageToHistory(message) {
    // Aktuelle Historie laden
    const history = JSON.parse(localStorage.getItem('mtwi_message_history') || '[]');
    
    // Neue Nachricht hinzufügen
    history.push({
        message: message,
        timestamp: Math.floor(Date.now() / 1000)
    });
    
    // Auf maximal 20 Einträge begrenzen
    while (history.length > 20) {
        history.shift();
    }
    
    // Zurück in den LocalStorage speichern
    localStorage.setItem('mtwi_message_history', JSON.stringify(history));
    
    // Historie neu laden
    loadMessageHistory();
}
