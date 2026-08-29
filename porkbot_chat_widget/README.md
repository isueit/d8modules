# Porkbot Chat Widget

Adds a configurable loader for the Porkbot chat widget.

## Install

1. Enable the module.
2. Go to `Configuration > Web services > Porkbot Chat Widget`.
3. Configure:
   - `Widget script URL`: hosted widget script bundle URL.
   - `API base URL`: Azure Function host URL.
   - `API path prefix`: API route prefix (`/api` by default).
   - `Default interface mode`: `Widget` or `Full page`.
   - `Open widget by default`: auto-open behavior in widget mode.
   - `Init method path`: optional JS init method (example `PorkbotChatWidget.init`).
   - `Init options`: optional JSON object for extra init options.

## Astro Source (Import)

The Astro source used for the interface is in:

`porkbot_chat_widget/Import`

### Astro setup

```bash
cd porkbot_chat_widget/Import
npm install
cp .env.example .env
npm run dev
```

### Astro environment variables

- `PUBLIC_PORKBOT_API_BASE_URL`
- `PUBLIC_PORKBOT_API_PREFIX`
- `PUBLIC_PORKBOT_DEFAULT_MODE`
- `PUBLIC_PORKBOT_OPEN_BY_DEFAULT`

## Notes

- By default, the widget is not loaded on admin pages.
- If no script URL is configured, nothing is injected.
- The loader publishes runtime config to:
  - `window.__PORKBOT_CHAT_WIDGET_CONFIG__`
  - `window.PorkbotChatWidgetConfig`
- Runtime config is also merged into `initOptions` for init-method consumers.
