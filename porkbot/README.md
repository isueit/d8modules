# Porkbot Chat Widget

Drupal 10+ module that embeds the Iowa State Extension Porkbot chat widget — a Bot Framework Web Chat client connected over Direct Line — as a placeable block.

## Files

- `porkbot.info.yml`: module definition
- `porkbot.libraries.yml`: asset library definitions (widget CSS/JS, remote Web Chat script)
- `porkbot.module`: theme hook implementation
- `src/Plugin/Block/PorkbotBlock.php`: the "Porkbot Chat Widget" block plugin
- `templates/porkbot-widget.html.twig`: widget markup
- `css/porkbot.css`: widget styles, scoped to the widget's own elements
- `js/porkbot.js`: Drupal behavior that wires up the toggle button and Web Chat
- `assets/`: launcher and header images

## Installation

1. Copy this module into your site, e.g. `modules/custom/porkbot`.
2. Enable it: `drush en porkbot` (or via Extend in the admin UI).
3. Go to **Structure > Block layout**, place the **Porkbot Chat Widget** block in any region, and configure its visibility (e.g. all pages, specific content types, roles).

## Direct Line Token Broker

The widget uses an Azure Function token broker to get a short-lived Direct Line token. The Direct Line secret must never be put in module code or browser-visible config — the Azure Function keeps it server-side and returns JSON containing a `token` property:

```json
{ "token": "direct-line-token" }
```

The endpoint URL, chat title, and welcome message are set directly in `js/porkbot.js`. Update them there if the token broker URL changes.
