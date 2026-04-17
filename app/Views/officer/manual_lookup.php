<?php shell_start('SentryLink | Manual Lookup', $user, 'ssg', 'lookup', 'Manual Lookup', 'Search a student when camera scanning fails.'); ?>
<style>
.lookup-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(170px, 0.42fr);
    gap: 1rem;
    align-items: end;
}

.lookup-field {
    min-width: 0;
}

.lookup-field .form-control,
.lookup-field .form-select,
.lookup-submit {
    min-height: 54px;
}

.lookup-submit {
    width: 100%;
    justify-content: center;
    border-radius: 18px;
    white-space: nowrap;
}

@media (max-width: 900px) {
    .lookup-form {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="panel">
    <form method="GET" class="lookup-form">
        <div class="lookup-field">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id">
                <option value="">Select event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventId === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lookup-field">
            <label class="form-label">Student ID</label>
            <input class="form-control" name="student_id" value="<?= h($studentId) ?>" placeholder="STU001">
        </div>
        <div class="lookup-field">
            <button class="btn btn-primary lookup-submit">Search</button>
        </div>
    </form>
</div>
<div class="panel">
    <?php if ($result): ?>
        <h3 class="h5 mb-3"><?= h($result['first_name'] . ' ' . $result['last_name']) ?></h3>
        <p class="mb-1"><strong>Student ID:</strong> <?= h($result['student_id']) ?></p>
        <p class="mb-1"><strong>Course / Year:</strong> <?= h(($result['course'] ?: '-') . ' / ' . ($result['year_level'] ?: '-')) ?></p>
        <p class="mb-1"><strong>Ticket Status:</strong> <span class="badge text-bg-<?= h(ticket_status_badge($result['payment_status'])[1]) ?>"><?= h(ticket_status_badge($result['payment_status'])[0]) ?></span></p>
        <p class="mb-2"><strong>Admission State:</strong> <?= h((string) ($result['gate_state']['current_label'] ?? 'Ready for Entry')) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <button
                id="manualToggleBtn"
                type="button"
                class="btn btn-primary"
                data-event-id="<?= h((string) $eventId) ?>"
                data-student-id="<?= h($result['student_id']) ?>"
            >
                Record <?= h((string) ($result['gate_state']['next_action_label'] ?? 'In')) ?>
            </button>
            <span id="manualToggleStatus" class="text-secondary align-self-center"></span>
        </div>
    <?php elseif ($eventId > 0 && $studentId !== ''): ?>
        <p class="text-secondary mb-0">No matching ticket record was found for that student in the selected event.</p>
    <?php else: ?>
        <p class="text-secondary mb-0">Choose an event and enter a student ID to start a manual lookup.</p>
    <?php endif; ?>
</div>
<?php
$script = '';
if ($result) {
    $script = '<script>
const manualToggleBtn = document.getElementById("manualToggleBtn");
const manualToggleStatus = document.getElementById("manualToggleStatus");

if (manualToggleBtn) {
    manualToggleBtn.addEventListener("click", async () => {
        const eventId = Number(manualToggleBtn.getAttribute("data-event-id") || 0);
        const studentId = manualToggleBtn.getAttribute("data-student-id") || "";
        if (!eventId || !studentId) {
            return;
        }

        manualToggleBtn.disabled = true;
        manualToggleStatus.textContent = "Updating gate activity...";

        try {
            const response = await fetch(' . json_encode(app_url('api/gate-activity-state')) . ', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ event_id: eventId, student_id: studentId, gate_location: "Manual Lookup" }),
            });
            const data = await response.json();

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || "Manual gate update failed.");
            }

            manualToggleStatus.textContent = data.message || "Updated.";
            setTimeout(() => window.location.reload(), 750);
        } catch (error) {
            manualToggleStatus.textContent = error.message;
            manualToggleBtn.disabled = false;
        }
    });
}
</script>';
}
shell_end($script);
?>
