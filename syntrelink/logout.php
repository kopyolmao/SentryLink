<?php
require_once __DIR__ . '/includes/app.php';

logout_current_session();

redirect_to('s/auth/login');
?>
