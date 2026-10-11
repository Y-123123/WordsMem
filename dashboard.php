<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();
require_auth();

$userId = auth_user()['id'];

$stats = [
    'new'     => db_count("SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND status = 'new'", [$userId]),
    'learning'=> db_count("SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND status = 'learning'", [$userId]),
    'review'  => db_count("SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND status = 'review'", [$userId]),
    'due_now' => db_count("SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND status IN ('review','learning') AND due_at <= NOW()", [$userId]),
];

$page_title = '学习概览';
include __DIR__ . '/core/header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm">
      <div class="num text-primary"><?= $stats['due_now'] ?></div>
      <div class="text-muted">待复习</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm">
      <div class="num text-dark"><?= $stats['new'] ?></div>
      <div class="text-muted">新词</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm">
      <div class="num text-warning"><?= $stats['learning'] ?></div>
      <div class="text-muted">学习中</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm">
      <div class="num text-success"><?= $stats['review'] ?></div>
      <div class="text-muted">复习中</div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body text-center py-5">
    <p class="text-muted mb-4">每个人的记忆曲线都不一样 —— 系统会根据你的答题情况，自动安排今天该复习的旧词和该学习的新词。</p>
    <a href="<?= BASE_URL ?>/study.php" class="btn btn-primary btn-lg px-4">开始背单词</a>
  </div>
</div>

<?php include __DIR__ . '/core/footer.php'; ?>
