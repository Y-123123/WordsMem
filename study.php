<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();
require_auth();

$userId = auth_user()['id'];
$setting = get_or_create_settings($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $stateId = (int) ($_POST['state_id'] ?? 0);
    $grade = (int) ($_POST['grade'] ?? -1);
    $queue = $_SESSION['study_queue'] ?? [];

    if (!in_array($grade, [0, 3, 4, 5], true)) {
        redirect(BASE_URL . '/study.php');
    }

    if (!empty($queue) && (int) $queue[0] === $stateId) {
        $state = db_one('SELECT * FROM user_word_states WHERE id = ? AND user_id = ?', [$stateId, $userId]);
        if ($state) {
            $update = sm2_review($state, $grade);
            db_update('user_word_states', $update, 'id = ?', [$stateId]);
            array_shift($queue);
            $_SESSION['study_queue'] = $queue;
        }
    }

    redirect(BASE_URL . '/study.php');
}

$queue = ensure_study_queue($userId, $setting);

$page_title = '背单词';
include __DIR__ . '/core/header.php';

if (empty($queue)) {
    ?>
    <div class="card shadow-sm word-card">
      <div class="card-body text-center py-5">
        <div style="font-size:3rem">🎉</div>
        <h4 class="mt-2">今日学习任务已完成！</h4>
        <p class="text-muted mt-2">明天再来复习到期的单词吧。你也可以去 <a href="<?= BASE_URL ?>/settings.php">设置</a> 里调整每日新词量。</p>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-dark mt-3">返回概览</a>
      </div>
    </div>
    <?php
    include __DIR__ . '/core/footer.php';
    exit;
}

$state = db_one('SELECT * FROM user_word_states WHERE id = ? AND user_id = ?', [$queue[0], $userId]);
if (!$state) {
    unset($_SESSION['study_queue']);
    redirect(BASE_URL . '/study.php');
}
$word = db_one('SELECT * FROM words WHERE id = ?', [$state['word_id']]);
$synonyms = json_decode($word['synonyms'] ?? '[]', true) ?: [];
$phrases = json_decode($word['phrases'] ?? '[]', true) ?: [];
$similarWords = $setting['show_similar_words']
    ? find_similar_words($word['headword'], $word['word_bank_id'], $word['id'])
    : [];
$semanticTags = $setting['show_semantic_tags'] ? word_semantic_tags($word['id']) : ['categories' => [], 'roots' => []];
$revealed = (bool) $setting['auto_reveal'];
?>
<div class="d-flex justify-content-between align-items-center word-card mb-2">
  <h5 class="mb-0">背单词</h5>
  <span class="text-muted">剩余 <?= count($queue) ?> 词</span>
</div>

<div class="card shadow-sm word-card">
  <div class="card-body p-4 border-bottom text-center">
    <div class="word-headword"><?= e($word['headword']) ?></div>
    <div class="word-phone">
      <?php if ($setting['show_us_phone'] && $word['us_phone']): ?><span>美 [<?= e($word['us_phone']) ?>]</span><?php endif; ?>
      <?php if ($setting['show_uk_phone'] && $word['uk_phone']): ?><span class="ms-3">英 [<?= e($word['uk_phone']) ?>]</span><?php endif; ?>
    </div>
    <?php if ($setting['auto_play_audio']): ?>
      <?= pronounce_button_html($word['headword']) ?>
    <?php endif; ?>
  </div>

  <?php if (!$revealed): ?>
  <div class="card-body text-center" id="reveal-prompt">
    <button type="button" class="btn btn-dark px-4" onclick="document.getElementById('reveal-prompt').style.display='none';document.getElementById('answer-block').style.display='block';">
      显示释义
    </button>
  </div>
  <?php endif; ?>

  <div class="card-body" id="answer-block" style="<?= $revealed ? '' : 'display:none' ?>">
    <div class="fs-5 mb-3"><?= e($word['translation_cn']) ?></div>

    <?php if ($setting['show_definition_en'] && $word['definition_en']): ?>
      <div class="text-muted fst-italic mb-3"><?= e($word['definition_en']) ?></div>
    <?php endif; ?>

    <?php if ($setting['show_example'] && $word['example_en']): ?>
      <div class="bg-light rounded p-3 mb-3">
        <div><?= e($word['example_en']) ?></div>
        <?php if ($word['example_cn']): ?><div class="text-muted small mt-1"><?= e($word['example_cn']) ?></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($setting['show_root_note'] && $word['root_note']): ?>
      <div class="small text-warning-emphasis mb-2"><strong>词根词缀：</strong><?= e($word['root_note']) ?></div>
    <?php endif; ?>

    <?php if ($setting['show_synonyms'] && $synonyms): ?>
      <div class="small text-muted mb-2"><strong>同近义词：</strong><?= e(implode('，', $synonyms)) ?></div>
    <?php endif; ?>

    <?php if ($setting['show_phrases'] && $phrases): ?>
      <div class="small text-muted mb-2">
        <strong>常用短语：</strong>
        <ul class="mb-0">
          <?php foreach ($phrases as $p): ?>
            <li><?= e($p['en'] ?? '') ?><?= !empty($p['cn']) ? ' —— ' . e($p['cn']) : '' ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($similarWords): ?>
      <div class="alert alert-warning small mb-2">
        <strong><i class="bi bi-exclamation-triangle"></i> 注意区分：</strong>
        <?php foreach ($similarWords as $sw): ?>
          <div><strong><?= e($sw['headword']) ?></strong><?= $sw['translation_cn'] ? ' —— ' . e($sw['translation_cn']) : '' ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($semanticTags['categories'] || $semanticTags['roots']): ?>
      <div class="small mb-2">
        <?php foreach ($semanticTags['categories'] as $cat): ?>
          <a href="<?= BASE_URL ?>/categories.php?slug=<?= urlencode($cat['slug']) ?>" class="badge text-bg-info text-decoration-none me-1">
            <i class="bi bi-tag"></i> <?= e($cat['name']) ?>
          </a>
        <?php endforeach; ?>
        <?php foreach ($semanticTags['roots'] as $root): ?>
          <a href="<?= BASE_URL ?>/roots.php?root=<?= urlencode($root) ?>" class="badge text-bg-secondary text-decoration-none me-1">
            <i class="bi bi-diagram-2"></i> <?= e($root) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="row g-2 grade-buttons mt-3">
      <?= csrf_field() ?>
      <input type="hidden" name="state_id" value="<?= (int) $state['id'] ?>">
      <div class="col-3"><button name="grade" value="0" class="btn btn-outline-danger w-100">忘记了</button></div>
      <div class="col-3"><button name="grade" value="3" class="btn btn-outline-warning w-100">困难</button></div>
      <div class="col-3"><button name="grade" value="4" class="btn btn-outline-success w-100">记得</button></div>
      <div class="col-3"><button name="grade" value="5" class="btn btn-outline-primary w-100">简单</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/core/footer.php'; ?>
