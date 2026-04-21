# Porkbot Astro Interface (Import)

This folder contains the Astro source for the Porkbot interface (widget + full page mode).

## 1) Install and run

```bash
cd porkbot_chat_widget/Import
npm install
cp .env.example .env
npm run dev
```

## 2) Environment configuration

Set these in `.env` (or deployment env):

- `PUBLIC_PORKBOT_API_BASE_URL`: Azure Function base URL
- `PUBLIC_PORKBOT_API_PREFIX`: API prefix (default `/api`)
- `PUBLIC_PORKBOT_DEFAULT_MODE`: `widget` or `full`
- `PUBLIC_PORKBOT_OPEN_BY_DEFAULT`: `true` or `false`

Current Azure Function host:

`https://porkbot-extension-f0edf7egf8d5c3e2.canadacentral-01.azurewebsites.net`

## 3) Build for deployment

```bash
npm run build
```

Deploy the generated `dist/` output to your static host.

## 4) Drupal runtime overrides

The interface can also read runtime config from:

- `window.__PORKBOT_CHAT_WIDGET_CONFIG__`
- `window.PorkbotChatWidgetConfig`
- Query parameters:
  - `porkbotApiBaseUrl` or `apiBaseUrl`
  - `porkbotApiPrefix` or `apiPrefix`
  - `porkbotMode` or `mode`
  - `porkbotOpenByDefault` or `openByDefault`

This allows Drupal init options to override environment defaults without rebuilding Astro.
