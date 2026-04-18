<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Forgot Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
:root {
    --accent: <?= h($roleConfig[$role]['accent']) ?>;
    --background: #1b0c2b;
    --surface-soft: rgba(66, 50, 83, 0.62);
    --surface-lowest: #160725;
    --border: rgba(145, 143, 161, 0.24);
    --text: #efdbff;
    --muted: #c7c4d8;
    --outline: #918fa1;
    --outline-variant: #464555;
    --success-bg: rgba(34, 197, 94, 0.16);
    --success-text: #b7ffd7;
    --danger-bg: rgba(147, 0, 10, 0.42);
    --danger-text: #ffdad6;
}
* {
    box-sizing: border-box;
}
html, body {
    min-height: 100%;
    margin: 0;
    padding: 0;
}
body {
    background: var(--background);
    font-family: "Manrope", sans-serif;
    color: var(--text);
    position: relative;
    overflow-x: hidden;
}
.signature-grid {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(to right, var(--outline-variant) 1px, transparent 1px),
        linear-gradient(to bottom, var(--outline-variant) 1px, transparent 1px);
    background-size: 60px 60px;
    opacity: 0.05;
    pointer-events: none;
    z-index: 0;
}
.page-glow-top,
.page-glow-bottom {
    position: fixed;
    border-radius: 999px;
    pointer-events: none;
    z-index: 0;
}
.page-glow-top {
    top: 4rem;
    right: -6rem;
    width: 26rem;
    height: 26rem;
    background: color-mix(in srgb, var(--accent) 18%, transparent);
    filter: blur(120px);
}
.page-glow-bottom {
    bottom: -4rem;
    left: -4rem;
    width: 18rem;
    height: 18rem;
    background: color-mix(in srgb, var(--accent) 14%, #855eca 12%);
    filter: blur(95px);
}
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}
.topbar {
    position: relative;
    z-index: 2;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(22, 7, 37, 0.36);
    backdrop-filter: blur(8px);
}
.topbar-inner {
    max-width: 1120px;
    margin: 0 auto;
    padding: 1.1rem 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.brand {
    margin: 0;
    font-family: "Newsreader", serif;
    font-weight: 500;
    font-size: 1.8rem;
    letter-spacing: -0.03em;
}
.topbar-icons {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}
.topbar-icons .material-symbols-outlined {
    color: color-mix(in srgb, var(--accent) 82%, white 18%);
    opacity: 0.8;
}
.auth-shell {
    position: relative;
    z-index: 2;
    min-height: calc(100vh - 77px);
    min-height: calc(100dvh - 77px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(1rem, 2vw, 1.4rem);
}
.auth-wrap {
    width: min(100%, 860px);
}
.auth-card {
    border-radius: 1rem;
    border: 1px solid var(--border);
    background: var(--surface-soft);
    backdrop-filter: blur(24px);
    box-shadow: 0 24px 48px rgba(15, 0, 105, 0.16);
    overflow: hidden;
    padding: clamp(1.2rem, 3vw, 3.5rem);
}
.chip {
    display: inline-flex;
    align-items: center;
    margin-bottom: 1.15rem;
    padding: 0.36rem 0.62rem;
    border-radius: 0.28rem;
    border: 1px solid rgba(70, 69, 85, 0.45);
    background: rgba(51, 35, 67, 0.72);
    color: var(--muted);
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.19em;
    text-transform: uppercase;
}
h1 {
    margin: 0;
    font-family: "Newsreader", serif;
    font-size: clamp(2.35rem, 6vw, 4rem);
    font-weight: 500;
    line-height: 0.97;
    letter-spacing: -0.03em;
}
.copy {
    margin: 0.85rem 0 1.4rem;
    color: var(--muted);
    font-size: 1rem;
    line-height: 1.6;
    max-width: 36rem;
}
.alert {
    border-radius: 0.8rem;
    padding: 0.85rem 0.95rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.alert-danger {
    background: var(--danger-bg);
    color: var(--danger-text);
}
.alert-success {
    background: var(--success-bg);
    color: var(--success-text);
}
form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
label {
    display: block;
    margin: 0 0 0.5rem 0.15rem;
    font-size: 0.73rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--muted);
}
input {
    width: 100%;
    border-radius: 0.45rem;
    border: 1px solid rgba(145, 143, 161, 0.32);
    background: var(--surface-lowest);
    color: var(--text);
    padding: 0.94rem 1rem;
    font: inherit;
    transition: border-color 180ms ease, box-shadow 180ms ease, background 180ms ease;
}
input::placeholder {
    color: rgba(145, 143, 161, 0.68);
}
input:focus {
    outline: none;
    border-color: color-mix(in srgb, var(--accent) 64%, white 36%);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 58%, transparent);
    background: color-mix(in srgb, var(--surface-lowest) 88%, var(--accent) 12%);
}
.primary-action {
    border: 0;
    border-radius: 0.45rem;
    padding: 0.95rem 1rem;
    font: inherit;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    color: #ffffff;
    background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 82%, #4c42e9 18%), color-mix(in srgb, var(--accent) 68%, #c3c0ff 32%));
    box-shadow: 0 14px 30px color-mix(in srgb, var(--accent) 28%, transparent);
    transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
}
.primary-action:hover,
.primary-action:focus {
    transform: translateY(-1px);
    filter: brightness(1.03);
}
.primary-action .material-symbols-outlined {
    font-size: 1.1rem;
    transition: transform 140ms ease;
}
.primary-action:hover .material-symbols-outlined {
    transform: translateX(3px);
}
.back-link {
    margin-top: 0.2rem;
    display: inline-flex;
    align-items: center;
}
.back-link a {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--outline);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: color 150ms ease;
}
.back-link a:hover,
.back-link a:focus {
    color: color-mix(in srgb, var(--accent) 84%, white 16%);
}
.back-link .material-symbols-outlined {
    font-size: 1.1rem;
    transition: transform 160ms ease;
}
.back-link a:hover .material-symbols-outlined {
    transform: translateX(-2px);
}
.support-grid {
    margin-top: clamp(1rem, 2.5vw, 1.65rem);
    display: grid;
    gap: 1rem;
}
.support-box h4 {
    margin: 0 0 0.35rem;
    font-size: 0.64rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--accent) 82%, white 18%);
}
.support-box p {
    margin: 0;
    color: color-mix(in srgb, var(--muted) 82%, transparent 18%);
    font-size: 0.86rem;
    line-height: 1.5;
}
@media (min-width: 760px) {
    .support-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.2rem;
    }
}
@media (max-width: 640px) {
    .topbar-inner {
        padding: 0.9rem 0.95rem;
    }
    .brand {
        font-size: 1.55rem;
    }
}
</style>
</head>
<body>
<div class="signature-grid"></div>
<div class="page-glow-top"></div>
<div class="page-glow-bottom"></div>

