<?php shell_start('SentryLink | Events', $user, 'admin', 'events', 'Event Management', 'Create, update, and prepare event gates.'); ?>
<style>
.event-admin {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.event-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}

.event-field {
    min-width: 0;
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }
.span-3 { grid-column: span 3; }

.event-form textarea {
    min-height: 150px;
    resize: none;
    overflow-y: hidden;
}

.event-check {
    display: flex;
    align-items: center;
    height: 100%;
    min-height: 100%;
    padding-top: 2rem;
}

.event-check .form-check {
    margin: 0;
}

.event-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.event-actions .btn {
    min-width: 180px;
    justify-content: center;
}

.event-table-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.event-table-actions form {
    margin: 0;
}

.event-table-actions .btn {
    margin: 0;
}

.event-cancel-modal[hidden] {
    display: none !important;
}

.event-cancel-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.event-cancel-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(5, 8, 24, 0.76);
    backdrop-filter: blur(6px);
}

.event-cancel-dialog {
    position: relative;
    width: min(100%, 460px);
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 1.35rem;
}

.event-cancel-copy {
    color: var(--muted);
    margin-bottom: 1rem;
}

.event-cancel-card {
    margin-bottom: 1rem;
    padding: 0.95rem 1rem;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: rgba(255, 255, 255, 0.04);
}

.event-cancel-card strong,
.event-cancel-card small {
    display: block;
}

.event-cancel-card small {
    color: var(--muted);
}

.event-cancel-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.75rem;
}

@media (max-width: 1100px) {
    .span-6,
    .span-4,
    .span-3 {
        grid-column: span 6;
    }
}

@media (max-width: 700px) {
    .event-form {
        grid-template-columns: 1fr;
    }

    .span-12,
    .span-6,
    .span-4,
    .span-3 {
        grid-column: auto;
    }

    .event-check {
        padding-top: 0.25rem;
    }

    .event-actions .btn,
    .event-table-actions .btn {
        width: 100%;
    }

    .event-cancel-actions {
        flex-direction: column;
    }

    .event-cancel-actions .btn {
        width: 100%;
    }

    .event-table-actions {
        flex-direction: column;
    }
}
</style>
<div class="event-admin">
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-3"><?= $editEvent ? 'Edit Event' : 'Create Event' ?></h3>
    <?php $isFreeChecked = (int) ($editEvent['is_free'] ?? 0) === 1; ?>
    <form method="POST" class="event-form">
        <input type="hidden" name="event_id" value="<?= h($editEvent['id'] ?? '') ?>">

        <div class="event-field span-6">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="<?= h($editEvent['title'] ?? '') ?>" required>
        </div>

        <div class="event-field span-6">
            <label class="form-label">Venue</label>
            <input class="form-control" name="venue" value="<?= h($editEvent['venue'] ?? '') ?>" required>
        </div>

        <div class="event-field span-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"><?= h($editEvent['description'] ?? '') ?></textarea>
        </div>

        <div class="event-field span-3">
            <label class="form-label">Event Date</label>
            <input type="date" class="form-control" name="event_date" value="<?= h($editEvent['event_date'] ?? '') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" value="<?= h($editEvent['start_time'] ?? '08:00') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" value="<?= h($editEvent['end_time'] ?? '17:00') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <?php foreach (['draft', 'open', 'ongoing', 'closed', 'cancelled'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($editEvent['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="event-field span-3" id="ticketPriceField" <?= $isFreeChecked ? 'hidden' : '' ?>>
            <label class="form-label">Ticket Price</label>
            <input type="number" step="0.01" min="0" class="form-control" id="ticketPriceInput" name="ticket_price" value="<?= h((string) ($editEvent['ticket_price'] ?? '')) ?>">
        </div>

        <div class="event-field span-3">
            <label class="form-label">Max Capacity</label>
            <input type="number" min="0" class="form-control" name="max_capacity" value="<?= h((string) ($editEvent['max_capacity'] ?? '')) ?>">
        </div>

        <div class="event-field span-6 event-check">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_free" id="is_free" <?= $isFreeChecked ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_free">Free Event</label>
            </div>
        </div>

        <div class="event-field span-12">
            <div class="event-actions">
                <button class="btn btn-primary" name="save_event" value="1"><?= $editEvent ? 'Save Event' : 'Create Event' ?></button>
                <?php if ($editEvent): ?>
                    <a class="btn btn-outline-light" href="<?= h(app_url('admin/events')) ?>">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Tickets</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><strong><?= h($event['title']) ?></strong><div class="text-secondary"><?= h($event['venue']) ?></div></td>
                    <td><?= h($event['event_date']) ?><br><small class="text-secondary"><?= h(substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5)) ?></small></td>
                    <td><span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span></td>
                    <td><?= h((string) $event['ticket_count']) ?></td>
                    <td>
                        <div class="event-table-actions">
                            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('admin/events') . '?id=' . $event['id']) ?>">Edit</a>
                            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('admin/events/' . $event['id'] . '/activities')) ?>">Activities</a>
                            <form method="POST"><input type="hidden" name="event_id" value="<?= $event['id'] ?>"><button class="btn btn-primary btn-sm" name="prepare_gate" value="1">Start Event & Prepare Gate</button></form>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm js-cancel-event"
                                data-event-id="<?= h((string) $event['id']) ?>"
                                data-event-title="<?= h((string) $event['title']) ?>"
                            >
                                Cancel
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
</div>
</div>
<div class="event-cancel-modal" id="eventCancelModal" hidden>
    <div class="event-cancel-backdrop" data-close-event-cancel></div>
    <div class="event-cancel-dialog" role="dialog" aria-modal="true" aria-labelledby="eventCancelTitle" aria-describedby="eventCancelDescription">
        <h3 class="h5 mb-2" id="eventCancelTitle">Cancel event?</h3>
        <p class="event-cancel-copy" id="eventCancelDescription">This will mark the event as cancelled and remove it from active operations.</p>
        <div class="event-cancel-card">
            <strong id="eventCancelName"></strong>
            <small>Status will be set to cancelled.</small>
        </div>
        <form method="POST">
            <input type="hidden" name="event_id" id="event_cancel_target_id">
            <div class="event-cancel-actions">
                <button type="button" class="btn btn-outline-light" id="eventCancelKeep" data-close-event-cancel>Keep Event</button>
                <button type="submit" class="btn btn-danger" name="soft_delete_event" value="1">Cancel Event</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelectorAll(".event-form textarea").forEach((textarea) => {
    const resizeTextarea = () => {
        textarea.style.height = "auto";
        textarea.style.height = `${textarea.scrollHeight}px`;
    };

    resizeTextarea();
    textarea.addEventListener("input", resizeTextarea);
});

