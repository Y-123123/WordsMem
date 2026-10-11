<?php
// 一次性脚本：从 root_note 里的"词根+词根→释义"文本解析出词根/词缀片段，填充 word_roots 表。
// 用法： php scripts/extract_roots.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}
require __DIR__ . '/../core/bootstrap.php';
// 太常见/太宽泛的词缀不值得单独归类（词根词缀法里价值较低，且会导致某一组几千个词）
$stoplist = ['ly', 'er', 'or', 'ed', 'ing', 'es', 's', 'e', 'a', 'in', 'to', 'is', 'at'];

$rows = db_all("SELECT id, root_note FROM words WHERE root_note IS NOT NULL AND root_note LIKE '%+%'");
echo "candidates: " . count($rows) . "\n";

db_query('DELETE FROM word_roots');

$inserted = 0;
foreach ($rows as $row) {
    $text = $row['root_note'];
    $beforeArrow = preg_split('/→|->/u', $text)[0] ?? $text;
    $segments = explode('+', $beforeArrow);

    $roots = [];
    foreach ($segments as $seg) {
        if (preg_match('/^\s*([a-zA-Z]+)/', $seg, $m)) {
            $token = strtolower($m[1]);
            if (strlen($token) >= 2 && !in_array($token, $stoplist, true)) {
                $roots[$token] = true;
            }
        }
    }

    foreach (array_keys($roots) as $root) {
        db_insert('word_roots', ['word_id' => $row['id'], 'root_text' => $root]);
        $inserted++;
    }
}

echo "inserted root tags: $inserted\n";

$groups = db_count("SELECT COUNT(*) FROM (SELECT root_text FROM word_roots GROUP BY root_text HAVING COUNT(*) >= 3) t");
echo "root groups with >=3 words: $groups\n";
