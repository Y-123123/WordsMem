<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();
require_auth();

$slug = $_GET['slug'] ?? '';

if ($slug !== '') {
    $category = db_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
    if (!$category) {
        redirect(BASE_URL . '/categories.php');
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // 同一个 headword 可能在多个词库(bank)里各有一行，按 headword 去重，
    // 每组取 MAX(id) 代表一行，避免"分类词汇"里同一个词重复出现好几次。
    $total = db_count(
        'SELECT COUNT(DISTINCT w.headword) FROM word_categories wc
         JOIN words w ON w.id = wc.word_id WHERE wc.category_id = ?',
        [$category['id']]
    );
    $idRows = db_all(
        "SELECT MAX(w.id) AS id FROM words w
         JOIN word_categories wc ON wc.word_id = w.id
         WHERE wc.category_id = ?
         GROUP BY w.headword
         ORDER BY w.headword
         LIMIT $perPage OFFSET $offset",
        [$category['id']]
    );
    $ids = array_column($idRows, 'id');
    $words = $ids ? db_all(
        'SELECT * FROM words WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY headword',
        $ids
    ) : [];
    $totalPages = max(1, (int) ceil($total / $perPage));

    $page_title = $category['name'];
    include __DIR__ . '/core/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><i class="bi bi-tag text-info"></i> <?= e($category['name']) ?>（共 <?= $total ?> 词）</h5>
      <a href="<?= BASE_URL ?>/categories.php" class="btn btn-sm btn-outline-secondary">返回全部分类</a>
    </div>
    <div class="card shadow-sm">
      <div class="list-group list-group-flush">
        <?php foreach ($words as $w): ?>
          <?php $synonyms = json_decode($w['synonyms'] ?? '[]', true) ?: []; ?>
          <div class="list-group-item">
            <a href="#w-<?= (int) $w['id'] ?>" data-bs-toggle="collapse"
               class="d-flex justify-content-between align-items-center text-decoration-none text-dark">
              <div>
                <strong><?= e($w['headword']) ?></strong>
                <?php if ($w['us_phone']): ?><span class="text-muted small ms-2">[<?= e($w['us_phone']) ?>]</span><?php endif; ?>
              </div>
              <i class="bi bi-chevron-down text-muted"></i>
            </a>
            <div class="collapse mt-2" id="w-<?= (int) $w['id'] ?>">
              <div class="small ps-2 border-start border-3">
                <div><strong>中文：</strong><?= e($w['translation_cn']) ?></div>
                <?php if ($w['definition_en']): ?>
                  <div class="fst-italic text-muted"><strong>English：</strong><?= e($w['definition_en']) ?></div>
                <?php endif; ?>
                <?php if ($w['example_en']): ?>
                  <div class="bg-light rounded p-2 mt-2">
                    <div><?= e($w['example_en']) ?></div>
                    <?php if ($w['example_cn']): ?><div class="text-muted small mt-1"><?= e($w['example_cn']) ?></div><?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if ($w['root_note']): ?>
                  <div class="text-warning-emphasis mt-2"><strong>词根词缀：</strong><?= e($w['root_note']) ?></div>
                <?php endif; ?>
                <?php if ($synonyms): ?>
                  <div class="text-muted mt-2"><strong>同近义词：</strong><?= e(implode('，', $synonyms)) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
              <a class="page-link" href="?slug=<?= urlencode($slug) ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <?php
    include __DIR__ . '/core/footer.php';
    exit;
}

$categories = db_all(
    'SELECT c.*, COUNT(wc.id) AS word_count FROM categories c
     LEFT JOIN word_categories wc ON wc.category_id = c.id
     GROUP BY c.id ORDER BY word_count DESC'
);

$page_title = '分类词汇';
include __DIR__ . '/core/header.php';
?>
<h5 class="mb-3">按类别记忆</h5>
<p class="text-muted small">按主题把单词分组，帮助建立语义联想 —— 比如背"哺乳动物"相关的词时，可以放在一起联想记忆。分类是根据释义关键词自动归类的，不是每个词都会命中分类。</p>
<div class="row g-3">
  <?php foreach ($categories as $cat): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a href="?slug=<?= urlencode($cat['slug']) ?>" class="card shadow-sm text-decoration-none h-100">
        <div class="card-body text-center">
          <div class="fw-bold text-dark"><?= e($cat['name']) ?></div>
          <div class="text-muted small"><?= (int) $cat['word_count'] ?> 词</div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/core/footer.php'; ?>
