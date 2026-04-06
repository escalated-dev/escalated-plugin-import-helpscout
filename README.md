# Escalated Plugin: Import Help Scout

Imports conversations, customers (contacts), users (agents), mailboxes (departments), and tags from Help Scout into Escalated. Authenticates via OAuth 2.0 Client Credentials with automatic token refresh.

## Features

- Imports users (agents), mailboxes (departments), customers (contacts), and tags
- Imports conversations (tickets) with full status mapping
- Imports all conversation threads (replies and notes)
- OAuth 2.0 Client Credentials authentication with automatic token refresh
- Follows HTTP 301 redirects for moved resources
- Cursor-based HAL pagination throughout for resumable imports
- Automatic rate-limit handling with `Retry-After` header support and retry on 429/5xx
- Maps Help Scout conversation statuses (active, pending, closed, spam) to Escalated equivalents

## Configuration

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `app_id` | text | Yes | OAuth App ID from Help Scout Profile > API Keys > OAuth Applications. |
| `app_secret` | password | Yes | OAuth App Secret for the same application. |

## Hooks

### Filters
- `import.adapters` — Registers the Help Scout import adapter with the Escalated import system.

## Entity Import Order

`agents` > `tags` > `departments` > `contacts` > `tickets` > `replies` > `attachments`

## Installation

```bash
npm install @escalated-dev/plugin-import-helpscout
```

## License

MIT
