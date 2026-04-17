: '
@echo off
setlocal
cd /d "%~dp0"
goto :windows
'

set -eu

cd "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

PORT="${PORT:-8080}"
PHP_BIN="${PHP_BIN:-}"

php_cli_is_healthy() {
    php_candidate=$1

    [ -x "$php_candidate" ] || return 1

    if ! php_version_output="$("$php_candidate" -v 2>&1)"; then
        return 1
    fi

    case "$php_version_output" in
        *"PHP Startup:"*)
            return 1
            ;;
    esac

    if ! php_modules="$("$php_candidate" -m 2>/dev/null)"; then
        return 1
    fi

    printf '%s\n' "$php_modules" | grep -qx 'intl' || return 1
    printf '%s\n' "$php_modules" | grep -qx 'mbstring' || return 1
    printf '%s\n' "$php_modules" | grep -qx 'curl' || return 1

    return 0
}

select_php_bin() {
    PHP_SELECTED_BIN=
    PHP_FALLBACK_BIN=

    if [ -x /opt/lampp/bin/php ]; then
        PHP_FALLBACK_BIN=/opt/lampp/bin/php
        if php_cli_is_healthy /opt/lampp/bin/php; then
            PHP_SELECTED_BIN=/opt/lampp/bin/php
            return 0
        fi
    fi

    if command -v php >/dev/null 2>&1; then
        php_path_candidate=$(command -v php)
        if [ -z "$PHP_FALLBACK_BIN" ]; then
            PHP_FALLBACK_BIN=$php_path_candidate
        fi
        if [ "$php_path_candidate" != "/opt/lampp/bin/php" ] && php_cli_is_healthy "$php_path_candidate"; then
            PHP_SELECTED_BIN=$php_path_candidate
            return 0
        fi
    fi

    if [ -n "$PHP_FALLBACK_BIN" ]; then
        PHP_SELECTED_BIN=$PHP_FALLBACK_BIN
        return 0
    fi

    return 1
}

if [ -z "$PHP_BIN" ]; then
    if select_php_bin; then
        PHP_BIN=$PHP_SELECTED_BIN
        if ! php_cli_is_healthy "$PHP_BIN"; then
            echo "Warning: falling back to '$PHP_BIN' because no clean PHP CLI was found. You may still see PHP startup warnings until that installation is fixed." >&2
        fi
    else
        echo "PHP executable not found. Set PHP_BIN or install PHP/LAMPP." >&2
        exit 1
    fi
fi

detect_linux_ip() {
    if command -v hostname >/dev/null 2>&1; then
        for ip in $(hostname -I 2>/dev/null); do
            case "$ip" in
                127.*|169.254.*|172.17.*|172.18.*|172.19.*|172.2?.*|172.30.*|172.31.*|192.168.122.*)
                    ;;
                *.*)
                    printf '%s\n' "$ip"
                    return 0
                    ;;
            esac
        done
    fi

    if command -v ip >/dev/null 2>&1; then
        ip -4 -o addr show scope global 2>/dev/null \
            | awk '{print $4}' \
            | cut -d/ -f1 \
            | while IFS= read -r ip; do
                case "$ip" in
                    127.*|169.254.*|172.17.*|172.18.*|172.19.*|172.2?.*|172.30.*|172.31.*|192.168.122.*)
                        ;;
                    *.*)
                        printf '%s\n' "$ip"
                        return 0
                        ;;
                esac
            done
    fi

    return 1
}

HOST_IP="${HOST_IP:-}"
if [ -z "$HOST_IP" ]; then
    HOST_IP="$(detect_linux_ip || true)"
fi

if [ -z "$HOST_IP" ]; then
    echo "Could not determine an active IPv4 address." >&2
    exit 1
fi

echo
echo "Detected Linux/Unix environment."
echo "Starting SentryLink on:"
echo "http://$HOST_IP:$PORT"
echo
echo "Other devices on the same local network can open that URL."
echo "Press Ctrl+C to stop the server."
echo

exec "$PHP_BIN" spark serve --host "$HOST_IP" --port "$PORT"

exit $?

: <<'__BATCH__'
:windows
if not defined PORT set "PORT=8080"
set "PHP_EXE=%PHP_EXE%"

if not defined PHP_EXE set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
    for /f "usebackq delims=" %%I in (`where php 2^>nul`) do (
        set "PHP_EXE=%%I"
        goto :php_found
    )
)

:php_found
if not exist "%PHP_EXE%" (
    echo PHP executable not found. Set PHP_EXE or install XAMPP/PHP.
    exit /b 1
)

set "HOST_IP=%HOST_IP%"
if defined HOST_IP goto :host_ready

for /f "usebackq delims=" %%I in (`powershell -NoProfile -ExecutionPolicy Bypass -Command "$ip = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notmatch '^127\.' -and $_.IPAddress -notmatch '^169\.254\.' -and $_.InterfaceAlias -notmatch 'Loopback|vEthernet|VirtualBox|VMware' } | Sort-Object InterfaceMetric, SkipAsSource | Select-Object -ExpandProperty IPAddress -First 1; if (-not $ip) { $ip = Get-CimInstance Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled } | ForEach-Object { $_.IPAddress } | Where-Object { $_ -match '^\d+\.' -and $_ -notmatch '^127\.' -and $_ -notmatch '^169\.254\.' } | Select-Object -First 1 }; if (-not $ip) { exit 1 }; Write-Output $ip"`) do set "HOST_IP=%%I"

:host_ready
if not defined HOST_IP (
    echo Could not determine an active IPv4 address.
    exit /b 1
)

echo.
echo Detected Windows environment.
echo Starting SentryLink on:
echo http://%HOST_IP%:%PORT%
echo.
echo Other devices on the same local network can open that URL.
echo Press Ctrl+C to stop the server.
echo.

"%PHP_EXE%" spark serve --host %HOST_IP% --port %PORT%
exit /b %errorlevel%
__BATCH__
