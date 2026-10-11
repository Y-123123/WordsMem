<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();
require_auth();

$userId = auth_user()['id'];
$setting = get_or_create_settings($userId);

$boolFields = ['auto_reveal', 'show_us_phone', 'show_uk_phone', 'show_definition_en', 'show_example', 'show_root_note', 'show_synonyms', 'show_phrases', 'show_similar_words', 'show_semantic_tags', 'auto_play_audio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $wordBankId = (int) ($_POST['word_bank_id'] ?? 0);
    $dailyNewWords = max(1, min(200, (int) ($_POST['daily_new_words'] ?? 15)));
    $dailyReviewLimit = max(1, min(500, (int) ($_POST['daily_review_limit'] ?? 100)));

    $update = [
        'word_bank_id'        => $wordBankId ?: null,
        'daily_new_words'     => $dailyNewWords,
        'daily_review_limit'  => $dailyReviewLimit,
    ];
    foreach ($boolFields as $f) {
        $update[$f] = isset($_POST[$f]) ? 1 : 0;
    }

    db_update('user_settings', $update, 'user_id = ?', [$userId]);
    flash('success', '设置已保存。');
    redirect(BASE_URL . '/settings.php');
}

$banks = db_all('SELECT wb.*, (SELECT COUNT(*) FROM words w WHERE w.word_bank_id = wb.id) AS word_count FROM word_banks wb ORDER BY wb.name');

$fieldLabels = [
    'show_us_phone'      => '美式音标',
    'show_uk_phone'      => '英式音标',
    'show_definition_en' => '英文释义',
    'show_example'       => '例句',
    'show_root_note'     => '词根词缀助记',
    'show_synonyms'      => '同近义词',
    'show_phrases'       => '常用短语',
    'show_similar_words' => '易混淆词提醒（如 deference/difference）',
    'show_semantic_tags' => '分类/词根标签（点击可跳转到分类词汇/词根词缀页面）',
    'auto_play_audio'    => '发音按钮',
];

$page_title = '学习设置';
include __DIR__ . '/core/header.php';
?>
<div class="card shadow-sm word-card">
  <div class="card-body p-4">
    <form method="POST">
      <?= csrf_field() ?>

      <h6 class="fw-bold mb-2">词库</h6>
      <select name="word_bank_id" class="form-select mb-4">
        <?php foreach ($banks as $bank): ?>
          <option value="<?= (int) $bank['id'] ?>" <?= (int) $setting['word_bank_id'] === (int) $bank['id'] ? 'selected' : '' ?>>
            <?= e($bank['name']) ?>（<?= (int) $bank['word_count'] ?> 词）
          </option>
        <?php endforeach; ?>
      </select>

      <div class="row mb-4">
        <div class="col-6">
          <label class="form-label fw-bold">每日新词数</label>
          <input type="number" name="daily_new_words" min="1" max="200" class="form-control" value="<?= (int) $setting['daily_new_words'] ?>">
        </div>
        <div class="col-6">
          <label class="form-label fw-bold">每日最大复习数</label>
          <input type="number" name="daily_review_limit" min="1" max="500" class="form-control" value="<?= (int) $setting['daily_review_limit'] ?>">
        </div>
      </div>

      <h6 class="fw-bold mb-2">答题方式</h6>
      <div class="form-check mb-4">
        <input type="checkbox" name="auto_reveal" value="1" class="form-check-input" id="auto_reveal" <?= $setting['auto_reveal'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="auto_reveal">看到单词后直接显示释义（取消勾选则需点击"显示释义"按钮）</label>
      </div>

      <h6 class="fw-bold mb-2">单词卡片显示内容</h6>
      <?php foreach ($fieldLabels as $field => $label): ?>
        <div class="form-check mb-2">
          <input type="checkbox" name="<?= $field ?>" value="1" class="form-check-input" id="<?= $field ?>" <?= $setting[$field] ? 'checked' : '' ?>>
          <label class="form-check-label" for="<?= $field ?>"><?= e($label) ?></label>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn btn-primary mt-3">保存设置</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/core/footer.php'; ?>
