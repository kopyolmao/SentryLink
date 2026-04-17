<?php shell_start('SentryLink | Students', $user, 'admin', 'students', 'Student Management', 'Add or review student accounts.'); ?>
<?php
$courses = ['BSIT', 'BSCS', 'BSBA', 'BSHM', 'BSA', 'BSIS', 'Other'];
$yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$houses = ['Azul', 'Cahel', 'Giallio', 'Roxxo', 'Vierrdy'];
$selectedCourse = old('course_select');
?>
<style>
.student-create-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
}

.student-create-grid > div {
    min-width: 0;
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }
.span-3 { grid-column: span 3; }

.other-course-field[hidden] {
    display: none !important;
}

.student-table-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.student-table-actions .btn {
    margin: 0;
}

.student-delete-modal[hidden] {
    display: none !important;
}

.student-delete-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.student-delete-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(5, 8, 24, 0.76);
    backdrop-filter: blur(6px);
}

.student-delete-dialog {
    position: relative;
    width: min(100%, 460px);
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 1.35rem;
    box-shadow: var(--shadow);
}

.student-delete-copy {
    color: var(--muted);
    margin-bottom: 1rem;
}

.student-delete-card {
    margin-bottom: 1rem;
    padding: 0.95rem 1rem;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: rgba(255, 255, 255, 0.04);
}

.student-delete-card strong,
.student-delete-card small {
    display: block;
}

.student-delete-card small {
    color: var(--muted);
}

.student-delete-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.75rem;
}

@media (max-width: 1000px) {
    .span-6,
    .span-4,
    .span-3 {
        grid-column: span 6;
    }
}

