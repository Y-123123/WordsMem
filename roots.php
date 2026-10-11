<?php
require_once __DIR__ . '/core/bootstrap.php';
session_init();
require_auth();

$root = $_GET['root'] ?? '';

if ($root !== '') {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    $total = db_count(
        'SELECT COUNT(DISTINCT w.headword) FROM word_roots wr
         JOIN words w ON w.id = wr.word_id
         WHERE wr.root_text = ?',
        [$root]
    );

    if ($total === 0) {
        redirect(BASE_URL . '/roots.php');
    }

    $idRows = db_all(
        "SELECT MAX(w.id) AS id FROM word_roots wr
         JOIN words w ON w.id = wr.word_id
         WHERE wr.root_text = ?
         GROUP BY w.headword
         ORDER BY w.headword
         LIMIT $perPage OFFSET $offset",
        [$root]
    );
    $ids = array_column($idRows, 'id');
    $words = $ids ? db_all(
        'SELECT * FROM words WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY headword',
        $ids
    ) : [];
    $totalPages = max(1, (int) ceil($total / $perPage));

    $page_title = "词根 {$root}";
    include __DIR__ . '/core/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><i class="bi bi-diagram-2 text-secondary"></i> 词根/词缀："<?= e($root) ?>"（共 <?= $total ?> 词）</h5>
      <a href="<?= BASE_URL ?>/roots.php" class="btn btn-sm btn-outline-secondary">返回全部词根</a>
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
                <?php if ($w['root_note']): ?>
                  <div class="text-warning-emphasis mt-2"><strong>词根词缀：</strong><?= e($w['root_note']) ?></div>
                <?php endif; ?>
                <?php if ($w['example_en']): ?>
                  <div class="bg-light rounded p-2 mt-2">
                    <div><?= e($w['example_en']) ?></div>
                    <?php if ($w['example_cn']): ?><div class="text-muted small mt-1"><?= e($w['example_cn']) ?></div><?php endif; ?>
                  </div>
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
              <a class="page-link" href="?root=<?= urlencode($root) ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <?php
    include __DIR__ . '/core/footer.php';
    exit;
}

$roots = db_all(
    "SELECT root_text, COUNT(DISTINCT wr.word_id) AS cnt
     FROM word_roots wr
     GROUP BY root_text
     HAVING cnt >= 3
     ORDER BY cnt DESC
     LIMIT 300"
);

$page_title = '词根词缀';
include __DIR__ . '/core/header.php';
?>
<h5 class="mb-3">词根词缀法</h5>
<p class="text-muted small">从每个单词的记忆助记里自动解析出词根/词缀片段，同一词根词缀归类展示，方便"举一反三"式记忆（比如 duc/duce 表示"引导"：deduce、induce、seduce、traduce）。</p>
<div class="d-flex flex-wrap gap-2">
  <?php foreach ($roots as $r): ?>
    <a href="?root=<?= urlencode($r['root_text']) ?>" class="badge text-bg-secondary text-decoration-none p-2" style="font-size:.9rem">
      <?= e($r['root_text']) ?> <span class="opacity-75">(<?= (int) $r['cnt'] ?>)</span>
    </a>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/core/footer.php'; ?>
