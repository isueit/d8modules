/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly PUBLIC_PORKBOT_API_BASE_URL?: string;
  readonly PUBLIC_PORKBOT_API_PREFIX?: string;
  readonly PUBLIC_PORKBOT_DEFAULT_MODE?: string;
  readonly PUBLIC_PORKBOT_OPEN_BY_DEFAULT?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

type PorkbotRuntimeConfigInput = {
  apiBaseUrl?: string;
  apiPrefix?: string;
  defaultMode?: "widget" | "full" | string;
  openByDefault?: boolean | string | number;
};

interface Window {
  __PORKBOT_CHAT_WIDGET_CONFIG__?: PorkbotRuntimeConfigInput;
  PorkbotChatWidgetConfig?: PorkbotRuntimeConfigInput;
}
