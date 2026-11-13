<p align="center">
  <a href="https://escalated.dev"><img src="https://raw.githubusercontent.com/escalated-dev/escalated/main/docs/assets/readme/logo.png" width="56" height="56" alt="Escalated"></a>
</p>

<h1 align="center">Help Scout import for Escalated</h1>

<p align="center">Move your Help Scout mailboxes into Escalated, history included.</p>

<p align="center">
  <a href="https://escalated.dev"><b>Website</b></a> ·
  <a href="https://escalated.dev/docs"><b>Docs</b></a> ·
  <a href="https://escalated.dev/cloud"><b>Cloud</b></a> ·
  <a href="https://github.com/escalated-dev/escalated-plugin-import-helpscout/releases"><b>Releases</b></a>
</p>

This plugin imports conversations, customers, users, mailboxes and tags from Help Scout into Escalated for Laravel, signing in with an OAuth app you create. It's for teams switching from Help Scout who want to keep every conversation thread.

## Features

<table>
<tr>
<td width="33%" valign="top">

**What it imports**
- Users, mailboxes, customers and tags
- Conversations with status mapping
- Every thread: replies and notes
- Statuses mapped: active, pending, closed, spam

</td>
<td width="33%" valign="top">

**Connection**
- OAuth client credentials with token refresh
- Follows 301 redirects for moved resources

</td>
<td width="33%" valign="top">

**Reliability**
- Resumable imports with HAL cursor pagination
- Retries on 429 and 5xx, honours Retry-After

</td>
</tr>
</table>

<p align="center"><sub><a href="docs/features.md">Every feature in detail</a></sub></p>

<p align="center">
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"></a>
  <a href="https://getcomposer.org"><img src="https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer"></a>
</p>

## Learn more

- [Every feature](docs/features.md)
- [Configuration, hooks and import order](docs/reference.md)
- [Plugin SDK](https://github.com/escalated-dev/escalated-plugin-sdk): how Escalated plugins are built
- [Escalated docs](https://escalated.dev/docs)
- [Escalated](https://github.com/escalated-dev/escalated), the main repo

## License

MIT. Copyright (c) 2026 Escalated.dev. See [LICENSE](LICENSE).
