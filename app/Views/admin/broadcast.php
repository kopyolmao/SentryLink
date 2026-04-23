<?php shell_start('SentryLink | Broadcast', $user, 'admin', 'broadcast', 'Notification Broadcast', 'Send notices to all users or filtered groups.'); ?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if (($error ?? '') !== ''): ?><div class="alert alert-danger"><?= h((string) $error) ?></div><?php endif; ?>
<div class="panel">
    <form method="POST" class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="broadcast_role">Role</label>
            <select class="form-select" id="broadcast_role" name="role">
                <option value="">All roles</option>
                <?php foreach (($allowedRoles ?? []) as $roleItem): ?>
                    <option value="<?= h((string) $roleItem) ?>" <?= (($roleFilter ?? '') === $roleItem) ? 'selected' : '' ?>><?= h(shell_role_label((string) $roleItem)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-9" id="studentBroadcastHint" hidden>
            <label class="form-label d-block">&nbsp;</label>
            <div class="text-secondary small">Student filters are available only when role is set to Student.</div>
        </div>
        <div class="col-12" id="studentBroadcastFilters" hidden>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="broadcast_course">Course</label>
                    <select class="form-select" id="broadcast_course" name="course">
                        <option value="">All courses</option>
                        <?php foreach (($allowedCourses ?? []) as $course): ?>
                            <option value="<?= h((string) $course) ?>" <?= (($courseFilter ?? '') === $course) ? 'selected' : '' ?>><?= h((string) $course) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="broadcast_year_level">Year Level</label>
                    <select class="form-select" id="broadcast_year_level" name="year_level">
                        <option value="">All year levels</option>
                        <?php foreach (($allowedYearLevels ?? []) as $yearLevel): ?>
                            <option value="<?= h((string) $yearLevel) ?>" <?= (($yearFilter ?? '') === $yearLevel) ? 'selected' : '' ?>><?= h((string) $yearLevel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="broadcast_house">House</label>
                    <select class="form-select" id="broadcast_house" name="house">
                        <option value="">All houses</option>
                        <?php foreach (($allowedHouses ?? []) as $house): ?>
                            <option value="<?= h((string) $house) ?>" <?= (($houseFilter ?? '') === $house) ? 'selected' : '' ?>><?= h((string) $house) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-12"><label class="form-label" for="broadcast_title">Title</label><input id="broadcast_title" class="form-control" name="title" value="<?= h((string) ($title ?? '')) ?>" required></div>
        <div class="col-12"><label class="form-label" for="broadcast_message">Message</label><textarea id="broadcast_message" class="form-control" name="message" rows="4" required><?= h((string) ($body ?? '')) ?></textarea></div>
        <div class="col-12"><button class="btn btn-primary">Send Broadcast</button></div>
    </form>
</div>
<?php
$script = <<<'HTML'
<script>
const broadcastRoleSelect = document.getElementById("broadcast_role");
const studentBroadcastFilters = document.getElementById("studentBroadcastFilters");
const studentBroadcastHint = document.getElementById("studentBroadcastHint");
const studentOnlyFilterInputs = studentBroadcastFilters
    ? studentBroadcastFilters.querySelectorAll("select[name='course'], select[name='year_level'], select[name='house']")
    : [];

function syncBroadcastStudentFilters() {
    if (!broadcastRoleSelect || !studentBroadcastFilters || !studentBroadcastHint) {
        return;
    }

    const isStudentRole = String(broadcastRoleSelect.value || "").toLowerCase() === "student";
    studentBroadcastFilters.hidden = !isStudentRole;
    studentBroadcastHint.hidden = isStudentRole;

    studentOnlyFilterInputs.forEach((input) => {
        input.disabled = !isStudentRole;
    });
}

if (broadcastRoleSelect) {
    broadcastRoleSelect.addEventListener("change", syncBroadcastStudentFilters);
}
syncBroadcastStudentFilters();
</script>
HTML;

shell_end($script);
?>
