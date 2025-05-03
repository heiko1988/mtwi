<?php
// Live Chat Fenster für Motor Town Webinterface
?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Live Chat (Experimentell)</h5>
        <span class="badge bg-warning text-dark">Alpha</span>
    </div>
    <div class="card-body">
        <div id="liveChatMessages" class="mb-2" style="height: 300px; overflow-y: auto; background: #23272b; color: #f8f9fa; border: 1px solid var(--bs-secondary); border-radius: 0.5rem; padding: 1rem;">
            <div class="text-center text-muted" id="liveChatPlaceholder">Live Chat wird geladen ...</div>
        </div>
        <div class="form-check form-switch mb-3 ms-1">
            <input class="form-check-input" type="checkbox" id="liveChatAutoScroll" checked>
            <label class="form-check-label" for="liveChatAutoScroll" style="user-select:none;cursor:pointer;">Auto-Scroll</label>
        </div>

        <form id="liveChatForm" autocomplete="off">
            <div class="input-group">
                <input type="text" class="form-control" id="liveChatInput" placeholder="Nachricht eingeben ..." required>
                <button class="btn btn-success" type="submit"><i class="bi bi-send"></i> Senden</button>
            </div>
        </form>
    </div>
</div>
