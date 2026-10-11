<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();

if (is_logged_in()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fails   = (int) ($_SESSION['login_fails'] ?? 0);
    $lockout = (int) ($_SESSION['login_lockout'] ?? 0);
    if ($fails >= 5 && time() < $lockout) {
        $error = '登录失败次数过多，请30秒后再试。';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (auth_login($email, $password)) {
            redirect(BASE_URL . '/dashboard.php');
        }
        $_SESSION['login_fails'] = $fails + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lockout'] = time() + 30;
        }
        $error = '邮箱或密码不正确。';
    }
}

$page_title = '登录';
include __DIR__ . '/core/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm mt-4">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">登录</h4>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">邮箱</label>
            <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">密码</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">登录</button>
        </form>
        <?php if (!defined('ALLOW_REGISTRATION') || ALLOW_REGISTRATION): ?>
        <p class="text-center mt-3 mb-0">
          还没有账号？<a href="<?= BASE_URL ?>/register.php">立即注册</a>
        </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/core/footer.php'; ?>
