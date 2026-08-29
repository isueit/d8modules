export type PorkbotWidgetMode = "widget" | "full";

export type PorkbotRuntimeConfig = {
  apiBaseUrl?: string;
  apiPrefix: string;
  defaultMode: PorkbotWidgetMode;
  openByDefault: boolean;
};

type RuntimeConfigValue = string | boolean | number | null | undefined;

type RuntimeConfigRecord = Record<string, RuntimeConfigValue>;

const DEFAULT_API_PREFIX = "/api";
const DEFAULT_DEV_API_BASE_URL = "http://localhost:7071";

export function getDefaultRuntimeConfig(): PorkbotRuntimeConfig {
  const apiBaseUrl =
    cleanString(import.meta.env.PUBLIC_PORKBOT_API_BASE_URL) ||
    (import.meta.env.DEV ? DEFAULT_DEV_API_BASE_URL : undefined);

  const apiPrefix =
    normalizeApiPrefix(import.meta.env.PUBLIC_PORKBOT_API_PREFIX) ||
    DEFAULT_API_PREFIX;

  const defaultMode = toWidgetMode(import.meta.env.PUBLIC_PORKBOT_DEFAULT_MODE);
  const openByDefault = toBoolean(import.meta.env.PUBLIC_PORKBOT_OPEN_BY_DEFAULT);

  return {
    apiBaseUrl,
    apiPrefix,
    defaultMode,
    openByDefault
  };
}

export function readRuntimeConfigFromWindow(
  defaults: PorkbotRuntimeConfig
): PorkbotRuntimeConfig {
  if (typeof window === "undefined") {
    return defaults;
  }

  const globalRuntime = asRuntimeConfigRecord(window.__PORKBOT_CHAT_WIDGET_CONFIG__);
  const legacyGlobalRuntime = asRuntimeConfigRecord(window.PorkbotChatWidgetConfig);
  const paramRuntime = getQueryRuntimeConfig(window.location.search);

  const merged = {
    ...defaults,
    ...legacyGlobalRuntime,
    ...globalRuntime,
    ...paramRuntime
  };

  const apiBaseUrl = cleanString(merged.apiBaseUrl) || defaults.apiBaseUrl;
  const apiPrefix = normalizeApiPrefix(merged.apiPrefix) || defaults.apiPrefix;
  const defaultMode = toWidgetMode(merged.defaultMode, defaults.defaultMode);
  const openByDefault = toBoolean(merged.openByDefault, defaults.openByDefault);

  return {
    apiBaseUrl,
    apiPrefix,
    defaultMode,
    openByDefault
  };
}

function getQueryRuntimeConfig(search: string): RuntimeConfigRecord {
  const params = new URLSearchParams(search);

  return {
    apiBaseUrl:
      params.get("porkbotApiBaseUrl") ??
      params.get("apiBaseUrl") ??
      params.get("porkbot_api_base_url"),
    apiPrefix:
      params.get("porkbotApiPrefix") ??
      params.get("apiPrefix") ??
      params.get("porkbot_api_prefix"),
    defaultMode:
      params.get("porkbotMode") ??
      params.get("mode") ??
      params.get("porkbot_default_mode"),
    openByDefault:
      params.get("porkbotOpenByDefault") ??
      params.get("openByDefault") ??
      params.get("porkbot_open_by_default")
  };
}

function asRuntimeConfigRecord(value: unknown): RuntimeConfigRecord {
  return isRecord(value) ? (value as RuntimeConfigRecord) : {};
}

function normalizeApiPrefix(
  value: RuntimeConfigValue,
  fallback?: string
): string | undefined {
  const clean = cleanString(value);
  if (!clean) {
    return fallback;
  }

  return clean.startsWith("/") ? clean : `/${clean}`;
}

function cleanString(value: RuntimeConfigValue): string | undefined {
  if (typeof value !== "string") {
    return undefined;
  }

  const trimmed = value.trim();
  return trimmed.length ? trimmed : undefined;
}

function toWidgetMode(
  value: RuntimeConfigValue,
  fallback: PorkbotWidgetMode = "widget"
): PorkbotWidgetMode {
  return value === "full" ? "full" : value === "widget" ? "widget" : fallback;
}

function toBoolean(value: RuntimeConfigValue, fallback = false): boolean {
  if (typeof value === "boolean") {
    return value;
  }

  if (typeof value === "number") {
    return value !== 0;
  }

  if (typeof value !== "string") {
    return fallback;
  }

  const normalized = value.trim().toLowerCase();
  if (!normalized) {
    return fallback;
  }

  if (["1", "true", "yes", "on"].includes(normalized)) {
    return true;
  }

  if (["0", "false", "no", "off"].includes(normalized)) {
    return false;
  }

  return fallback;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}
