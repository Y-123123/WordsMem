<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();

redirect(BASE_URL . (is_logged_in() ? '/dashboard.php' : '/login.php'));
