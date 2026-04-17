<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                background: "#0d0f31",
                surface: "#0d0f31",
                "surface-container-lowest": "#080a2c",
                "surface-container-low": "#16183a",
                "surface-container": "#1a1c3e",
                "surface-container-high": "#252749",
                "surface-container-highest": "#2f3255",
                "surface-variant": "#2f3255",
                "surface-bright": "#343659",
                "primary": "#ccbdff",
                "primary-container": "#5b19e6",
                "primary-fixed": "#e7deff",
                "primary-fixed-dim": "#ccbdff",
                "secondary": "#c2c3ee",
                "secondary-container": "#44466a",
                "tertiary": "#bbc9d0",
                "tertiary-container": "#48555b",
                "outline": "#948ea2",
                "outline-variant": "#494456",
                "on-surface": "#e0e0ff",
                "on-surface-variant": "#cac3d9",
                "on-primary": "#360096",
                "on-background": "#e0e0ff",
                "error": "#ffb4ab",
                "error-container": "#93000a",
                "on-error-container": "#ffdad6"
            },
            fontFamily: {
                headline: ["Newsreader", "serif"],
                body: ["Manrope", "sans-serif"],
                label: ["Manrope", "sans-serif"]
            },
            animation: {
                "fade-in-up": "fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards",
                shimmer: "shimmer 4s infinite linear"
            },
            keyframes: {
                fadeInUp: {
                    "0%": { opacity: "0", transform: "translateY(20px)" },
                    "100%": { opacity: "1", transform: "translateY(0)" }
                },
                shimmer: {
                    "0%": { backgroundPosition: "-200% 0" },
                    "100%": { backgroundPosition: "200% 0" }
                }
            }
        }
    }
};
</script>
<style>
html,
body {
    height: 100%;
}

body {
    font-family: "Manrope", sans-serif;
    overflow: hidden;
}

h1, h2, .headline {
    font-family: "Newsreader", serif;
}

