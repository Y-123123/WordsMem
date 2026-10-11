# Installation · 安装部署

[English](#english) · [中文](#中文)

---

## English

### 1. Requirements

| | Minimum | Notes |
|---|---|---|
| PHP | 8.1 | extensions: `pdo_mysql`, `mbstring` |
| MySQL | 5.7 | or MariaDB 10.3+ (the `words` table uses a JSON column) |
| Web server | any | Apache, nginx, Caddy, or `php -S` for a trial |

```bash
php -v
php -m | grep -E 'pdo_mysql|mbstring'
mysql --version
```

### 2. Get the code

```bash
git clone https://github.com/Y-123123/WordsMem.git
cd WordsMem
```

### 3. Create the database

```sql
CREATE DATABASE wordsmem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wordsmem'@'localhost' IDENTIFIED BY 'pick-a-real-password';
GRANT ALL PRIVILEGES ON wordsmem.* TO 'wordsmem'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Configure

```bash
cp core/config.example.php core/config.php
```

Edit it. At minimum set `DB_NAME`, `DB_USER`, `DB_PASS`. While you are there:

| Setting | What it does |
|---|---|
| `APP_NAME` | Shown in the navigation bar |
| `BASE_URL` | Leave the auto-detect line unless you sit behind a proxy that rewrites `Host` |
| `ALLOW_REGISTRATION` | `false` closes signup — do this once your users have accounts |
| `TTS_MODE` | `'browser'` (default, no network), `'url'` (your own endpoint), `'off'` |

On shared hosting prefer environment variables over literals — every secret in the template reads `getenv()` first. `core/config.php` is git-ignored.

### 5. Load the schema

```bash
mysql -u wordsmem -p wordsmem < database/schema.sql
```

Eight tables, no rows. The file is idempotent (`CREATE TABLE IF NOT EXISTS`).

### 6. Import a word list

Nothing appears in the app until a word bank exists.

```bash
php scripts/import_words.php data/demo-wordlist.csv "Demo list" demo
```

Then, optionally, build the two semantic indexes:

```bash
php scripts/extract_roots.php          # groups words that share a root/affix
php scripts/classify_categories.php    # tags words by subject area
```

Both read the words already in the database and can be re-run any time — after importing a new bank, for example. `classify_categories.php` rebuilds all tags from scratch each run.

> The demo list is abstract academic vocabulary, so only a dozen or so words land in a subject category. With a large, concrete word bank the categories page fills out properly.

**Your own list.** CSV or JSON; only `headword` is required. Recognised columns:

| Column | Meaning |
|---|---|
| `headword` | the word — **required** |
| `us_phone` / `uk_phone` | phonetic transcriptions |
| `translation_cn` | translation / gloss in your language |
| `definition_en` | English definition |
| `example_en` / `example_cn` | example sentence and its translation |
| `root_note` | root/affix mnemonic — feeds `extract_roots.php` |
| `synonyms` | separated by `\|` or `;` |
| `phrases` | `english == 中文` pairs, separated by `\|` |
| `rank` | introduction order; defaults to file order |

```bash
php scripts/import_words.php mylist.csv "Unit 1" unit-1
php scripts/import_words.php mylist.csv "Unit 1" unit-1 --replace
php scripts/import_words.php mylist.json --dry-run
```

A UTF-8 BOM (what Excel writes) is handled. Re-importing updates rows in place rather than duplicating them.

### 7. Serve it

**Trial run:**

```bash
php -S localhost:8000
```

Open the site and register — the first account is created through the normal signup form. There is no separate installer.

**Apache** — document root at the project folder. The bundled `.htaccess` sets security headers and blocks `core/` and `database/`; make sure `AllowOverride All` is on and `mod_headers` and `mod_rewrite` are enabled.

```apache
<VirtualHost *:80>
    ServerName words.example.org
    DocumentRoot /var/www/wordsmem
    <Directory /var/www/wordsmem>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**nginx** — `.htaccess` is ignored, so the protection it provides must be recreated. **Do not skip the `core/` and `database/` blocks**: without them `core/config.php` is not executed but `database/schema.sql` *is* downloadable.

```nginx
server {
    listen 80;
    server_name words.example.org;
    root /var/www/wordsmem;
    index index.php;

    add_header X-Frame-Options        "SAMEORIGIN"  always;
    add_header X-Content-Type-Options "nosniff"     always;
    add_header X-Robots-Tag           "noindex, nofollow, noarchive, nosnippet" always;

    location / { try_files $uri $uri/ =404; }

    # Keep this rule BEFORE the PHP location: nginx uses the first regex match.
    location ~ ^/(core|database|scripts|data|tests|\.git)(/|$) { return 403; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

}
```

**Shared hosting (cPanel / hPanel / Plesk)** — upload the folder to `public_html`, create the database in the control panel, then do steps 4–6. Without SSH: load `database/schema.sql` through phpMyAdmin's Import tab, and import your word list by converting it to SQL locally first —

```bash
php scripts/import_words.php mylist.csv "Unit 1" unit-1   # against a local DB
mysqldump -u root -p --no-create-info wordsmem word_banks words > words.sql
```

— then import `words.sql` through phpMyAdmin.

### 8. Before you go live

- [ ] HTTPS enabled (session cookies only get `Secure` over TLS)
- [ ] `ALLOW_REGISTRATION` set to `false` once your users are signed up
- [ ] A real password on the database user
- [ ] `core/`, `database/`, `scripts/` and `data/` not reachable over the web
- [ ] You have checked that you are entitled to redistribute whatever word list you imported

### Troubleshooting

**"Configuration missing" page** — `core/config.php` does not exist. Run step 4.

**"Cannot connect to the database"** — wrong `DB_*` values, or MySQL is not running:
```bash
mysql -u wordsmem -p -e "SELECT 1" wordsmem
```

**The study page says today's work is done, immediately** — no word bank exists, or the bank you selected is empty. Import a list (step 6), then check Settings → 词库.

**Nothing on the categories or roots pages** — those tables are built by the two scripts in step 6; run them after importing.

**Chinese shows as `???`** — the database is not utf8mb4. Check with `SHOW CREATE DATABASE wordsmem;`.

**Blank page** — turn errors on temporarily:
```bash
php -d display_errors=1 -d error_reporting=E_ALL -S localhost:8000
```

---

## 中文

### 1. 运行环境

| | 最低要求 | 说明 |
|---|---|---|
| PHP | 8.1 | 需要 `pdo_mysql`、`mbstring` 扩展 |
| MySQL | 5.7 | 或 MariaDB 10.3+（`words` 表用到 JSON 字段） |
| Web 服务器 | 任意 | Apache、nginx、Caddy；先试试用 `php -S` 也行 |

### 2. 拿到代码

```bash
git clone https://github.com/Y-123123/WordsMem.git
cd WordsMem
```

### 3. 建数据库

```sql
CREATE DATABASE wordsmem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wordsmem'@'localhost' IDENTIFIED BY '换成一个真密码';
GRANT ALL PRIVILEGES ON wordsmem.* TO 'wordsmem'@'localhost';
FLUSH PRIVILEGES;
```

### 4. 改配置

```bash
cp core/config.example.php core/config.php
```

至少要填 `DB_NAME`、`DB_USER`、`DB_PASS`。顺便看看这几项：

| 配置项 | 作用 |
|---|---|
| `APP_NAME` | 显示在导航栏 |
| `BASE_URL` | 除非用了会改写 `Host` 头的反向代理，否则保持自动探测 |
| `ALLOW_REGISTRATION` | 改成 `false` 即关闭注册 —— 自己人注册完就该关 |
| `TTS_MODE` | `'browser'`（默认，不联网）、`'url'`（自己的接口）、`'off'` |

共享主机上建议用环境变量，模板里每个密码项都先读 `getenv()`。`core/config.php` 已被 git 忽略。

### 5. 导入表结构

```bash
mysql -u wordsmem -p wordsmem < database/schema.sql
```

八张表，没有数据。脚本是幂等的，重复执行没有副作用。

### 6. 导入词表

**没有词库，系统里什么都不会显示。**

```bash
php scripts/import_words.php data/demo-wordlist.csv "示例词库" demo
```

然后（可选）建立两个语义索引：

```bash
php scripts/extract_roots.php          # 把共享词根的词分到一组
php scripts/classify_categories.php    # 按学科给单词打标签
```

这两个脚本读的是数据库里已有的单词，随时可以重跑 —— 比如导入新词库之后。`classify_categories.php` 每次运行都会重建全部标签。

> 示例词表是抽象的学术词汇，因此只有十几个词能落进学科分类。换成量大、具体的词库，分类页面就会填满。

**导入自己的词表。** CSV 或 JSON，只有 `headword` 必填。可识别的列：

| 列名 | 含义 |
|---|---|
| `headword` | 单词 —— **必填** |
| `us_phone` / `uk_phone` | 美式 / 英式音标 |
| `translation_cn` | 中文释义 |
| `definition_en` | 英文释义 |
| `example_en` / `example_cn` | 例句及其译文 |
| `root_note` | 词根词缀助记 —— `extract_roots.php` 从这里解析 |
| `synonyms` | 用 `\|` 或 `;` 分隔 |
| `phrases` | 「英文 == 中文」成对，用 `\|` 分隔 |
| `rank` | 学习顺序，不填就按文件先后 |

```bash
php scripts/import_words.php mylist.csv "第一单元" unit-1
php scripts/import_words.php mylist.csv "第一单元" unit-1 --replace
php scripts/import_words.php mylist.json --dry-run
```

Excel 存出来的 UTF-8 BOM 会被自动处理。同一份文件重复导入是更新，不会产生重复行。

### 7. 跑起来

**先试试：**

```bash
php -S localhost:8000
```

打开网站直接注册 —— 第一个账号就走正常注册流程，没有单独的安装程序。

**Apache** —— DocumentRoot 指到项目目录。自带的 `.htaccess` 负责安全响应头，并阻止访问 `core/` 和 `database/`；需要 `AllowOverride All`，并启用 `mod_headers` 和 `mod_rewrite`。配置见上面英文部分。

**nginx** —— nginx 不读 `.htaccess`，它提供的保护必须自己写。**`core/` 和 `database/` 这段千万别省**：不加的话 `core/config.php` 虽然会被 PHP 执行，但 `database/schema.sql` 是可以直接下载的。

```nginx
location ~ ^/(core|database|scripts|data|tests|\.git)(/|$) { return 403; }
```

完整的 server 块见上面英文部分。

**共享主机（cPanel / hPanel / 宝塔）** —— 把目录上传到 `public_html`，在控制面板建数据库，然后做第 4–6 步。没有 SSH 的话：`database/schema.sql` 用 phpMyAdmin 的「导入」执行；词表则先在本地导入，再导出成 SQL 上传 ——

```bash
php scripts/import_words.php mylist.csv "第一单元" unit-1   # 在本地库上执行
mysqldump -u root -p --no-create-info wordsmem word_banks words > words.sql
```

—— 然后在 phpMyAdmin 里导入 `words.sql`。

### 8. 上线前检查

- [ ] 已启用 HTTPS（只有走 TLS，Session Cookie 才会带 `Secure`）
- [ ] 自己人账号建好后，`ALLOW_REGISTRATION` 已改成 `false`
- [ ] 数据库用户设了强密码
- [ ] `core/`、`database/`、`scripts/`、`data/` 无法从网页访问
- [ ] 你已确认自己有权重新分发导入的那份词表

### 常见问题

**打开显示「缺少配置文件」** —— `core/config.php` 还没建，回到第 4 步。

**「无法连接数据库」** —— `DB_*` 填错了，或者 MySQL 没启动：
```bash
mysql -u wordsmem -p -e "SELECT 1" wordsmem
```

**一进背单词页就说今天学完了** —— 没有词库，或者选中的词库是空的。先做第 6 步导入词表，再去「设置 → 词库」里确认选的是哪一个。

**分类词汇 / 词根词缀页面是空的** —— 这两张表由第 6 步的两个脚本生成，导完词表后跑一遍。

**中文显示成 `???`** —— 数据库不是 utf8mb4，执行 `SHOW CREATE DATABASE wordsmem;` 确认。

**白屏** —— 临时打开报错：
```bash
php -d display_errors=1 -d error_reporting=E_ALL -S localhost:8000
```
