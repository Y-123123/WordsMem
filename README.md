<div align="center">

# WordsMem · 单词记忆

**A self-hosted spaced-repetition vocabulary trainer. Bring your own word list.**
**自己部署的间隔重复背单词系统，词库自备。**

[English](#english) · [中文](#中文) · [Install](docs/INSTALL.md) · [Contributing](CONTRIBUTING.md)

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%2F%20MariaDB-00758f)
![License](https://img.shields.io/badge/code-MIT-green)
![No data](https://img.shields.io/badge/word%20data-not%20included-lightgrey)

</div>

---

## English

### What it is

A small PHP application that schedules vocabulary review using **SM-2**, the spaced-repetition algorithm behind SuperMemo and, for many years, Anki. You host it yourself; each user gets their own account, their own scheduling state, and their own display preferences.

It is built for people learning vocabulary from a list they already have — a syllabus, a textbook glossary, a set of words they collected themselves.

**Features**

- **SM-2 scheduling** — every word carries its own ease factor, interval and due date. Grade a card *Again / Hard / Good / Easy* and the next review moves accordingly.
- **Daily budget** — separate caps for new words and reviews, so a backlog never becomes unbounded.
- **Configurable cards** — show or hide phonetics, English definition, example sentence, root/affix note, synonyms, phrases. Reveal the answer automatically or on click.
- **Confusable-word warnings** — words in the same bank within a small edit distance (*deference / difference*) are surfaced on the card.
- **Subject categories** — a keyword classifier tags words by field (zoology, law, astronomy…) so you can revise by theme.
- **Root and affix groups** — words sharing a root are cross-linked, for anyone who learns morphologically.
- **Multiple word banks** — import as many lists as you like and switch between them.
- **Multi-user** — registration, login, per-user progress. Registration can be closed once your group has signed up.

No Composer, no npm, no build step. Bootstrap and its icons are vendored in `assets/`, so it runs offline and uploads to cheap shared hosting as-is.

### Word data: a demo list, with your own lists imported separately

**This repository includes a small demo word list, but no third-party dictionary or commercial word bank.** Import your own licensed lists for regular study.

Most freely circulating Chinese-English word banks are re-packaged from commercial dictionaries and vocabulary apps. They typically carry no licence at all, and their definitions, example sentences and mnemonics are somebody's copyrighted work. Bundling one into a public repository would republish that work, so this project does not do it.

What you get instead:

- **`database/schema.sql`** — the tables, no rows.
- **`scripts/import_words.php`** — a CSV/JSON importer, so any list you are entitled to use loads in one command.
- **`data/demo-wordlist.csv`** — 112 words written specifically for this repository so a fresh install has something to show. Definitions, example sentences, translations and root notes are original to this project and are released under the same MIT licence as the code.

When you import a list, **check what you are allowed to do with it.** A list you compiled yourself, a public-domain source such as WordNet, or something released under a clear licence is fine. Scraped dictionary content is not yours to redistribute, and putting it in a public fork of this repository would republish it under your name.

### Requirements

- PHP **8.1+** with `pdo_mysql` and `mbstring`
- MySQL **5.7+** or MariaDB **10.3+**
- Any web server (Apache, nginx, Caddy, or PHP's built-in server for a trial)

GitHub hosts the source code. This PHP/MySQL application requires a PHP server and cannot run on GitHub Pages.

### Quick start

```bash
git clone https://github.com/Y-123123/WordsMem.git
cd WordsMem

cp core/config.example.php core/config.php    # then edit the DB_* values

mysql -u root -p -e "CREATE DATABASE wordsmem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p wordsmem < database/schema.sql

php scripts/import_words.php data/demo-wordlist.csv "Demo list" demo
php scripts/extract_roots.php          # optional: build the root index
php scripts/classify_categories.php    # optional: tag words by subject

php -S localhost:8000                  # open http://localhost:8000 and register
```

Full instructions, including Apache/nginx and shared hosting, are in **[docs/INSTALL.md](docs/INSTALL.md)**.

### Importing your own list

The importer takes CSV or JSON. Only `headword` is required:

```csv
"headword","us_phone","translation_cn","definition_en","example_en","example_cn","root_note","synonyms"
"ubiquitous","juˈbɪkwətəs","[adj] 无处不在的","found everywhere","The assumption is so ubiquitous that nobody states it.","这个假设无处不在，以至于没人再明说。","ubi (where) → 到处都是","omnipresent|pervasive"
```

```bash
php scripts/import_words.php mylist.csv "Unit 1" unit-1
php scripts/import_words.php mylist.csv "Unit 1" unit-1 --replace   # wipe the bank first
php scripts/import_words.php mylist.json --dry-run                  # parse only, write nothing
```

Re-importing the same file updates the existing rows rather than duplicating them, so you can fix your list and run it again.

### Pronunciation

By default the pronunciation button uses the **browser's own speech synthesis** — no network request, no third-party service, works offline. If you would rather call an external text-to-speech endpoint, set `TTS_MODE` and `TTS_URL_TEMPLATE` in `core/config.php`, having first confirmed you are permitted to use that service. Set `TTS_MODE` to `'off'` to hide the button.

### Security notes

- `core/config.php` is git-ignored. Never commit it. Secrets can also come from environment variables (`WORDSMEM_DB_PASS` and friends).
- Passwords are bcrypt. Sessions are HttpOnly + SameSite=Lax and regenerate on login; five failed logins trigger a 30-second lockout.
- All queries are prepared statements; every state-changing form carries a CSRF token.
- `.htaccess` blocks direct web access to `core/` and `database/`. On nginx you must add the equivalent — see [docs/INSTALL.md](docs/INSTALL.md).
- Set `ALLOW_REGISTRATION` to `false` once your users have accounts, or anyone who finds the URL can create one.
- **Serve it over HTTPS.** Session cookies only get the `Secure` flag over TLS.

### Layout

```
core/          config, database, auth, shared functions, header/footer
database/      schema.sql — tables only, no rows
data/          demo-wordlist.csv — 112 original demo words
scripts/       import_words.php, extract_roots.php, classify_categories.php
assets/        vendored Bootstrap and Bootstrap Icons
*.php          login / register / dashboard / study / settings / categories / roots
```

### Licence

Code and the demo word list: **MIT** — see [LICENSE](LICENSE).
Anything you import is governed by whatever licence that source carries; this project makes no claim over it.

---

## 中文

### 这是什么

一个自己部署的背单词系统，用 **SM-2** 间隔重复算法安排复习 —— SuperMemo 的经典算法，Anki 多年来用的也是它。多用户，每人有自己的账号、自己的复习进度和自己的显示偏好。

它是给「已经有一份词表」的人用的：课程大纲、教材词汇表，或者自己整理的生词本。

**功能**

- **SM-2 排程** —— 每个词都有自己的难度系数、间隔天数和到期时间。选「忘记了 / 困难 / 记得 / 简单」，下次复习时间随之调整。
- **每日额度** —— 新词和复习分别限量，积压不会失控。
- **卡片可配置** —— 音标、英文释义、例句、词根词缀助记、同近义词、常用短语都可以单独开关；释义可以自动显示，也可以点了才显示。
- **易混词提醒** —— 同词库中编辑距离很近的词（如 deference / difference）会在卡片上提示。
- **学科分类** —— 关键词分类器按领域给单词打标签（动物学、法律、天文学…），可以按主题复习。
- **词根词缀分组** —— 共享词根的词互相串联，适合用词根词缀法记忆的人。
- **多词库** —— 想导入几份词表都行，随时切换。
- **多用户** —— 注册、登录、各自的进度。自己人注册完之后可以关闭注册。

不需要 Composer，不需要 npm，没有构建步骤。Bootstrap 和图标都在 `assets/` 里，断网能跑，上传到便宜的虚拟主机即可使用。

### 词库说明：附带示例词表，正式词库自行导入

**本仓库包含小型示例词表，不附带第三方词典或商业词库。** 正式学习所需词表请自行准备，并确认来源授权。

网上流传的中英词库，绝大多数是从商业词典和背单词 App 里扒出来重新打包的，通常没有任何授权说明，其释义、例句、助记都是别人享有版权的内容。把这样一份词库打包进公开仓库，等于替别人重新发布了这些内容，所以本项目不这么做。

作为替代，你会得到：

- **`database/schema.sql`** —— 只有表结构，没有数据。
- **`scripts/import_words.php`** —— CSV / JSON 导入工具，你有权使用的任何词表，一条命令就能导进来。
- **`data/demo-wordlist.csv`** —— 112 个专门为这个仓库编写的示例单词，让刚装好的系统有东西可看。其中的释义、例句、译文和词根注解都是本项目原创，与代码一样以 MIT 协议发布。

导入词表之前，**请先确认你有权使用它**。自己整理的词表、WordNet 这类公有领域来源，或者授权清晰的开源词库都没问题。从词典 App 扒来的内容不属于你，把它放进本仓库的公开 fork 里，等于用你的名义重新发布了它。

### 运行环境

- PHP **8.1+**，需要 `pdo_mysql` 和 `mbstring` 扩展
- MySQL **5.7+** 或 MariaDB **10.3+**
- 任意 Web 服务器（Apache、nginx、Caddy；先试试用 PHP 自带的也行）

GitHub 用于托管源代码；实际运行需要 PHP 与 MySQL 服务器，不能直接部署到 GitHub Pages。

### 快速开始

```bash
git clone https://github.com/Y-123123/WordsMem.git
cd WordsMem

cp core/config.example.php core/config.php    # 然后改里面的 DB_* 几行

mysql -u root -p -e "CREATE DATABASE wordsmem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p wordsmem < database/schema.sql

php scripts/import_words.php data/demo-wordlist.csv "示例词库" demo
php scripts/extract_roots.php          # 可选：建立词根索引
php scripts/classify_categories.php    # 可选：按学科打标签

php -S localhost:8000                  # 浏览器打开 http://localhost:8000 注册账号
```

完整步骤（含 Apache / nginx 配置和虚拟主机部署）见 **[docs/INSTALL.md](docs/INSTALL.md)**。

### 导入自己的词表

导入工具支持 CSV 和 JSON，只有 `headword` 一列是必填的：

```csv
"headword","us_phone","translation_cn","definition_en","example_en","example_cn","root_note","synonyms"
"ubiquitous","juˈbɪkwətəs","[adj] 无处不在的","found everywhere","The assumption is so ubiquitous that nobody states it.","这个假设无处不在，以至于没人再明说。","ubi (where) → 到处都是","omnipresent|pervasive"
```

```bash
php scripts/import_words.php mylist.csv "第一单元" unit-1
php scripts/import_words.php mylist.csv "第一单元" unit-1 --replace   # 先清空该词库
php scripts/import_words.php mylist.json --dry-run                    # 只解析不写库
```

同一份文件重复导入会更新已有记录，不会插入重复行，所以词表改完可以直接再导一次。

### 发音

发音按钮默认用**浏览器自带的语音合成** —— 不联网、不依赖第三方服务、离线可用。如果你想调用外部 TTS 接口，在 `core/config.php` 里设置 `TTS_MODE` 和 `TTS_URL_TEMPLATE`，前提是你确认自己有权使用该服务。把 `TTS_MODE` 设成 `'off'` 则完全隐藏发音按钮。

### 安全须知

- `core/config.php` 已被 `.gitignore` 排除，千万不要提交。密码也可以走环境变量（`WORDSMEM_DB_PASS` 等）。
- 密码用 bcrypt。Session 是 HttpOnly + SameSite=Lax，登录时重新生成 ID；连续登录失败 5 次锁 30 秒。
- 所有查询都是预处理语句；会改数据的表单都带 CSRF token。
- `.htaccess` 阻止从网页直接访问 `core/` 和 `database/`。nginx 不读 `.htaccess`，必须自己加等效规则，见 [docs/INSTALL.md](docs/INSTALL.md)。
- 自己人账号都建好之后，把 `ALLOW_REGISTRATION` 改成 `false`，否则任何知道网址的人都能注册。
- **请用 HTTPS。** 只有走 TLS，Session Cookie 才会带 `Secure` 标记。

### 目录结构

```
core/          配置、数据库、认证、公共函数、页头页尾
database/      schema.sql —— 只有表结构，没有数据
data/          demo-wordlist.csv —— 112 个原创示例单词
scripts/       import_words.php、extract_roots.php、classify_categories.php
assets/        本地化的 Bootstrap 与 Bootstrap Icons
*.php          登录 / 注册 / 概览 / 背单词 / 设置 / 分类词汇 / 词根词缀
```

### 开源协议

代码和示例词表：**MIT**，见 [LICENSE](LICENSE)。
你自己导入的词表适用其来源本身的授权，本项目对其不主张任何权利。

---

<div align="center">
<sub>Issues and pull requests welcome — see <a href="CONTRIBUTING.md">CONTRIBUTING.md</a>.<br>
欢迎提 Issue 和 PR，详见 <a href="CONTRIBUTING.md">CONTRIBUTING.md</a>。</sub>
</div>
