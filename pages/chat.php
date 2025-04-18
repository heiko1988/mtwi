<?php
// Schnellnachrichten aus der Datenbank laden
$quickMessages = $db->getQuickMessages();
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo t('chat'); ?></h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageQuickMessagesModal">
                    <i class="bi bi-gear"></i> <?php echo t('manage_quick_messages'); ?>
                </button>
            </div>
            <div class="card-body">
                <!-- Hinweise-Box -->
                <div class="alert alert-info mb-3" style="background-color: var(--bs-light); color: var(--bs-dark); border-color: var(--bs-primary);">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Hinweis:</strong> Dies ist nur ein Senden-Chat. Sie können Nachrichten an den Server senden, aber keine Nachrichten empfangen.
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <form id="chatMessageForm">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="chatMessage" placeholder="<?php echo t('message_text'); ?>" autocomplete="off">
                                <button class="btn btn-primary" type="submit" id="sendMessageButton">
                                    <i class="bi bi-send"></i> <?php echo t('send_message'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <h6><?php echo t('quick_messages'); ?></h6>
                            <div class="d-flex flex-wrap gap-2" id="quickMessagesContainer">
                                <?php foreach ($quickMessages as $qm): ?>
                                <?php 
                                    // Wenn die Nachricht mit t: beginnt, handelt es sich um einen Übersetzungsschlüssel
                                    $messageText = substr($qm['message'], 0, 2) === 't:' 
                                        ? t(substr($qm['message'], 2)) 
                                        : $qm['message'];
                                ?>
                                <button class="btn btn-outline-secondary btn-sm send-quick-message" 
                                        data-message="<?php echo htmlspecialchars($messageText); ?>"
                                        data-id="<?php echo $qm['id']; ?>">
                                    <?php echo htmlspecialchars($messageText); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo t('recent_messages'); ?></h6>
                            </div>
                            <div class="message-history p-2" style="max-height: 300px; overflow-y: auto;">
                                <div class="text-center text-muted p-3">
                                    <?php echo t('no_messages'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live-Chat Fenster (experimentell) -->
<?php include __DIR__ . '/live_chat.php'; ?>

<!-- Modal: Schnellnachrichten verwalten -->
<div class="modal fade" id="manageQuickMessagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('manage_quick_messages'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn btn-success btn-sm" id="addQuickMessageBtn">
                        <i class="bi bi-plus-circle"></i> <?php echo t('add_quick_message'); ?>
                    </button>
                </div>
                
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo t('message_text'); ?></th>
                            <th class="text-end"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="quickMessagesTableBody">
                        <?php foreach ($quickMessages as $qm): ?>
                        <?php 
                            // Wenn die Nachricht mit t: beginnt, handelt es sich um einen Übersetzungsschlüssel
                            $isTranslated = substr($qm['message'], 0, 2) === 't:';
                            $rawMessage = $isTranslated ? $qm['message'] : $qm['message']; // Rohwert für Bearbeitung
                            $displayMessage = $isTranslated 
                                ? t(substr($qm['message'], 2)) 
                                : $qm['message'];
                        ?>
                        <tr data-id="<?php echo $qm['id']; ?>" data-message="<?php echo htmlspecialchars($rawMessage); ?>">
                            <td><?php echo htmlspecialchars($displayMessage); ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary edit-quick-message">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-quick-message">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Schnellnachricht hinzufügen/bearbeiten -->
<div class="modal fade" id="editQuickMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalTitle"><?php echo t('add_quick_message'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickMessageForm">
                <div class="modal-body">
                    <input type="hidden" id="quickMessageId">
                    <div class="mb-3">
                        <label for="quickMessageText" class="form-label"><?php echo t('quick_message_text'); ?></label>
                        <input type="text" class="form-control" id="quickMessageText" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="saveQuickMessageBtn"><?php echo t('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Schnellnachricht löschen -->
<div class="modal fade" id="deleteQuickMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('delete_quick_message'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo t('confirm_delete_quick_message'); ?></p>
                <p id="deleteMessageText" class="fw-bold"></p>
                <input type="hidden" id="deleteMessageId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><?php echo t('delete_quick_message'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Übersetzungen für JavaScript
    const translations = {
        no_messages: '<?php echo t("no_messages"); ?>',
        add_quick_message: '<?php echo t("add_quick_message"); ?>',
        edit_quick_message: '<?php echo t("edit_quick_message"); ?>'
    };
</script>
