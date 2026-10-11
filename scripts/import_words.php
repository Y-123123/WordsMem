<?php
/**
 * WordsMem — word list importer / 词表导入工具
 *
 * Loads a CSV or JSON word list into a word bank, creating the bank if it does
 * not exist yet. Re-running with the same file updates the existing rows
 * instead of duplicating them, so it is safe to fix your list and import again.
 *
 * 把 CSV 或 JSON 词表导入某个词库，词库不存在时自动创建。同一份文件重复导入
 * 会更新已有记录而不是插入重复行，所以改完词表可以直接再导一次。
 *
 * Usage / 用法:
 *   php scripts/import_words.php <file> [bank name] [bank slug]
 *
 *   php scripts/import_words.php data/demo-wordlist.csv
 *   php scripts/import_words.php mylist.csv "My exam list" my-exam
 *   php scripts/import_words.php mylist.json "Unit 1" unit-1 --replace
 *
 * Options / 选项:
 *   --replace   empty the bank before importing / 导入前清空该词库
 *   --dry-run   parse and report, write nothing / 只解析和报告，不写库
 *
 * ── CSV format / CSV 格式 ────────────────────────────────────────────────
 * First row is a header. Only `headword` is required; every other column is
 * optional and may be left blank or omitted entirely.
 * 第一行是表头。只有 `headword` 是必填的，其余列都可以留空或整列不写。
 *
 *   headword,us_phone,uk_phone,translation_cn,definition_en,example_en,
 *   example_cn,root_note,synonyms,phrases,rank
 *
 *   synonyms — separated by | or ; e.g.  crime|guilt|offence
 *              用 | 或 ; 分隔
 *   phrases  — "english == 中文" pairs, separated by |
 *              「英文 == 中文」成对，用 | 分隔
 *   rank     — display / introduction order; defaults to file order
 *              学习顺序，不填就按文件里的先后顺序
 *
 * ── JSON format / JSON 格式 ─────────────────────────────────────────────
 * An array of objects using the same field names; `synonyms` may be a real
 * array and `phrases` an array of {en, cn} objects.
 * 一个对象数组，字段名相同；`synonyms` 可以直接是数组，`phrases` 可以是
 * {en, cn} 对象的数组。
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("import_words.php must be run from the command line.\n"
       . "import_words.php 只能在命令行下运行。\n");
}

require __DIR__ . '/../core/bootstrap.php';

$argv    = $_SERVER['argv'];
$flags   = array_values(array_filter($argv, fn($a) => str_starts_with($a, '--')));
$args    = array_values(array_filter(array_slice($argv, 1), fn($a) => !str_starts_with($a, '--')));
$replace = in_array('--replace', $flags, true);
$dryRun  = in_array('--dry-run', $flags, true);

$file = $args[0] ?? '';
if ($file === '') {
    fwrite(STDERR, "Usage: php scripts/import_words.php <file.csv|file.json> [bank name] [bank slug]\n");
    exit(1);
}
if (!is_file($file)) {
    fwrite(STDERR, "File not found / 找不到文件: {$file}\n");
    exit(1);
}

$bankName = $args[1] ?? pathinfo($file, PATHINFO_FILENAME);
$bankSlug = $args[2] ?? preg_replace('/[^a-z0-9]+/', '-', strtolower($bankName));
$bankSlug = trim($bankSlug, '-') ?: 'imported';

// ── Parse ────────────────────────────────────────────────────────────────
$FIELDS = ['headword', 'us_phone', 'uk_phone', 'translation_cn', 'definition_en',
           'example_en', 'example_cn', 'root_note', 'synonyms', 'phrases', 'rank'];

function split_list(string $s): array
{
    $parts = preg_split('/\s*[|;]\s*/u', trim($s), -1, PREG_SPLIT_NO_EMPTY);
    return array_values($parts ?: []);
}

function parse_phrases(string $s): array
{
    $out = [];
    foreach (split_list($s) as $item) {
        $bits = preg_split('/\s*==\s*/u', $item, 2);
        $out[] = ['en' => trim($bits[0]), 'cn' => trim($bits[1] ?? '')];
    }
    return $out;
}

$rows = [];
$skipped = [];

