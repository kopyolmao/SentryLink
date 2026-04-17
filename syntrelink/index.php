<?php
require_once __DIR__ . '/includes/app.php';

if (current_user_id() && current_user_role()) {
    redirect_to(role_home((string) current_user_role()));
}

redirect_to('s/auth/login');
?>
