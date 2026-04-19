<?php shell_start('SentryLink | Audit Logs', $user, 'admin', 'audit', 'Audit Logs', 'Read-only system actions for accountability.'); ?>
<style>
.audit-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.audit-clear-modal[hidden] {
    display: none !important;
}

.audit-clear-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.audit-clear-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(5, 8, 24, 0.76);
    backdrop-filter: blur(6px);
}

.audit-clear-dialog {
    position: relative;
    width: min(100%, 460px);
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 1.25rem;
}

.audit-clear-copy {
    color: var(--muted);
    margin-bottom: 1rem;
}

.audit-clear-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    flex-wrap: wrap;
}

@media (max-width: 700px) {
    .audit-clear-actions {
        flex-direction: column;
    }

    .audit-clear-actions .btn {
        width: 100%;
    }
}
</style>
<?php if (($message ?? '') !== ''): ?><div class="alert alert-success"><?= h((string) $message) ?></div><?php endif; ?>
<?php if (($error ?? '') !== ''): ?><div class="alert alert-danger"><?= h((string) $error) ?></div><?php endif; ?>
<div class="panel">
    <div class="audit-toolbar">
        <div class="text-secondary">Latest 200 records are shown.</div>
        <button type="button" class="btn btn-danger btn-sm" id="openAuditClearModal">Clear Audit Logs</button>
    </div>
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Action</th><th>User</th><th>Target</th><th>IP</th><th>Created</th></tr></thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr>
                    <td colspan="5" class="text-secondary">No audit logs found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h((string) $log['action']) ?></td>
                    <td><?= h(trim(((string) ($log['first_name'] ?? '')) . ' ' . ((string) ($log['last_name'] ?? 'System')))) ?></td>
                    <td><?= h(((string) ($log['target_type'] ?: '-')) . (!empty($log['target_id']) ? ' #' . (string) $log['target_id'] : '')) ?></td>
                    <td><?= h((string) ($log['ip_address'] ?? '-')) ?></td>
                    <td><?= h((string) $log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="audit-clear-modal" id="auditClearModal" hidden>
    <div class="audit-clear-backdrop" data-close-audit-clear></div>
    <div class="audit-clear-dialog" role="dialog" aria-modal="true" aria-labelledby="auditClearTitle" aria-describedby="auditClearDescription">
        <h3 class="h5 mb-2" id="auditClearTitle">Clear all audit logs?</h3>
        <p class="audit-clear-copy" id="auditClearDescription">This will permanently delete existing audit log entries. A single “AUDIT_LOGS_CLEARED” record will be created for accountability.</p>
        <form method="POST">
            <input type="hidden" name="clear_audit_logs" value="1">
            <div class="audit-clear-actions">
                <button type="button" class="btn btn-outline-light" data-close-audit-clear>Cancel</button>
                <button type="submit" class="btn btn-danger">Clear Logs</button>
            </div>
        </form>
    </div>
</div>
<script>
const auditClearModal = document.getElementById("auditClearModal");
const openAuditClearModalButton = document.getElementById("openAuditClearModal");
const auditClearCloseButtons = document.querySelectorAll("[data-close-audit-clear]");

function openAuditClearModal() {
    if (!auditClearModal) {
        return;
    }
    auditClearModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeAuditClearModal() {
    if (!auditClearModal || auditClearModal.hidden) {
        return;
    }
    auditClearModal.hidden = true;
    document.body.style.overflow = "";
}

if (openAuditClearModalButton) {
    openAuditClearModalButton.addEventListener("click", openAuditClearModal);
}

auditClearCloseButtons.forEach((button) => {
    button.addEventListener("click", closeAuditClearModal);
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeAuditClearModal();
    }
});
</script>
<?php shell_end(); ?>