if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'json') {
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        fwrite(STDERR, "Could not parse JSON / JSON 解析失败: " . json_last_error_msg() . "\n");
        exit(1);
    }
    foreach ($data as $i => $item) {
        $head = trim((string) ($item['headword'] ?? ''));
        if ($head === '') { $skipped[] = "#" . ($i + 1) . " no headword"; continue; }
        $row = ['headword' => $head];
        foreach ($FIELDS as $f) {
            if ($f === 'headword') continue;
            $row[$f] = $item[$f] ?? null;
        }
        $row['synonyms'] = is_array($row['synonyms'])
            ? $row['synonyms']
            : split_list((string) ($row['synonyms'] ?? ''));
        $row['phrases'] = is_array($row['phrases'])
            ? $row['phrases']
            : parse_phrases((string) ($row['phrases'] ?? ''));
        $rows[] = $row;
    }
} else {
    $fh = fopen($file, 'r');
    // Tolerate a UTF-8 BOM written by Excel.
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($fh);

    $header = fgetcsv($fh, 0, ',', '"', '');
    if (!$header) {
        fwrite(STDERR, "The file is empty / 文件是空的。\n");
        exit(1);
    }
    $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);
    if (!in_array('headword', $header, true)) {
        fwrite(STDERR, "The header row must contain a `headword` column.\n"
                     . "表头必须有 `headword` 这一列。\nFound: " . implode(', ', $header) . "\n");
        exit(1);
    }

    $lineNo = 1;
    while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        $lineNo++;
        if (count(array_filter($line, fn($v) => trim((string) $v) !== '')) === 0) continue;

        $assoc = [];
        foreach ($header as $i => $name) {
            $assoc[$name] = isset($line[$i]) ? trim((string) $line[$i]) : '';
        }
        if (($assoc['headword'] ?? '') === '') { $skipped[] = "line {$lineNo}: no headword"; continue; }

        $row = [];
        foreach ($FIELDS as $f) {
            $row[$f] = $assoc[$f] ?? '';
        }
        $row['synonyms'] = split_list((string) $row['synonyms']);
        $row['phrases']  = parse_phrases((string) $row['phrases']);
        $rows[] = $row;
    }
    fclose($fh);
}

printf("Parsed %d word(s) from %s\n", count($rows), $file);
if ($skipped) {
    printf("Skipped %d row(s):\n", count($skipped));
    foreach (array_slice($skipped, 0, 10) as $s) echo "  - {$s}\n";
    if (count($skipped) > 10) echo "  … and " . (count($skipped) - 10) . " more\n";
}
if (!$rows) {
    fwrite(STDERR, "Nothing to import / 没有可导入的内容。\n");
    exit(1);
}

if ($dryRun) {
    echo "\n--dry-run: nothing written. First 3 parsed rows / 前 3 条解析结果:\n";
    foreach (array_slice($rows, 0, 3) as $r) {
        echo "  " . json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }
    exit(0);
}

// ── Write ────────────────────────────────────────────────────────────────
try {
    db()->query('SELECT 1 FROM word_banks LIMIT 1');
} catch (PDOException $e) {
    fwrite(STDERR, "The tables are missing. Load the schema first / 请先导入表结构:\n"
                 . "  mysql -u root -p " . DB_NAME . " < database/schema.sql\n");
    exit(1);
}

$bank = db_one('SELECT * FROM word_banks WHERE slug = ?', [$bankSlug]);
if ($bank) {
    printf("Using existing bank / 使用已有词库: %s (slug=%s, id=%d)\n", $bank['name'], $bank['slug'], $bank['id']);
} else {
    $bankId = db_insert('word_banks', [
        'name'       => $bankName,
        'slug'       => $bankSlug,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $bank = db_one('SELECT * FROM word_banks WHERE id = ?', [$bankId]);
    printf("Created bank / 已创建词库: %s (slug=%s, id=%d)\n", $bank['name'], $bank['slug'], $bank['id']);
}
$bankId = (int) $bank['id'];

if ($replace) {
    db_query('DELETE FROM words WHERE word_bank_id = ?', [$bankId]);
    echo "--replace: emptied the bank first / 已先清空该词库\n";
}

$now = date('Y-m-d H:i:s');
$sql = 'INSERT INTO words
          (word_bank_id, `rank`, headword, us_phone, uk_phone, translation_cn,
           definition_en, example_en, example_cn, root_note, synonyms, phrases,
           created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          `rank`=VALUES(`rank`), us_phone=VALUES(us_phone), uk_phone=VALUES(uk_phone),
          translation_cn=VALUES(translation_cn), definition_en=VALUES(definition_en),
          example_en=VALUES(example_en), example_cn=VALUES(example_cn),
          root_note=VALUES(root_note), synonyms=VALUES(synonyms),
          phrases=VALUES(phrases), updated_at=VALUES(updated_at)';

$stmt = db()->prepare($sql);
$n = 0;
db()->beginTransaction();
foreach ($rows as $i => $r) {
    $blank = fn($v) => ($v === '' || $v === null) ? null : $v;
    $stmt->execute([
        $bankId,
        ($r['rank'] === '' || $r['rank'] === null) ? $i + 1 : (int) $r['rank'],
        $r['headword'],
        $blank($r['us_phone']),
        $blank($r['uk_phone']),
        $blank($r['translation_cn']),
        $blank($r['definition_en']),
        $blank($r['example_en']),
        $blank($r['example_cn']),
        $blank($r['root_note']),
        $r['synonyms'] ? json_encode(array_values($r['synonyms']), JSON_UNESCAPED_UNICODE) : null,
        $r['phrases']  ? json_encode(array_values($r['phrases']),  JSON_UNESCAPED_UNICODE) : null,
        $now, $now,
    ]);
    $n++;
}
db()->commit();

$total = db_count('SELECT COUNT(*) FROM words WHERE word_bank_id = ?', [$bankId]);
printf("\nImported %d word(s). Bank '%s' now holds %d.\n", $n, $bank['name'], $total);
printf("已导入 %d 条，词库「%s」现有 %d 词。\n\n", $n, $bank['name'], $total);
echo "Optional next steps / 可选的后续步骤:\n";
echo "  php scripts/extract_roots.php        # build the roots index / 建立词根索引\n";
echo "  php scripts/classify_categories.php  # tag words by subject / 按学科打标签\n";