<header class="topbar">
    <div class="topbar-inner">
        <h2 class="brand">SentryLink</h2>
        <div class="topbar-icons" aria-hidden="true">
            <span class="material-symbols-outlined">help_outline</span>
            <span class="material-symbols-outlined">info</span>
        </div>
    </div>
</header>

<main class="auth-shell">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="chip"><?= h($roleConfig[$role]['label']) ?> Reset</div>
            <h1>Recover access.</h1>
            <p class="copy">Enter your verified email already bound to your <?= h(strtolower($roleConfig[$role]['label'])) ?> account. The system will email a secure reset link.</p>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?= h($status) ?>"><?= h($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div>
                    <label for="email">Verified Email Address</label>
                    <input id="email" type="email" name="email" value="<?= h(old('email')) ?>" maxlength="254" placeholder="name@university.edu" required>
                </div>
                <?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
                    <div class="cf-turnstile" data-sitekey="<?= h((string) $turnstileSiteKey) ?>" data-theme="dark" data-size="flexible"></div>
                <?php endif; ?>
                <button type="submit" class="primary-action">
                    <span>Email Reset Link</span>
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                </button>
            </form>

            <div class="back-link">
                <a href="<?= h(app_url(match ($role) { 'student' => 's/auth/login', 'ssg' => 'o/auth/login', 'admin' => 'admin/auth/login', 'director' => 'director/auth/login', default => 's/auth/login', })) ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
                    <span>Back to login</span>
                </a>
            </div>
        </div>

        <div class="support-grid">
            <div class="support-box">
                <h4>Immediate Support</h4>
                <p>If you no longer have access to your inbox, contact your administrator for account recovery assistance.</p>
            </div>
            <div class="support-box">
                <h4>Security Protocol</h4>
                <p>Reset links expire shortly after issuance and can only be used once for your account protection.</p>
            </div>
        </div>
    </div>
</main>

<?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
</body>
</html>
