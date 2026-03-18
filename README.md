# Help Scout Import

Import conversations, customers (contacts), users (agents), mailboxes (departments), and tags from Help Scout into Escalated. The adapter uses Help Scout's OAuth 2.0 Client Credentials flow, automatically refreshing the bearer token before expiry.

## Installation

```bash
# Install via Composer
composer require escalated/escalated-plugin-import-helpscout
```

## Configuration

Credentials are entered through the Escalated import wizard UI. The following fields are required:

| Field | Description |
|---|---|
| `app_id` | OAuth App ID — create an app in **Help Scout > Your Profile > API Keys > OAuth Applications** |
| `app_secret` | OAuth App Secret for the same application |

## Features

- Imports users (mapped to agents), mailboxes (mapped to departments), customers (mapped to contacts), and tags
- Imports conversations (mapped to tickets) with full status mapping
- Imports all conversation threads (replies and notes)
- Authenticates via OAuth 2.0 Client Credentials — token is obtained and refreshed automatically (60-second buffer before expiry)
- Follows HTTP 301 redirects for moved resources
- Cursor-based HAL pagination (`_links.next`) throughout — imports are resumable after failures
- Automatic rate-limit handling: respects `Retry-After` headers and retries on 429/5xx responses
- Maps Help Scout conversation statuses (`active`, `pending`, `closed`, `spam`) to Escalated equivalents

## Hooks

### Filters

- `import.adapters` — Registers the `HelpScoutImportAdapter` with the Escalated import system

## Entity Types Imported

`agents` → `tags` → `departments` → `contacts` → `tickets` → `replies` → `attachments`

## Requirements

- Escalated >= 0.6.0
- Help Scout account with an OAuth application configured
