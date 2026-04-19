<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Email Setup</title>
<script>document.documentElement.classList.add('page-loading');</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<style>
:root {
    --accent: #5b19e6;
    --background: #0d0f31;
    --surface-low: rgba(22, 24, 58, 0.78);
    --surface-lowest: rgba(8, 10, 44, 0.78);
    --border: rgba(202, 195, 217, 0.12);
    --text: #e0e0ff;
    --muted: #cac3d9;
}
.page-load-skeleton {
    position: fixed;
    inset: 0;
    z-index: 70;
    display: grid;
    place-items: center;
    background: #0d0f31;
    opacity: 0;
    visibility: hidden;
    transition: opacity 220ms ease, visibility 220ms ease;
}
.page-load-skeleton-card {
    width: min(90vw, 32rem);
    border-radius: 1.5rem;
    border: 1px solid rgba(202, 195, 217, 0.14);
    background: rgba(22, 24, 58, 0.9);
    padding: 1rem;
}
.page-load-skeleton-line {
    height: 11px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(224,224,255,0.08), rgba(224,224,255,0.24), rgba(224,224,255,0.08));
    background-size: 200% 100%;
    animation: loadSweep 1.2s linear infinite;
}
.page-load-skeleton-line + .page-load-skeleton-line {
    margin-top: 0.65rem;
}
.page-load-skeleton-line.w-80 { width: 80%; }
.page-load-skeleton-line.w-60 { width: 60%; }
.page-load-skeleton-line.w-40 { width: 40%; }
@keyframes loadSweep {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
html.page-loading .page-load-skeleton {
    opacity: 1;
    visibility: visible;
}
html.page-loading .auth-wrap {
    visibility: hidden;
}
html, body {
    height: 100%;
}
body {
    margin: 0;
    min-height: 100vh;
    min-height: 100dvh;
    overflow: hidden;
    display: grid;
    place-items: center;
    padding: 1rem;
    font-family: "Manrope", sans-serif;
    color: var(--text);
    background:
        radial-gradient(circle at top right, rgba(91, 25, 230, 0.18), transparent 24%),
        radial-gradient(circle at bottom left, rgba(56, 122, 255, 0.14), transparent 22%),
        linear-gradient(180deg, #090b26 0%, var(--background) 100%);
}
body::before,
body::after {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
}
body::before {
    background: radial-gradient(circle at center, rgba(91, 25, 230, 0.14) 0%, transparent 70%);
}
body::after {
    background-size: 24px 24px;
    background-image:
        linear-gradient(to right, rgba(73, 68, 86, 0.09) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(73, 68, 86, 0.09) 1px, transparent 1px);
}
.auth-wrap {
    position: relative;
    z-index: 1;
    width: min(100%, 32rem);
}
.auth-card {
    border-radius: 2rem;
    border: 1px solid var(--border);
    background: var(--surface-low);
    backdrop-filter: blur(24px);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
    padding: 1.5rem;
}
.brand {
    font-family: "Newsreader", serif;
    font-size: 2rem;
    font-style: italic;
    letter-spacing: -0.04em;
    margin: 0 0 1.25rem;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 1rem;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    color: #ccbdff;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
h1 {
    margin: 0;
    font-family: "Newsreader", serif;
    font-size: clamp(2rem, 6vw, 2.7rem);
    line-height: 1.04;
    letter-spacing: -0.04em;
}
.copy {
    margin: 0.75rem 0 1.25rem;
    color: var(--muted);
    line-height: 1.6;
}
.alert {
    border-radius: 1.25rem;
    padding: 0.95rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
}
.alert-danger {
    background: rgba(147, 0, 10, 0.42);
    color: #ffdad6;
}
.alert-success {
    background: rgba(34, 197, 94, 0.16);
    color: #b7ffd7;
}
form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
label {
    display: block;
    margin: 0 0 0.45rem 0.1rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--muted);
}
input {
    box-sizing: border-box;
    width: 100%;
    border-radius: 999px;
    border: 1px solid rgba(148, 142, 162, 0.26);
    background: var(--surface-lowest);
    color: var(--text);
    padding: 0.95rem 1rem;
    font: inherit;
}
input:focus {
    outline: none;
    border-color: rgba(204, 189, 255, 0.7);
    box-shadow: 0 0 0 1px rgba(204, 189, 255, 0.55);
}
button {
    border: 0;
    border-radius: 999px;
    padding: 0.95rem 1rem;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    color: white;
    background: linear-gradient(135deg, var(--accent), #ccbdff);
    box-shadow: 0 12px 28px rgba(91, 25, 230, 0.24);
}
.helper {
    margin-top: 1rem;
    color: var(--muted);
    font-size: 0.92rem;
    line-height: 1.5;
}
</style>
</head>
<body>
<div class="page-load-skeleton" id="pageLoadSkeleton" aria-hidden="true">
    <div class="page-load-skeleton-card">
        <div class="page-load-skeleton-line w-40"></div>
        <div class="page-load-skeleton-line w-80"></div>
        <div class="page-load-skeleton-line w-60"></div>
        <div class="page-load-skeleton-line w-80"></div>
    </div>
</div>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand">SentryLink</div>
        <div class="chip">First Login Setup</div>
        <h1>Verify your email.</h1>
        <p class="copy">Students must bind and confirm a reachable email address before entering the dashboard. This email will also be used for password recovery.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if (! session()->has('verification_code')): ?>
            <form method="POST">
                <div>
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" required>
                </div>
                <button type="submit" name="send_code" value="1">Send Verification Code</button>
            </form>
            <div class="helper">Use an email address you can access immediately. The verification code expires after ten minutes.</div>
        <?php else: ?>
            <form method="POST">
                <div>
                    <label for="code">Verification Code</label>
                    <input id="code" type="text" name="code" maxlength="6" required>
                </div>
                <button type="submit" name="verify_code" value="1">Verify and Continue</button>
            </form>
            <div class="helper">Enter the six-digit code sent to your email, then continue into the student dashboard.</div>
        <?php endif; ?>
    </div>
</div>
<script>
(() => {
    const finishInitialLoader = () => {
        document.documentElement.classList.remove("page-loading");
        const loader = document.getElementById("pageLoadSkeleton");
        if (loader) {
            loader.setAttribute("aria-hidden", "true");
            setTimeout(() => loader.remove(), 260);
        }
    };

    if (document.readyState === "complete") {
        finishInitialLoader();
    } else {
        window.addEventListener("load", finishInitialLoader, { once: true });
        setTimeout(finishInitialLoader, 4500);
    }
})();
</script>
</body>
</html>
