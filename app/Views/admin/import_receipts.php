<?php shell_start('SentryLink | Receipt Import', $user, 'admin', 'import', 'CSV Receipt Import', 'Import cashier receipt data and turn it into tickets.'); ?>
<style>
.import-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.import-upload {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
    gap: 1.2rem;
    align-items: stretch;
}

.import-upload::after {
    content: "";
    position: absolute;
    right: -80px;
    top: -80px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(157, 78, 221, 0.22), transparent 68%);
    pointer-events: none;
}

.import-copy,
.import-form {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.import-kicker {
    display: inline-flex;
    align-items: center;
    padding: 0.42rem 0.8rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    color: #d9c3ff;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.import-copy h3 {
    margin: 0.95rem 0 0.45rem;
    font-size: clamp(2rem, 3vw, 2.7rem);
    line-height: 1.02;
    letter-spacing: -0.04em;
}

.import-copy p {
    max-width: 620px;
    color: var(--muted);
    margin-bottom: 1.1rem;
}

.import-points {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.import-point {
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.035);
    padding: 0.95rem 1rem;
    min-width: 0;
}

.import-point strong {
    display: block;
    margin-bottom: 0.25rem;
}

.import-form-shell {
    height: 100%;
    border-radius: 26px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.035);
    padding: 1.15rem;
}

.import-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.upload-field {
    min-width: 0;
}

.file-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    width: 100%;
    padding: 0.55rem 0.7rem 0.55rem 1rem;
    border-radius: 18px;
    border: 1px solid rgba(148, 142, 162, 0.26);
    background: rgba(8, 10, 44, 0.7);
    cursor: pointer;
}

.file-trigger strong {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.72rem 1rem;
    border-radius: 14px;
    background: rgba(255,255,255,0.9);
    color: #171231;
    font-size: 0.92rem;
}

.file-name {
    min-width: 0;
    flex: 1 1 auto;
    color: var(--muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: left;
}

.file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

.import-submit {
    width: 100%;
    justify-content: center;
}

.expected-columns {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.expected-chip {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    max-width: 100%;
    padding: 0.7rem 1rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
}

@media (max-width: 1100px) {
    .import-upload {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .import-points {
        grid-template-columns: 1fr;
    }

    .file-trigger {
        flex-direction: column;
        align-items: stretch;
    }

    .file-trigger strong {
        width: 100%;
    }
}
</style>

<div class="import-layout">
    <?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

    <div class="panel import-upload">
        <div class="import-copy">
            <span class="import-kicker">Receipt Import</span>
            <h3>Upload and validate cashier receipts.</h3>
            <p>Select the target event, attach the CSV file, and let SentryLink validate the rows before tickets are created.</p>

            <div class="import-points">
                <div class="import-point">
                    <strong>Expected Columns</strong>
                    <span class="text-secondary">Use only the required values so validation stays clean.</span>
                </div>
                <div class="import-point">
                    <strong>Linked to Event</strong>
                    <span class="text-secondary">The selected event on this screen determines where the receipts will be applied.</span>
                </div>
            </div>
        </div>

        <div class="import-form">
            <div class="import-form-shell">
                <form method="POST" enctype="multipart/form-data" class="import-form-grid">
                    <div class="upload-field">
                        <label class="form-label">Event</label>
                        <select class="form-select" name="event_id" required>
                            <option value="">Select event</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= $event['id'] ?>"><?= h($event['title']) ?> (<?= h($event['event_date']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="upload-field">
                        <label class="form-label" for="csv_file">CSV File</label>
                        <label class="file-trigger" for="csv_file">
                            <strong>Choose File</strong>
                            <span class="file-name" id="csvFileName">No file selected</span>
                        </label>
                        <input type="file" class="file-input" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>

                    <button class="btn btn-primary import-submit" name="import" value="1">Validate Import</button>
                </form>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 class="h5 mb-3">Expected CSV Columns</h3>
        <div class="expected-columns">
            <span class="expected-chip"><code>receipt_id,student_no</code></span>
            <p class="text-secondary mb-0">Event selection happens on this screen, so the file only needs receipt ID and student number.</p>
        </div>
    </div>
</div>

<script>
const csvInput = document.getElementById("csv_file");
const csvFileName = document.getElementById("csvFileName");

if (csvInput && csvFileName) {
    csvInput.addEventListener("change", () => {
        csvFileName.textContent = csvInput.files && csvInput.files.length > 0
            ? csvInput.files[0].name
            : "No file selected";
    });
}
</script>
<?php shell_end(); ?>
