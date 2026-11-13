# Reference

Imports conversations, customers (contacts), users (agents), mailboxes (departments), and tags from Help Scout into Escalated. Authenticates via OAuth 2.0 Client Credentials with automatic token refresh.

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
