<?php
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function e(?string $s): string {
    return h($s ?? '');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function flash_html(): string {
    $html = '';
    foreach (get_flashes() as $f) {
        $cls = match ($f['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        $html .= '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
               . e($f['msg'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    return $html;
}

/**
 * Pronunciation button for a headword.
 *
 * Which mechanism is used comes from TTS_MODE in core/config.php:
 *   'browser' — the browser's own speech synthesis. No network request and no
 *               third-party service, so it works offline and raises no
 *               terms-of-use questions. This is the default.
 *   'url'     — an external text-to-speech endpoint you configure yourself in
 *               TTS_URL_TEMPLATE, with {word} standing in for the headword.
 *   'off'     — no button at all.
 *
 * 发音按钮。用哪种方式由 core/config.php 里的 TTS_MODE 决定：
 *   'browser' 浏览器自带语音合成，不联网、不依赖第三方，离线可用（默认）；
 *   'url'     你自己配置的外部 TTS 接口，地址写在 TTS_URL_TEMPLATE，{word} 是占位符；
 *   'off'     不显示按钮。
 */
function pronounce_button_html(string $headword): string
{
    $mode = defined('TTS_MODE') ? TTS_MODE : 'browser';
    if ($mode === 'off') {
        return '';
    }

    $label = '<i class="bi bi-volume-up"></i> 发音';

    if ($mode === 'url') {
        $tpl = defined('TTS_URL_TEMPLATE') ? TTS_URL_TEMPLATE : '';
        if ($tpl === '') {
            return '';
        }
        $url = str_replace('{word}', rawurlencode($headword), $tpl);
        $js  = 'new Audio(' . json_encode($url, JSON_UNESCAPED_SLASHES) . ').play()';
    } else {
        // Browser speech synthesis — no external call.
        $js = 'if(window.speechSynthesis){var u=new SpeechSynthesisUtterance('
            . json_encode($headword, JSON_UNESCAPED_UNICODE)
            . ');u.lang="en-US";speechSynthesis.cancel();speechSynthesis.speak(u);}';
    }

    return '<button type="button" class="btn btn-link" onclick="' . h($js) . '">' . $label . '</button>';
}

/**
 * Classic SM-2 algorithm (SuperMemo, 1987) — the same scheduling logic
 * that powered SuperMemo and Anki for decades before FSRS.
 *
 * Grade scale: 0 = Again (forgot), 3 = Hard, 4 = Good, 5 = Easy.
 */
function sm2_review(array $state, int $grade): array {
    $easeFactor   = (float) $state['ease_factor'];
    $repetitions  = (int) $state['repetitions'];
    $intervalDays = (int) $state['interval_days'];
    $lapses       = (int) $state['lapses'];

    if ($grade < 3) {
        $repetitions  = 0;
        $intervalDays = 1;
        $lapses += 1;
        $status = 'learning';
    } else {
        $easeFactor = max(1.3, $easeFactor + (0.1 - (5 - $grade) * (0.08 + (5 - $grade) * 0.02)));

        $intervalDays = match ($repetitions) {
            0 => 1,
            1 => 6,
            default => (int) round($intervalDays * $easeFactor),
        };

        $repetitions += 1;
        $status = 'review';
    }

    return [
        'status'          => $status,
        'ease_factor'     => $easeFactor,
        'interval_days'   => $intervalDays,
        'repetitions'     => $repetitions,
        'lapses'          => $lapses,
        'last_grade'      => $grade,
        'last_reviewed_at' => date('Y-m-d H:i:s'),
        'due_at'          => date('Y-m-d H:i:s', strtotime("+{$intervalDays} days")),
    ];
}

/**
 * Find words in the same bank that are easy to confuse with $headword
 * (e.g. deference/difference, affect/effect) using Levenshtein edit
 * distance over a small candidate set — same starting letter and similar
 * length — rather than scanning the whole word bank.
 */
function find_similar_words(string $headword, int $wordBankId, int $excludeId, int $limit = 3): array {
    $needle = strtolower(trim($headword));
    $len = strlen($needle);
    if ($len < 4) {
        return [];
    }

    $minLen = max(2, $len - 2);
    $maxLen = $len + 2;

    $candidates = db_all(
        "SELECT id, headword, translation_cn FROM words
         WHERE word_bank_id = ? AND id != ? AND LEFT(LOWER(headword), 1) = ?
           AND CHAR_LENGTH(headword) BETWEEN ? AND ?
         LIMIT 500",
        [$wordBankId, $excludeId, $needle[0], $minLen, $maxLen]
    );

    $scored = [];
    foreach ($candidates as $c) {
        $other = strtolower($c['headword']);
        if ($other === $needle) {
            continue;
        }
        $dist = levenshtein($needle, $other);
        if ($dist >= 1 && $dist <= 2) {
            $scored[] = [
                'id'             => $c['id'],
                'headword'       => $c['headword'],
                'translation_cn' => $c['translation_cn'],
                'distance'       => $dist,
            ];
        }
    }

    usort($scored, fn ($a, $b) => $a['distance'] <=> $b['distance']);

    return array_slice($scored, 0, $limit);
}

/** Categories (学科分类) and roots (词根词缀) this word belongs to — for the
 * "语义网络" cross-reference block shown on the study card. */
function word_semantic_tags(int $wordId): array {
    $categories = db_all(
        'SELECT c.id, c.name, c.slug FROM categories c
         JOIN word_categories wc ON wc.category_id = c.id
         WHERE wc.word_id = ?',
        [$wordId]
    );
    $roots = db_all(
        "SELECT DISTINCT wr.root_text
         FROM word_roots wr
         WHERE wr.word_id = ?
           AND (SELECT COUNT(*) FROM word_roots wr2 WHERE wr2.root_text = wr.root_text) >= 3",
        [$wordId]
    );

    return ['categories' => $categories, 'roots' => array_column($roots, 'root_text')];
}

function get_or_create_settings(int $userId): array {
    $setting = db_one('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);
    if ($setting) {
        return $setting;
    }

    $defaultBank = db_one('SELECT id FROM word_banks ORDER BY id LIMIT 1');
    db_insert('user_settings', [
        'user_id'      => $userId,
        'word_bank_id' => $defaultBank['id'] ?? null,
    ]);

    return db_one('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);
}

/**
 * Build (or resume) today's study queue: due reviews first, then
 * freshly introduced new words up to the user's daily limit.
 * Queue of user_word_states.id is kept in the PHP session for the day.
 */
function ensure_study_queue(int $userId, array $setting): array {
    $today = date('Y-m-d');

    // Reset the tracked queue when the day rolls over; otherwise keep
    // whatever hasn't been reviewed yet so we can top it up below.
    if (($_SESSION['study_queue_date'] ?? null) !== $today) {
        $_SESSION['study_queue'] = [];
        $_SESSION['study_queue_date'] = $today;
    }
    // Rebuild from database state so another session cannot leave stale cards.
    $reviewedToday = db_count(
        'SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND DATE(last_reviewed_at) = ? AND DATE(created_at) < ?',
        [$userId, $today, $today]
    );
    $reviewLimit = max(0, (int) $setting['daily_review_limit'] - $reviewedToday);
    $dueIds = array_column(db_all(
        "SELECT id FROM user_word_states
         WHERE user_id = ? AND status IN ('review','learning') AND due_at <= NOW()
         ORDER BY due_at LIMIT $reviewLimit",
        [$userId]
    ), 'id');

    $newIntroducedToday = db_count(
        'SELECT COUNT(*) FROM user_word_states WHERE user_id = ? AND DATE(created_at) = ?',
        [$userId, $today]
    );

    $remainingNewSlots = max(0, (int) $setting['daily_new_words'] - $newIntroducedToday);

    if ($remainingNewSlots > 0 && !empty($setting['word_bank_id'])) {
        $knownWordIds = array_column(
            db_all('SELECT word_id FROM user_word_states WHERE user_id = ?', [$userId]),
            'word_id'
        );

        $placeholders = $knownWordIds ? implode(',', array_fill(0, count($knownWordIds), '?')) : null;
        $sql = "SELECT id FROM words WHERE word_bank_id = ?"
             . ($placeholders ? " AND id NOT IN ($placeholders)" : '')
             . " ORDER BY `rank` LIMIT " . (int) $remainingNewSlots;
        $params = array_merge([$setting['word_bank_id']], $knownWordIds);
        $freshWords = db_all($sql, $params);

        foreach ($freshWords as $word) {
            db_insert('user_word_states', [
                'user_id' => $userId,
                'word_id' => $word['id'],
                'status'  => 'new',
                'due_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    $newIds = array_column(db_all(
        "SELECT id FROM user_word_states
         WHERE user_id = ? AND status = 'new'
         ORDER BY id",
        [$userId]
    ), 'id');

    // Include unfinished new cards from earlier days as well as due reviews.
    $queue = array_values(array_unique(array_merge($dueIds, $newIds)));

    $_SESSION['study_queue'] = $queue;

    return $queue;
}
