<?php
/**
 * WordsMem — single entry point for every page.
 * WordsMem —— 所有页面的统一入口。
 *
 * Loads configuration, database helpers, authentication and shared functions,
 * in that order. Every page starts with:
 * 依次载入配置、数据库、认证、公共函数。每个页面开头写：
 *
 *     require_once __DIR__ . '/core/bootstrap.php';
 */

$__cfg = __DIR__ . '/config.php';

if (!is_file($__cfg)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>WordsMem — configuration missing</title>
<style>
 body{font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;max-width:44rem;
      margin:4rem auto;padding:0 1.5rem;color:#222}
 h1{font-size:1.4rem;margin-bottom:.2rem} p.sub{color:#666;margin-top:0}
 code,pre{background:#f4f4f6;border-radius:4px}
 code{padding:.1rem .35rem} pre{padding:.8rem 1rem;overflow-x:auto}
 hr{border:0;border-top:1px solid #e5e5e8;margin:2rem 0}
</style></head><body>
<h1>Configuration missing</h1>
<p class="sub">WordsMem cannot start until <code>core/config.php</code> exists.</p>
<p>Copy the template and fill in your database credentials:</p>
<pre>cp core/config.example.php core/config.php</pre>
<p>Then create the database and load the schema — see <code>docs/INSTALL.md</code>.</p>
<hr>
<h1>缺少配置文件</h1>
<p class="sub">需要先创建 <code>core/config.php</code>，WordsMem 才能启动。</p>
<p>复制模板并填入你的数据库信息：</p>
<pre>cp core/config.example.php core/config.php</pre>
<p>然后建库并导入表结构 —— 详见 <code>docs/INSTALL.md</code>。</p>
</body></html>
HTML;
    exit;
}

require_once $__cfg;
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
