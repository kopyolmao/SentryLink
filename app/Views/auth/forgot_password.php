<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Forgot Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<style>
:root {
    --accent: <?= h($roleConfig[$role]['accent']) ?>;
    --background: #0d0f31;
    --surface-low: rgba(22, 24, 58, 0.78);
    --surface-lowest: rgba(8, 10, 44, 0.78);
    --border: rgba(202, 195, 217, 0.12);
    --text: #e0e0ff;
    --muted: #cac3d9;
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
    width: min(100%, 30rem);
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
    color: var(--accent);
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
    border-color: color-mix(in srgb, var(--accent) 60%, white 40%);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 55%, transparent);
}
button {
    border: 0;
    border-radius: 999px;
    padding: 0.95rem 1rem;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    color: white;
    background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 68%, white 32%));
    box-shadow: 0 12px 28px color-mix(in srgb, var(--accent) 24%, transparent);
}
.back-link {
    margin-top: 1rem;
}
.back-link a {
    color: #e7deff;
    text-decoration: none;
    font-weight: 600;
}
</style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand">SentryLink</div>
        <div class="chip"><?= h($roleConfig[$role]['label']) ?> Reset</div>
        <h1>Recover access.</h1>
        <p class="copy">Enter the verified email already bound to your <?= h(strtolower($roleConfig[$role]['label'])) ?> account. The system will email a secure reset link.</p>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= h($status) ?>"><?= h($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div>
                <label for="email">Verified Email</label>
                <input id="email" type="email" name="email" required>
            </div>
            <?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
                <div class="cf-turnstile" data-sitekey="<?= h((string) $turnstileSiteKey) ?>" data-theme="dark" data-size="flexible"></div>
            <?php endif; ?>
            <button type="submit">Email Reset Link</button>
        </form>

        <div class="back-link">
            <a href="<?= h(app_url(match ($role) { 'student' => 's/auth/login', 'ssg' => 'o/auth/login', 'admin' => 'admin/auth/login', 'director' => 'director/auth/login', default => 's/auth/login', })) ?>">Back to login</a>
        </div>
    </div>
</div>
<?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
</body>
</html>
