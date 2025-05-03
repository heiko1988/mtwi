// assets/js/live_chat.js
$(function() {
    // Chat-Log laden Button
    let chatLogVisible = false;
    $('#loadChatLogBtn').on('click', function() {
        const $output = $('#chatLogOutput');
        if (chatLogVisible) {
            $output.slideUp(120);
            chatLogVisible = false;
            return;
        }
        $output.html('<div class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Lädt Chat-Log ...</div>').slideDown(120);
        chatLogVisible = true;
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: { action: 'get_live_chat_messages', csrf_token: CSRF_TOKEN },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success && Array.isArray(data.data)) {
                        let html = '';
                        // Array umdrehen, damit älteste Nachrichten oben und neueste unten angezeigt werden
                        const messages = [...data.data].reverse();
                        messages.forEach(msg => {
                            if (msg.author && msg.author.toLowerCase() === 'admin') {
                                const zeit = msg.timestamp ? `<span style="color:#aaa;">[${msg.timestamp}]</span> ` : '';
                                html += `<div style="margin-bottom:2px; background: #ffe066; color: #222; font-weight: bold; border-radius: 0.25em; padding: 2px 8px; display:inline-block;">${zeit}<span class='fw-bold'>admin</span>: ${msg.text}</div>`;
                            }
                        });
                        $output.html(html || '<div class="text-muted">Keine Chatdaten gefunden.</div>');
                    } else {
                        $output.html('<div class="text-danger">Fehler beim Laden!</div>');
                    }
                } catch(e) {
                    $output.html('<div class="text-danger">Fehler beim Parsen!</div>');
                }
            },
            error: function() {
                $output.html('<div class="text-danger">Serverfehler!</div>');
            }
        });
    });
    // CSS fix for dark chat box readability
    $('<style>\n#liveChatMessages a { color: #0dcaf0; }\n#liveChatMessages .text-muted, #liveChatMessages .text-center.text-muted { color: #adb5bd !important; }\n</style>').appendTo('head');

    function scrollLiveChatToBottom() {
        var box = document.getElementById('liveChatMessages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function isAutoScrollEnabled() {
        return $('#liveChatAutoScroll').is(':checked');
    }

    // Nachricht senden (zentral)
    function sendLiveChatMessage(message) {
        var $input = $('#liveChatInput');
        var $button = $('#liveChatForm button[type=submit]');
        if (!message) return;
        $input.prop('disabled', true);
        $button.prop('disabled', true);
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'send_chat_message',
                message: message,
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $input.val('');
                        fetchLiveChatMessages();
                    } else {
                        alert(data.message || 'Fehler beim Senden!');
                    }
                } catch(e) {
                    alert('Fehler beim Parsen der Antwort!');
                }
            },
            error: function() {
                alert('Serverfehler beim Senden!');
            },
            complete: function() {
                $input.prop('disabled', false);
                $button.prop('disabled', false);
                $input.focus();
            }
        });
    }
    // Form-Submit für Live-Chat
    $('#liveChatForm').on('submit', function(e) {
        e.preventDefault();
        var msg = $('#liveChatInput').val().trim();
        sendLiveChatMessage(msg);
    });
    // Schnellnachrichten-Button für Live-Chat (nur im Live-Chat-Bereich!)
    $(document).off('click.livechat', '.send-quick-message').on('click.livechat', '.send-quick-message', function(e) {
        // Verhindere doppeltes Senden, falls ein anderes Skript (z.B. chat.js) das Event bereits behandelt hat
        if ($(this).closest('.card').find('#liveChatForm').length === 0) return;
        e.stopImmediatePropagation();
        var msg = $(this).data('message');
        sendLiveChatMessage(msg);
    });

    // Nachrichten abrufen
    function fetchLiveChatMessages() {
        var box = document.getElementById('liveChatMessages');
        // Vor dem Update: Prüfen, ob der User schon ganz unten ist (max 40px Abstand)
        var wasAtBottom = false;
        if (box) {
            wasAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 40;
        }
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'get_live_chat_messages',
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success && Array.isArray(data.data)) {
                        let html = '';
                        // Array umdrehen, damit älteste Nachrichten oben und neueste unten angezeigt werden
                        const messages = [...data.data].reverse();
                        messages.forEach(msg => {
                            if (msg.author && msg.author.toLowerCase() === 'admin') {
                                const zeit = msg.timestamp ? `<span style=\"color:#fff;font-size:0.95em;margin-right:4px;\">[${msg.timestamp}]</span> ` : '';
                                html += `<div style=\"margin-bottom:2px;display:block;\">${zeit}<span class='fw-bold' style=\"background:#ffe066;color:#222;border-radius:0.25em;padding:2px 8px;\">admin</span>: ${msg.text}</div>`;
                            } else {
                                const zeit = msg.timestamp ? `<span style=\"color:#fff;font-size:0.95em;margin-right:4px;\">[${msg.timestamp}]</span> ` : '';
                                html += `<div style=\"margin-bottom:2px;display:block;\">${zeit}<span class='fw-bold'>${msg.author||'Server'}</span>: ${msg.text}</div>`;
                            }
                        });
                        $('#liveChatMessages').html(html);
                        // Nur scrollen, wenn Auto-Scroll aktiv ODER User war vorher schon ganz unten
                        if (isAutoScrollEnabled() || wasAtBottom) scrollLiveChatToBottom();
                    } else {
                        $('#liveChatMessages').html('<div class="text-center text-muted">Keine Nachrichten.</div>');
                    }
                } catch (e) {
                    $('#liveChatMessages').html('<div class="text-center text-danger">Fehler beim Laden!</div>');
                }
            },
            error: function() {
                $('#liveChatMessages').html('<div class="text-center text-danger">Serverfehler!</div>');
            }
        });
    }

    // Initial laden und dann alle 3 Sekunden aktualisieren
    fetchLiveChatMessages();
    setInterval(fetchLiveChatMessages, 3000);

    // Senden
    $('#liveChatForm').on('submit', function(e) {
        e.preventDefault();
        const msg = $('#liveChatInput').val();
        if (!msg) return;
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'send_live_chat_message',
                message: msg,
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                $('#liveChatInput').val('');
                fetchLiveChatMessages();
            }
        });
    });
});
