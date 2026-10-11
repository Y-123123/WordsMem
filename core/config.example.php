<?php
/**
 * WordsMem — configuration template / 配置模板
 *
 * Copy this file to `config.php` and edit it. `config.php` is git-ignored, so
 * your credentials never end up in a commit.
 * 复制本文件为 `config.php` 后修改。`config.php` 已被 .gitignore 排除，
 * 密码不会被提交到仓库。
 *
 *     cp core/config.example.php core/config.php
 *
 * Secrets can also come from environment variables, which is safer on shared
 * hosting. 密码也支持从环境变量读取，在共享主机上更安全。
 */

// ── Identity / 站点标识 ───────────────────────────────────────────────
define('APP_NAME',    'WordsMem');       // shown in the navbar / 导航栏显示
define('APP_VERSION', '1.0.0');

// Base URL. Leave the auto-detect line alone unless the app sits behind a
// proxy that rewrites the Host header.
// 站点地址。除非用了会改写 Host 头的反向代理，否则保持自动探测。
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
// define('BASE_URL', 'https://words.example.org');   // or hard-code it / 或写死

// ── Database / 数据库 ────────────────────────────────────────────────
define('DB_HOST',    getenv('WORDSMEM_DB_HOST') ?: 'localhost');
define('DB_PORT',    getenv('WORDSMEM_DB_PORT') ?: '3306');
define('DB_NAME',    getenv('WORDSMEM_DB_NAME') ?: 'wordsmem');
define('DB_USER',    getenv('WORDSMEM_DB_USER') ?: 'wordsmem');
define('DB_PASS',    getenv('WORDSMEM_DB_PASS') ?: 'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

// ── Session / 会话 ───────────────────────────────────────────────────
define('SESSION_NAME',     'WORDSMEM_SESS');
define('SESSION_LIFETIME', 7200);   // seconds / 秒 (2 h)

// ── Registration / 注册 ──────────────────────────────────────────────
// Set to false once your users have signed up, to stop strangers creating
// accounts on a publicly reachable instance.
// 自己人都注册完之后改成 false，防止公网上的陌生人随便注册。
define('ALLOW_REGISTRATION', true);

// ── Pronunciation audio / 单词发音 ───────────────────────────────────
// 'browser' — use the browser's built-in speech synthesis. No network call,
//             no third-party service, works offline. This is the default.
// 'url'     — call an external text-to-speech endpoint. Put the full URL in
//             TTS_URL_TEMPLATE with {word} where the headword goes, and make
//             sure you are allowed to use that service.
// 'off'     — hide the pronunciation button entirely.
//
// 'browser' —— 用浏览器自带的语音合成，不联网、不依赖第三方、离线可用（默认）。
// 'url'     —— 调用外部 TTS 接口。在 TTS_URL_TEMPLATE 里填完整地址，{word}
//              是单词占位符。请自行确认你有权使用该服务。
// 'off'     —— 完全隐藏发音按钮。
define('TTS_MODE', 'browser');
define('TTS_URL_TEMPLATE', '');   // e.g. 'https://tts.example.org/say?text={word}'
