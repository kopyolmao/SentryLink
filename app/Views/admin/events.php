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

.event-label {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.field-help {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1;
    cursor: help;
    padding: 0;
}

.field-help[data-tooltip-active="true"] {
    border-color: rgba(140, 182, 255, 0.95);
    background: rgba(91, 139, 255, 0.32);
}

.field-help-tooltip {
    position: fixed;
    z-index: 1400;
    max-width: min(320px, calc(100vw - 24px));
    background: rgba(12, 16, 40, 0.96);
    color: #f6f8ff;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 12px;
    padding: 0.5rem 0.6rem;
    font-size: 0.76rem;
    line-height: 1.4;
    text-align: left;
    white-space: normal;
    pointer-events: none;
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.16s ease, transform 0.16s ease;
}

.field-help-tooltip[data-visible="true"] {
    opacity: 1;
    transform: translateY(0);
}

.field-note {
    margin: 0.4rem 0 0;
    color: var(--muted);
    font-size: 0.82rem;
    line-height: 1.35;
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
            <label class="form-label event-label" for="event_title">
                Title
                <button type="button" class="field-help" data-help="Use a short and specific event name so students can identify it quickly." aria-label="Title help">?</button>
            </label>
            <input id="event_title" class="form-control" name="title" value="<?= h($editEvent['title'] ?? '') ?>" required>
        </div>

        <div class="event-field span-6">
            <label class="form-label event-label" for="event_venue">
                Venue
                <button type="button" class="field-help" data-help="Enter the exact location where scanning or attendance will happen." aria-label="Venue help">?</button>
            </label>
            <input id="event_venue" class="form-control" name="venue" value="<?= h($editEvent['venue'] ?? '') ?>" required>
        </div>

        <div class="event-field span-12">
            <label class="form-label event-label" for="event_description">
                Description
                <button type="button" class="field-help" data-help="Optional details shown to users, such as reminders, attire, or required materials." aria-label="Description help">?</button>
            </label>
            <textarea id="event_description" class="form-control" name="description" rows="4"><?= h($editEvent['description'] ?? '') ?></textarea>
        </div>

        <div class="event-field span-3">
            <label class="form-label event-label" for="event_date">
                Event Date
                <button type="button" class="field-help" data-help="The calendar date when the event takes place." aria-label="Event date help">?</button>
            </label>
            <input id="event_date" type="date" class="form-control" name="event_date" value="<?= h($editEvent['event_date'] ?? '') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label event-label" for="event_start_time">
                Start Time
                <button type="button" class="field-help" data-help="Planned time the event starts. This helps staff coordinate gate operations." aria-label="Start time help">?</button>
            </label>
            <input id="event_start_time" type="time" class="form-control" name="start_time" value="<?= h($editEvent['start_time'] ?? '08:00') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label event-label" for="event_end_time">
                End Time
                <button type="button" class="field-help" data-help="When the event is expected to finish. The system auto closes events after this time." aria-label="End time help">?</button>
            </label>
            <input id="event_end_time" type="time" class="form-control" name="end_time" value="<?= h($editEvent['end_time'] ?? '17:00') ?>" required>
        </div>

        <div class="event-field span-3">
            <label class="form-label event-label" for="event_status">
                Status
                <button type="button" class="field-help" data-help="Draft: internal only. Open: can accept tickets before start. Ongoing: gate/scanning is active. Closed: ended normally. Cancelled: event will not proceed." aria-label="Status help">?</button>
            </label>
            <select id="event_status" class="form-select" name="status">
                <?php foreach (['draft', 'open', 'ongoing', 'closed', 'cancelled'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($editEvent['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="field-note" id="eventStatusHelpText" aria-live="polite"></p>
        </div>

        <div class="event-field span-3" id="ticketPriceField" <?= $isFreeChecked ? 'hidden' : '' ?>>
            <label class="form-label event-label" for="ticketPriceInput">
                Ticket Price
                <button type="button" class="field-help" data-help="Amount students pay per ticket. Hidden automatically when Free Event is enabled." aria-label="Ticket price help">?</button>
            </label>
            <input type="number" step="0.01" min="0" class="form-control" id="ticketPriceInput" name="ticket_price" value="<?= h((string) ($editEvent['ticket_price'] ?? '')) ?>">
        </div>

        <div class="event-field span-3">
            <label class="form-label event-label" for="event_max_capacity">
                Max Capacity
                <button type="button" class="field-help" data-help="Optional attendee limit. Leave blank if there is no fixed cap." aria-label="Max capacity help">?</button>
            </label>
            <input id="event_max_capacity" type="number" min="0" class="form-control" name="max_capacity" value="<?= h((string) ($editEvent['max_capacity'] ?? '')) ?>">
        </div>

        <div class="event-field span-6 event-check">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_free" id="is_free" <?= $isFreeChecked ? 'checked' : '' ?>>
                <label class="form-check-label event-label" for="is_free">
                    Free Event
                    <button type="button" class="field-help" data-help="When enabled, the system sets ticket price to zero and hides the ticket price input." aria-label="Free event help">?</button>
                </label>
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
                <?php
                $eventStatus = strtolower(trim((string) ($event['status'] ?? '')));
                $canPrepareGate = ! event_has_ended($event) && ! in_array($eventStatus, ['closed', 'cancelled'], true);
                ?>
                <tr>
                    <td><strong><?= h($event['title']) ?></strong><div class="text-secondary"><?= h($event['venue']) ?></div></td>
                    <td><?= h($event['event_date']) ?><br><small class="text-secondary"><?= h(substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5)) ?></small></td>
                    <td><span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span></td>
                    <td><?= h((string) $event['ticket_count']) ?></td>
                    <td>
                        <div class="event-table-actions">
                            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('admin/events') . '?id=' . $event['id']) ?>">Edit</a>
                            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('admin/events/' . $event['id'] . '/activities')) ?>">Activities</a>
                            <?php if ($canPrepareGate): ?>
                                <form method="POST"><input type="hidden" name="event_id" value="<?= h((string) $event['id']) ?>"><button class="btn btn-primary btn-sm" name="prepare_gate" value="1">Start Event & Prepare Gate</button></form>
                            <?php endif; ?>
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

const helpButtons = Array.from(document.querySelectorAll(".field-help"));
let activeHelpTooltip = null;
const tooltipViewportGap = 12;

const closeHelpTooltip = () => {
    if (!activeHelpTooltip) {
        return;
    }

    const { button, tooltip } = activeHelpTooltip;
    button.removeAttribute("data-tooltip-active");
    tooltip.remove();
    activeHelpTooltip = null;
};

const positionHelpTooltip = (button, tooltip) => {
    const buttonRect = button.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();

    let left = buttonRect.left + (buttonRect.width / 2) - (tooltipRect.width / 2);
    left = Math.max(tooltipViewportGap, Math.min(left, window.innerWidth - tooltipRect.width - tooltipViewportGap));

    let top = buttonRect.bottom + 10;
    if (top + tooltipRect.height > window.innerHeight - tooltipViewportGap) {
        top = buttonRect.top - tooltipRect.height - 10;
    }
    if (top < tooltipViewportGap) {
        top = Math.max(tooltipViewportGap, Math.min(top, window.innerHeight - tooltipRect.height - tooltipViewportGap));
    }

    tooltip.style.left = `${Math.round(left)}px`;
    tooltip.style.top = `${Math.round(top)}px`;
    tooltip.setAttribute("data-visible", "true");
};

const openHelpTooltip = (button) => {
    const helpText = (button.getAttribute("data-help") || "").trim();
    if (!helpText) {
        return;
    }

    closeHelpTooltip();

    const tooltip = document.createElement("div");
    tooltip.className = "field-help-tooltip";
    tooltip.setAttribute("role", "tooltip");
    tooltip.textContent = helpText;
    document.body.appendChild(tooltip);

    button.setAttribute("data-tooltip-active", "true");
    activeHelpTooltip = { button, tooltip };
    positionHelpTooltip(button, tooltip);
};

helpButtons.forEach((helpButton) => {
    helpButton.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
    });

    helpButton.addEventListener("mouseenter", () => {
        openHelpTooltip(helpButton);
    });
    helpButton.addEventListener("focus", () => {
        openHelpTooltip(helpButton);
    });
    helpButton.addEventListener("mouseleave", closeHelpTooltip);
    helpButton.addEventListener("blur", closeHelpTooltip);
});

window.addEventListener("scroll", () => {
    if (activeHelpTooltip) {
        positionHelpTooltip(activeHelpTooltip.button, activeHelpTooltip.tooltip);
    }
}, true);

window.addEventListener("resize", () => {
    if (activeHelpTooltip) {
        positionHelpTooltip(activeHelpTooltip.button, activeHelpTooltip.tooltip);
    }
});

const freeEventCheckbox = document.getElementById("is_free");
const ticketPriceField = document.getElementById("ticketPriceField");
const ticketPriceInput = document.getElementById("ticketPriceInput");
const eventStatusSelect = document.getElementById("event_status");
const eventStatusHelpText = document.getElementById("eventStatusHelpText");

if (eventStatusSelect && eventStatusHelpText) {
    const statusHelpMap = {
        draft: "Draft: event is still being prepared and should not be treated as active.",
        open: "Open: event is published and can accept tickets before the gate starts.",
        ongoing: "Ongoing: event is currently running and gate scanning should be active.",
        closed: "Closed: event already ended and no more gate operations should occur.",
        cancelled: "Cancelled: event was stopped and should not continue."
    };

    const syncStatusHelp = () => {
        const selectedStatus = (eventStatusSelect.value || "").toLowerCase().trim();
        eventStatusHelpText.textContent = statusHelpMap[selectedStatus] || "";
    };

    eventStatusSelect.addEventListener("change", syncStatusHelp);
    syncStatusHelp();
}

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
        closeHelpTooltip();
        closeEventCancelModal();
    }
});
</script>
</div>
<?php shell_end(); ?>
