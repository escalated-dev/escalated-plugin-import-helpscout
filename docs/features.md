# Features

## Features

- Imports users (agents), mailboxes (departments), customers (contacts), and tags
- Imports conversations (tickets) with full status mapping
- Imports all conversation threads (replies and notes)
- OAuth 2.0 Client Credentials authentication with automatic token refresh
- Follows HTTP 301 redirects for moved resources
- Cursor-based HAL pagination throughout for resumable imports
- Automatic rate-limit handling with `Retry-After` header support and retry on 429/5xx
- Maps Help Scout conversation statuses (active, pending, closed, spam) to Escalated equivalents