const freeEventCheckbox = document.getElementById("is_free");
const ticketPriceField = document.getElementById("ticketPriceField");
const ticketPriceInput = document.getElementById("ticketPriceInput");

if (freeEventCheckbox && ticketPriceField) {
    const syncTicketPriceVisibility = () => {
        const isFree = freeEventCheckbox.checked;
        ticketPriceField.hidden = isFree;

        if (ticketPriceInput) {
            if (isFree) {
                ticketPriceInput.value = "";
                ticketPriceInput.disabled = true;
            } else {
                ticketPriceInput.disabled = false;
            }
        }
    };

    freeEventCheckbox.addEventListener("change", syncTicketPriceVisibility);
    syncTicketPriceVisibility();
}

const eventCancelModal = document.getElementById("eventCancelModal");
const eventCancelName = document.getElementById("eventCancelName");
const eventCancelTargetId = document.getElementById("event_cancel_target_id");
const eventCancelKeep = document.getElementById("eventCancelKeep");
const eventCancelButtons = document.querySelectorAll(".js-cancel-event");
const eventCancelCloseButtons = document.querySelectorAll("[data-close-event-cancel]");

function openEventCancelModal(eventId, eventTitle) {
    if (!eventCancelModal || !eventCancelTargetId) {
        return;
    }

    eventCancelTargetId.value = eventId;
    if (eventCancelName) {
        eventCancelName.textContent = eventTitle || "Selected event";
    }

    eventCancelModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeEventCancelModal() {
    if (!eventCancelModal || eventCancelModal.hidden) {
        return;
    }

    eventCancelModal.hidden = true;
    document.body.style.overflow = "";
}

eventCancelButtons.forEach((button) => {
    button.addEventListener("click", () => {
        openEventCancelModal(
            button.getAttribute("data-event-id") || "",
            button.getAttribute("data-event-title") || ""
        );
    });
});

eventCancelCloseButtons.forEach((button) => {
    button.addEventListener("click", closeEventCancelModal);
});

if (eventCancelKeep) {
    eventCancelKeep.addEventListener("click", closeEventCancelModal);
}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeEventCancelModal();
    }
});
</script>
</div>
<?php shell_end(); ?>
