<?php
function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

function require_auth(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

function auth_register(string $name, string $email, string $password): array {
    $email = strtolower(trim($email));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        return [false, '请填写完整信息，密码至少需要8位。'];
    }
    if (db_one('SELECT id FROM users WHERE email = ?', [$email])) {
        return [false, '这个邮箱已经注册过了。'];
    }

    $userId = db_insert('users', [
        'name'          => $name,
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    // 新用户默认设置：默认词库取第一个，答题默认直接显示释义
    $defaultBank = db_one('SELECT id FROM word_banks ORDER BY id LIMIT 1');
    db_insert('user_settings', [
        'user_id'       => $userId,
        'word_bank_id'  => $defaultBank['id'] ?? null,
    ]);

    auth_login($email, $password);

    return [true, ''];
}

function auth_login(string $email, string $password): bool {
    $email = strtolower(trim($email));
    $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    unset($_SESSION['login_fails'], $_SESSION['login_lockout']);
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
    ];
    unset($_SESSION['study_queue'], $_SESSION['study_queue_date']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

function auth_logout(): void {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            die('CSRF验证失败，请返回刷新重试。');
        }
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}
