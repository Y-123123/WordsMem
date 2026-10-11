# Contributing · 参与贡献

[English](#english) · [中文](#中文)

---

## English

### The one hard rule

**Do not submit vocabulary data.** No word lists, no dictionary dumps, no "here is a 20 000-word exam vocabulary bank I found on GitHub". Almost all circulating Chinese-English word banks are repackaged commercial dictionary content with no licence, and a pull request adding one would be rejected regardless of how useful it is.

The only exception is a list you wrote yourself, or one under a clear licence that permits redistribution — and even then, open an issue first so the licensing can be recorded properly in `LICENSE`.

Everything else — bug fixes, features, importer formats, translations of the interface — is welcome.

### Before you open a pull request

There is no build step and no test suite yet, so the bar is: **it lints and it runs.**

```bash
# 1. every file parses
find . -name '*.php' -not -path './assets/*' -exec php -l {} \; | grep -v 'No syntax errors'

# 2. a clean install still works end to end
mysql -u root -p -e "DROP DATABASE IF EXISTS wm_test; CREATE DATABASE wm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p wm_test < database/schema.sql
php scripts/import_words.php data/demo-wordlist.csv "Demo" demo
php scripts/extract_roots.php
php scripts/classify_categories.php
php -S localhost:8000     # register, study a few cards, check settings
```

### Reporting a bug

Include your PHP version, MySQL/MariaDB version, and what you saw versus what you expected. If a page errored, run it with `display_errors=1` and paste the message. Do not paste rows from your word bank into an issue.

### Code style

Match what is already there rather than reformatting:

- 4 spaces, no tabs; `snake_case` for functions and variables.
- Every query goes through the `db_*` helpers in `core/db.php` — prepared statements only, never string interpolation into SQL. The one place values are interpolated is `LIMIT`/`OFFSET`, and those are cast to `int` first; keep it that way.
- Escape output with `e()` / `h()`.
- Any form that changes data needs `csrf_field()` in the markup and `csrf_verify()` in the handler.
- New pages start with `require_once __DIR__ . '/core/bootstrap.php';` — not four separate requires.
- Comments in whichever language explains it best; the existing code mixes both.

### Things worth working on

- **Interface translation.** The UI is Chinese only. An English (or other) UI would widen the audience considerably — the LabKeeper sibling project shows one way to retrofit this.
- **Importer formats.** Anki `.apkg`, plain `.txt` word lists, TSV.
- **Statistics.** A retention curve or a review-history heatmap on the dashboard.
- **FSRS.** A modern alternative to SM-2, as an option alongside it. `sm2_review()` in `core/functions.php` is the seam.
- **Mobile.** The study card works on a phone but was not designed for it.

### Adding a field to the word card

The card is deliberately configurable end to end. To add one:

1. `database/schema.sql` — add the column to `words`, and a `show_*` flag to `user_settings`.
2. `scripts/import_words.php` — add the name to `$FIELDS`.
3. `settings.php` — add it to `$boolFields` and `$fieldLabels`.
4. `study.php` — render it, guarded by `$setting['show_*']`.
5. `categories.php` / `roots.php` — render it in the expanded list rows too, if it belongs there.

### What is unlikely to be merged

- Composer or npm dependencies. The zero-build-step property is what makes this installable on cheap shared hosting by a non-developer.
- A hard-coded call to a third-party dictionary or TTS API. Make it configurable and default it to off.
- Vocabulary data, as above.

---

## 中文

### 唯一的硬性规定

**不要提交词库数据。** 不要词表，不要词典导出，不要「我在 GitHub 上找到一份两万词的词库」。网上流传的中英词库几乎全部是从商业词典重新打包的、没有任何授权的内容，这类 PR 无论多有用都会被拒绝。

唯一的例外是你自己编写的词表，或者授权明确允许再分发的词表 —— 即便如此，也请先开一个 Issue，以便把授权信息正确地记进 `LICENSE`。

除此之外都欢迎：bug 修复、新功能、新的导入格式、界面翻译。

### 提 PR 之前

目前没有构建步骤，也还没有测试套件，所以标准是：**能通过语法检查，能跑起来。**

```bash
# 1. 每个文件都能解析
find . -name '*.php' -not -path './assets/*' -exec php -l {} \; | grep -v 'No syntax errors'

# 2. 从零安装一遍，整条链路能走通
mysql -u root -p -e "DROP DATABASE IF EXISTS wm_test; CREATE DATABASE wm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p wm_test < database/schema.sql
php scripts/import_words.php data/demo-wordlist.csv "示例" demo
php scripts/extract_roots.php
php scripts/classify_categories.php
php -S localhost:8000     # 注册、背几张卡、看看设置页
```

### 报 bug

请附上 PHP 版本、MySQL/MariaDB 版本，以及「你看到了什么」和「你期望看到什么」。页面报错就用 `display_errors=1` 跑一遍把信息贴上。**不要把你词库里的内容贴进 Issue。**

### 代码风格

跟着现有代码写，不要顺手重排版：

- 4 个空格缩进，不用 Tab；函数和变量用 `snake_case`。
- 所有查询走 `core/db.php` 里的 `db_*` 函数 —— 只用预处理语句，绝不把变量拼进 SQL。唯一拼接的地方是 `LIMIT`/`OFFSET`，且都先强制转成 `int`，请保持这个写法。
- 输出用 `e()` / `h()` 转义。
- 会改数据的表单，模板里要有 `csrf_field()`，处理端要有 `csrf_verify()`。
- 新页面开头写 `require_once __DIR__ . '/core/bootstrap.php';`，不要再分成四行。
- 注释用哪种语言讲得清楚就用哪种，现有代码本来就是中英混排。

### 值得做的方向

- **界面翻译。** 目前只有中文界面。做一套英文（或其他语言）界面能让更多人用上 —— 姊妹项目 LabKeeper 演示了一种改造思路。
- **更多导入格式。** Anki `.apkg`、纯 `.txt` 词表、TSV。
- **统计。** 概览页加一条记忆保持曲线，或者复习历史热力图。
- **FSRS。** 作为 SM-2 之外的可选算法。改造入口是 `core/functions.php` 里的 `sm2_review()`。
- **移动端。** 背单词卡片在手机上能用，但不是为手机设计的。

### 给单词卡片加一个字段

卡片从头到尾都是可配置的，加字段要改五个地方：

1. `database/schema.sql` —— 给 `words` 加列，给 `user_settings` 加一个 `show_*` 开关。
2. `scripts/import_words.php` —— 把列名加进 `$FIELDS`。
3. `settings.php` —— 加进 `$boolFields` 和 `$fieldLabels`。
4. `study.php` —— 渲染出来，外面套一层 `$setting['show_*']` 判断。
5. `categories.php` / `roots.php` —— 如果该字段也应出现在列表展开项里，一并加上。

### 大概不会被合并的东西

- 引入 Composer 或 npm 依赖。「零构建步骤」正是让不写代码的人也能把它装到便宜虚拟主机上的关键。
- 写死的第三方词典或 TTS 接口调用。做成可配置的，并且默认关闭。
- 词库数据，同上。
