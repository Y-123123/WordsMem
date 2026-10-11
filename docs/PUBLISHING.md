# GitHub 发布说明

WordsMem 是可自行部署的 PHP + MySQL 单词学习应用。仓库用于分发源代码；GitHub Pages 无法运行 PHP 或 MySQL，实际网站需部署到支持 PHP 的服务器。

建议仓库名：`WordsMem`；可见性：Public。

建议 GitHub About 描述：

> Self-hosted PHP/MySQL vocabulary trainer with SM-2 spaced repetition, CSV/JSON imports, and Chinese/English learning cards.

建议主题：`php`, `mysql`, `vocabulary`, `spaced-repetition`, `sm2`, `self-hosted`, `language-learning`。

安装步骤见 [INSTALL.md](INSTALL.md)，功能介绍见 [README.md](../README.md)。配置模板为 `core/config.example.php`，真实的 `core/config.php` 不提交。仅发布示例词表；个人词库、数据库备份、用户信息和访问令牌不进入仓库。

本次发布准备修正了空 CSRF token 校验、维护脚本的网页访问、HTTPS cookie 判断和每日复习限额，并保留跨天未完成的新词。发布前检查不等同于完整安全审计。登录失败限制依赖当前会话；公网部署仍需要服务器层面的访问限流。
