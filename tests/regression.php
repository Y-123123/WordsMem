<?php
// Run: php tests/regression.php (no database required).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../core/functions.php';
require __DIR__ . '/../core/auth.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$base = ['ease_factor' => 2.5, 'repetitions' => 0, 'interval_days' => 0, 'lapses' => 0];
$good = sm2_review($base, 4);
check($good['interval_days'] === 1 && $good['repetitions'] === 1, 'First successful review');
$second = sm2_review(array_merge($base, $good), 4);
check($second['interval_days'] === 6, 'Second successful review');
$forgot = sm2_review(array_merge($base, $second), 0);
check($forgot['repetitions'] === 0 && $forgot['lapses'] === 1, 'Forgotten card resets');

$queries = [];
function db_count(string $sql, array $params): int {
    return str_contains($sql, 'last_reviewed_at') ? 2 : 15;
}
function db_all(string $sql, array $params): array {
    global $queries;
    $queries[] = [$sql, $params];
    if (str_contains($sql, "status = 'new'")) {
        check(!str_contains($sql, 'DATE(created_at)'), 'Unfinished new cards survive midnight');
        return [['id' => 9]];
    }
    check(str_contains($sql, 'LIMIT 0'), 'Daily review budget is exhausted');
    return [];
}
$_SESSION = ['study_queue_date' => date('Y-m-d'), 'study_queue' => [999]];
$queue = ensure_study_queue(7, ['daily_review_limit' => 2, 'daily_new_words' => 15, 'word_bank_id' => 1]);
check($queue === [9], 'Stale session cards are removed');
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['csrf_token'] = str_repeat('a', 64);
$_POST['_csrf'] = $_SESSION['csrf_token'];
csrf_verify();
check(str_contains(pronounce_button_html('" onclick="alert(1)'), '&quot;'), 'Pronunciation escapes HTML');
echo "Regression checks passed.\n";
