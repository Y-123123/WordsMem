<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();

if (is_logged_in()) {
    redirect(BASE_URL . '/dashboard.php');
}

// Registration can be closed from core/config.php once your users have signed
// up. 注册可以在 core/config.php 里关掉，防止陌生人在公网实例上注册。
if (defined('ALLOW_REGISTRATION') && !ALLOW_REGISTRATION) {
    $page_title = '注册';
    include __DIR__ . '/core/header.php';
    echo '<div class="row justify-content-center"><div class="col-md-5">'
       . '<div class="alert alert-secondary mt-4">本站已关闭注册，请联系管理员开通账号。'
       . ' <a href="' . BASE_URL . '/login.php">返回登录</a></div></div></div>';
    include __DIR__ . '/core/footer.php';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirmation'] ?? '';

    if ($password !== $passwordConfirm) {
        $error = '两次输入的密码不一致。';
    } else {
        [$ok, $msg] = auth_register($name, $email, $password);
        if ($ok) {
            redirect(BASE_URL . '/dashboard.php');
        }
        $error = $msg;
    }
}

$page_title = '注册';
include __DIR__ . '/core/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm mt-4">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">注册新账号</h4>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">姓名</label>
            <input type="text" name="name" class="form-control" required autofocus value="<?= e($_POST['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">邮箱</label>
            <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">密码（至少8位）</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="mb-3">
            <label class="form-label">确认密码</label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
          </div>
          <button type="submit" class="btn btn-primary w-100">注册</button>
        </form>
        <p class="text-center mt-3 mb-0">
          已经有账号？<a href="<?= BASE_URL ?>/login.php">直接登录</a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/core/footer.php'; ?>