.blueprint-grid {
    background-size: 24px 24px;
    background-image:
        linear-gradient(to right, rgba(73, 68, 86, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(73, 68, 86, 0.1) 1px, transparent 1px);
}

.vanguard-glow {
    background: radial-gradient(circle at center, rgba(91, 25, 230, 0.15) 0%, transparent 70%);
}

.btn-shimmer {
    background: linear-gradient(45deg, #ccbdff 0%, #ccbdff 40%, #e7deff 50%, #ccbdff 60%, #ccbdff 100%);
    background-size: 200% 200%;
    animation: shimmer 4s infinite linear;
}

.animate-delay-100 { animation-delay: 100ms; }
.animate-delay-200 { animation-delay: 200ms; }
.animate-delay-300 { animation-delay: 300ms; }
.animate-delay-400 { animation-delay: 400ms; }
.animate-delay-500 { animation-delay: 500ms; }

.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.viewport-shell {
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: stretch;
    overflow: hidden;
}

.hero-copy {
    display: flex;
    flex-direction: column;
    gap: clamp(1rem, 1.8vw, 1.5rem);
}

.auth-panel {
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(1rem, 2vw, 1.5rem);
}

.auth-card {
    width: min(100%, 28rem);
    max-height: calc(100dvh - 2rem);
    padding: clamp(1.25rem, 2vw, 2rem);
    display: flex;
    flex-direction: column;
    gap: clamp(1rem, 1.4vw, 1.25rem);
}

.auth-card-head {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auth-footer {
    margin-top: 0.25rem;
    border-top: 1px solid rgba(73, 68, 86, 0.28);
    padding-top: 1rem;
}

.error-banner {
    line-height: 1.35;
}

@media (max-width: 1023px) {
    .auth-panel {
        padding-top: 4.5rem;
        padding-bottom: 1rem;
    }

    .auth-card {
        width: min(100%, 26rem);
        max-height: calc(100dvh - 5.5rem);
    }
}

@media (max-height: 820px) {
    .auth-card {
        gap: 0.85rem;
        padding: 1.125rem;
    }

    .auth-form {
        gap: 0.85rem;
    }

    .auth-footer {
        padding-top: 0.8rem;
    }
}

@media (max-height: 720px) {
    .hero-copy p,
    .auth-footer p {
        font-size: 0.8125rem;
    }
}
</style>
</head>
<body class="bg-background text-on-background selection:bg-primary-container selection:text-primary-fixed">
<?php
$normalizedPortal = strtolower((string) $portalLabel);
$headline = match ($normalizedPortal) {
    'student'  => 'Student access with dynamic ticket validation.',
    'officer'  => 'Officer access for live gate validation.',
    'admin'    => 'Administrative access for events and reports.',
    'director' => 'Director access for oversight and audit visibility.',
    default    => 'Secure access for the SentryLink platform.',
};

$supportingText = match ($normalizedPortal) {
    'student'  => 'Sign in to manage event tickets, live QR credentials, and campus notifications.',
    'officer'  => 'Sign in to open the scanner, validate admissions, and monitor gate activity in real time.',
    'admin'    => 'Sign in to manage events, ticket imports, admissions, and operational reporting.',
    'director' => 'Sign in to review event outcomes, admissions, and audit trails from a leadership view.',
    default    => 'Use your verified account credentials to continue into the SentryLink system.',
};

$portalNotice = match ($normalizedPortal) {
    'student'  => 'Use the student account assigned to your record. Password recovery is handled through your verified email.',
    'officer'  => 'Only provisioned gate officers should use this portal. Use the email bound to your officer account for recovery.',
    'admin'    => 'This portal is for event operations staff. Sign in only with the admin account assigned to you.',
    'director' => 'This portal is reserved for leadership review access. Use your assigned director account credentials.',
    default    => 'Use the credentials assigned to your account for this portal.',
};

$passwordFieldId = 'password-input';
$heroSystemTitle = trim((string) ($heroTitle ?? '')) !== '' ? (string) $heroTitle : 'SentryLink';
?>
<div class="fixed inset-0 pointer-events-none vanguard-glow"></div>
<div class="fixed inset-0 pointer-events-none blueprint-grid"></div>
<main class="viewport-shell relative lg:grid lg:grid-cols-[minmax(0,1.15fr)_minmax(22rem,28rem)]">
    <section class="relative hidden overflow-hidden p-10 lg:flex lg:flex-col lg:justify-between xl:p-12 2xl:p-16">
        <div class="absolute -top-20 -left-20 h-96 w-96 rounded-full bg-primary-container/20 blur-[120px]"></div>
        <div class="relative z-10 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-tr from-primary-container to-primary shadow-lg shadow-primary-container/20">
                    <span class="material-symbols-outlined text-xl text-on-primary" style="font-variation-settings: 'FILL' 1;">security</span>
                </div>
                <span class="headline text-3xl font-light italic tracking-tight text-on-surface">SentryLink</span>
            </div>
            <div class="mt-4 h-px w-24 bg-gradient-to-r from-primary/40 to-transparent"></div>
        </div>

        <div class="hero-copy relative z-10 max-w-xl">
            <div class="animate-fade-in-up animate-delay-100">
                <span class="mb-6 block text-[11px] font-semibold uppercase tracking-[0.24em] text-primary/70"><?= h($heroBadge) ?></span>
            </div>
            <h1 class="animate-fade-in-up animate-delay-200 text-4xl font-light leading-tight text-on-surface xl:text-5xl 2xl:text-6xl">
                <?= h($heroSystemTitle) ?><br>
                <span class="italic font-normal"><?= h($portalLabel) ?></span>
                <span class="text-primary-fixed-dim">portal access</span>.
            </h1>
            <p class="animate-fade-in-up animate-delay-300 max-w-md text-base leading-relaxed text-on-surface-variant xl:text-lg"><?= h($heroText !== '' ? $heroText : $supportingText) ?></p>
        </div>

        <div class="relative z-10 flex items-center gap-12 text-[10px] font-bold uppercase tracking-[0.22em] text-on-surface-variant/40 animate-fade-in-up animate-delay-500">
            <span>Campus Event Security</span>
            <span><?= h($badge) ?></span>
            <div class="h-px flex-grow bg-outline-variant/10"></div>
        </div>

        <div class="absolute bottom-0 right-0 -z-10 h-full w-full opacity-30">
            <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent"></div>
            <img class="h-full w-full object-cover grayscale opacity-40" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCwHiI4kY4uE0JW2W4TGQ41NqNjHwzHl8gToYz8koOR70G5xcaPFKX5fWt0dP0MGXtdT2J6DEjljyfUG5g5wGthE_e3fYQNX9oA4fCIE-AEmjo7ZCreTxVGjYXzWRKRdgjey0KCujBrrk-MFGAnSyDTZsbMgdxMZtjNCH_gHs1Yn6F2jzEA5NtZAYXnbLCHopJV1jAWCiQgMoYvUiDg-Pl0TOX16SEJJyOe3Vr5cPzgichYzz9XgnDHE_ugsgjnw8VNGx0o4oSXiKQ" alt="Abstract architectural lines and dark glass geometry">
        </div>
    </section>

    <section class="auth-panel relative z-20">
        <div class="absolute left-5 top-5 flex items-center gap-2 animate-fade-in-up lg:hidden">
            <span class="headline text-2xl font-light italic text-on-surface">SentryLink</span>
        </div>

        <div class="group relative mt-6 w-full animate-fade-in-up animate-delay-400 lg:mt-0">
            <div class="absolute -inset-1 rounded-[2rem] bg-gradient-to-r from-primary-container/20 to-secondary-container/20 opacity-50 blur-2xl transition-all duration-1000 group-hover:opacity-100 group-hover:blur-3xl"></div>
            <div class="auth-card relative rounded-[2rem] border border-outline-variant/10 bg-surface-container-low/75 shadow-[0_0_50px_rgba(0,0,0,0.3)] backdrop-blur-3xl transition-all duration-700 group-hover:border-primary/20 group-hover:bg-surface-container-low/85">
                <div class="auth-card-head">
                    <span class="mb-5 inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-primary-fixed"><?= h($badge) ?></span>
                    <h2 class="text-3xl font-light text-on-surface xl:text-4xl">Sign in</h2>
                    <p class="text-sm text-on-surface-variant/70"><?= h($headline) ?></p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="error-banner rounded-2xl border border-error/20 bg-error-container/60 px-4 py-3 text-sm text-on-error-container">
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST" action="<?= h($actionUrl) ?>">
                    <div class="space-y-2">
                        <label class="ml-1 block text-[11px] font-bold uppercase tracking-widest text-on-surface-variant">Email Address</label>
                        <div class="group/input relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-xl text-on-surface-variant/40 transition-colors group-focus-within/input:text-primary">alternate_email</span>
                            <input type="email" name="email" value="<?= h(old('email')) ?>" class="w-full rounded-full border border-outline-variant/20 bg-surface-container-lowest/40 py-3.5 pl-12 pr-6 text-sm text-on-surface placeholder:text-on-surface-variant/30 focus:border-primary/50 focus:outline-none focus:ring-1 focus:ring-primary/50" placeholder="name@school.edu" required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="ml-1 block text-[11px] font-bold uppercase tracking-widest text-on-surface-variant">Password</label>
                        <div class="group/input relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-xl text-on-surface-variant/40 transition-colors group-focus-within/input:text-primary">lock_open</span>
                            <input id="<?= h($passwordFieldId) ?>" type="password" name="password" class="js-password-input w-full rounded-full border border-outline-variant/20 bg-surface-container-lowest/40 py-3.5 pl-12 pr-14 text-sm text-on-surface placeholder:text-on-surface-variant/30 focus:border-primary/50 focus:outline-none focus:ring-1 focus:ring-primary/50" placeholder="Enter your password" required>
                            <button type="button" class="js-password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 transition-colors hover:text-primary" aria-label="Show password" aria-pressed="false">
                                <span class="material-symbols-outlined text-xl js-password-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-xs text-on-surface-variant">Role: <?= h($portalLabel) ?></span>
                        <a href="<?= h($forgotUrl) ?>" class="text-xs font-semibold text-primary/80 transition-colors hover:text-primary">Forgot password?</a>
                    </div>

                    <?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
                        <div class="pt-1">
                            <div class="cf-turnstile" data-sitekey="<?= h((string) $turnstileSiteKey) ?>" data-theme="dark" data-size="flexible"></div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="group/btn relative mt-1 block w-full overflow-hidden rounded-full">
                        <div class="btn-shimmer absolute inset-0 opacity-90 transition-opacity group-hover/btn:opacity-100"></div>
                        <div class="relative flex items-center justify-center rounded-full px-8 py-3.5 font-bold tracking-wide text-on-primary transition-transform active:scale-95">
                            <?= h($submitLabel) ?>
                            <span class="material-symbols-outlined ml-2 text-lg transition-transform group-hover/btn:translate-x-1">arrow_forward</span>
                        </div>
                    </button>
                </form>

                <div class="auth-footer text-center">
                    <p class="text-sm text-on-surface-variant">
                        <?= h($portalNotice) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="absolute bottom-8 right-8 hidden select-none flex-col items-end gap-2 text-right opacity-30 2xl:flex animate-fade-in-up animate-delay-500">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">System Status: Nominal</span>
            <div class="flex gap-1">
                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary-container"></div>
                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary-container" style="animation-delay: 120ms;"></div>
                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary/20" style="animation-delay: 240ms;"></div>
            </div>
        </div>
    </section>
</main>
<div class="pointer-events-none fixed right-0 top-0 -z-10 h-1/3 w-1/3 bg-primary/5 blur-[150px]"></div>
<div class="pointer-events-none fixed bottom-0 left-0 -z-10 h-1/4 w-1/4 bg-primary-container/10 blur-[150px]"></div>
<script>
document.querySelectorAll(".js-password-toggle").forEach((toggle) => {
    toggle.addEventListener("click", () => {
        const input = toggle.parentElement.querySelector(".js-password-input");
        const icon = toggle.querySelector(".js-password-icon");
        const isVisible = input.type === "text";
        input.type = isVisible ? "password" : "text";
        toggle.setAttribute("aria-label", isVisible ? "Show password" : "Hide password");
        toggle.setAttribute("aria-pressed", isVisible ? "false" : "true");
        if (icon) {
            icon.textContent = isVisible ? "visibility" : "visibility_off";
        }
    });
});
</script>
<?php if (trim((string) ($turnstileSiteKey ?? '')) !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
</body>
</html>