@media (max-width: 640px) {
    .student-create-grid {
        grid-template-columns: 1fr;
    }

    .span-12,
    .span-6,
    .span-4,
    .span-3 {
        grid-column: auto;
    }

    .student-table-actions,
    .student-delete-actions {
        flex-direction: column;
    }

    .student-table-actions .btn,
    .student-delete-actions .btn {
        width: 100%;
    }
}
</style>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-3">Create Student Account</h3>
    <form method="POST" class="student-create-grid">
        <div class="span-3">
            <input class="form-control" name="student_id" placeholder="Student ID" value="<?= h(old('student_id')) ?>" required>
        </div>
        <div class="span-3">
            <input class="form-control" name="first_name" placeholder="First Name" value="<?= h(old('first_name')) ?>" required>
        </div>
        <div class="span-3">
            <input class="form-control" name="last_name" placeholder="Last Name" value="<?= h(old('last_name')) ?>" required>
        </div>
        <div class="span-3">
            <input class="form-control" name="email" placeholder="Email" value="<?= h(old('email')) ?>" required>
        </div>

        <div class="span-4">
            <label class="form-label">Course</label>
            <select class="form-select" name="course_select" id="course_select" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= h($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="span-4 other-course-field" id="other_course_field"<?= $selectedCourse === 'Other' ? '' : ' hidden' ?>>
            <label class="form-label">Other Course</label>
            <input class="form-control" name="course_other" id="course_other" placeholder="Enter course name" value="<?= h(old('course_other')) ?>">
        </div>

        <div class="span-4">
            <label class="form-label">Year Level</label>
            <select class="form-select" name="year_level" required>
                <option value="">Select Year Level</option>
                <?php foreach ($yearLevels as $year): ?>
                    <option value="<?= h($year) ?>" <?= old('year_level') === $year ? 'selected' : '' ?>><?= h($year) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="span-4">
            <label class="form-label">House</label>
            <select class="form-select" name="house" required>
                <option value="">Select House</option>
                <?php foreach ($houses as $house): ?>
                    <option value="<?= h($house) ?>" <?= old('house') === $house ? 'selected' : '' ?>><?= h($house) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="span-4">
            <label class="form-label">Temporary Password</label>
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="password" value="<?= h(old('password') !== '' ? old('password') : 'Password123!') ?>" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>

        <div class="span-12">
            <button class="btn btn-primary" name="create_student" value="1">Create Student</button>
        </div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Course / Year</th><th>Email</th><th>House</th><th>Email Verified</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($students === []): ?>
                <tr>
                    <td colspan="6" class="text-secondary">No student accounts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <?php $fullName = trim($student['first_name'] . ' ' . $student['last_name']); ?>
                    <tr>
                        <td><?= h($student['last_name'] . ', ' . $student['first_name']) ?><br><small class="text-secondary"><?= h($student['student_id']) ?></small></td>
                        <td><?= h(($student['course'] ?: '-') . ' / ' . ($student['year_level'] ?: '-')) ?></td>
                        <td><?= h($student['email']) ?></td>
                        <td><?= h($student['house']) ?></td>
                        <td><span class="badge text-bg-<?= (int) $student['email_verified'] === 1 ? 'success' : 'warning' ?>"><?= (int) $student['email_verified'] === 1 ? 'Verified' : 'Pending' ?></span></td>
                        <td>
                            <div class="student-table-actions">
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm js-delete-student"
                                    data-target-id="<?= h((string) $student['id']) ?>"
                                    data-student-name="<?= h($fullName) ?>"
                                    data-student-number="<?= h($student['student_id']) ?>"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="student-delete-modal" id="studentDeleteModal" hidden>
    <div class="student-delete-backdrop" data-close-student-delete></div>
    <div class="student-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="studentDeleteTitle" aria-describedby="studentDeleteDescription">
        <h3 class="h5 mb-2" id="studentDeleteTitle">Delete student account?</h3>
        <p class="student-delete-copy" id="studentDeleteDescription">This will archive the account, immediately sign the student out, and remove the record from the active students list.</p>
        <div class="student-delete-card">
            <strong id="studentDeleteName"></strong>
            <small id="studentDeleteMeta"></small>
        </div>
        <form method="POST">
            <input type="hidden" name="target_id" id="student_delete_target_id">
            <div class="student-delete-actions">
                <button type="button" class="btn btn-outline-light" id="studentDeleteCancel" data-close-student-delete>Keep Student</button>
                <button type="submit" class="btn btn-danger" name="delete_student" value="1">Delete Student</button>
            </div>
        </form>
    </div>
</div>
<script>
const courseSelect = document.getElementById("course_select");
const otherCourseField = document.getElementById("other_course_field");
const otherCourseInput = document.getElementById("course_other");
const studentDeleteModal = document.getElementById("studentDeleteModal");
const studentDeleteName = document.getElementById("studentDeleteName");
const studentDeleteMeta = document.getElementById("studentDeleteMeta");
const studentDeleteTargetId = document.getElementById("student_delete_target_id");
const studentDeleteCancel = document.getElementById("studentDeleteCancel");
const deleteButtons = document.querySelectorAll(".js-delete-student");
const modalCloseButtons = document.querySelectorAll("[data-close-student-delete]");
const passwordToggles = document.querySelectorAll(".js-password-toggle");

function syncCourseField() {
    if (!courseSelect || !otherCourseField || !otherCourseInput) {
        return;
    }

    if (courseSelect.value === "Other") {
        otherCourseField.hidden = false;
        otherCourseInput.required = true;
    } else {
        otherCourseField.hidden = true;
        otherCourseInput.required = false;
        otherCourseInput.value = "";
    }
}

function openDeleteModal(targetId, studentName, studentNumber) {
    if (!studentDeleteModal || !studentDeleteTargetId) {
        return;
    }

    studentDeleteTargetId.value = targetId;
    if (studentDeleteName) {
        studentDeleteName.textContent = studentName || "Selected student";
    }
    if (studentDeleteMeta) {
        studentDeleteMeta.textContent = studentNumber || "";
    }

    studentDeleteModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeDeleteModal() {
    if (!studentDeleteModal || studentDeleteModal.hidden) {
        return;
    }

    studentDeleteModal.hidden = true;
    document.body.style.overflow = "";
}

function setupPasswordToggle(button) {
    const input = button.closest(".password-field")?.querySelector(".js-password-input");
    if (!input) {
        return;
    }

    button.addEventListener("click", () => {
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", show ? "true" : "false");
        button.setAttribute("aria-label", show ? "Hide password" : "Show password");
        const icon = button.querySelector("span");
        if (icon) {
            icon.textContent = show ? "Hide" : "Show";
        }
    });
}

if (courseSelect) {
    courseSelect.addEventListener("change", syncCourseField);
    syncCourseField();
}

deleteButtons.forEach((button) => {
    button.addEventListener("click", () => {
        openDeleteModal(
            button.getAttribute("data-target-id") || "",
            button.getAttribute("data-student-name") || "",
            button.getAttribute("data-student-number") || ""
        );
    });
});

modalCloseButtons.forEach((button) => {
    button.addEventListener("click", closeDeleteModal);
});

if (studentDeleteCancel) {
    studentDeleteCancel.addEventListener("click", closeDeleteModal);
}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeDeleteModal();
    }
});

passwordToggles.forEach(setupPasswordToggle);
</script>
<?php shell_end(); ?>
