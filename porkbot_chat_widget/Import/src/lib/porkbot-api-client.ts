const DEFAULT_API_PREFIX = "/api";
const SSE_CONTENT_TYPE = "text/event-stream";

export type BackendErrorType =
  | "COSMOS_DB"
  | "OPENAI"
  | "AZURE_AI_PROJECT"
  | "NETWORK"
  | "CONFIGURATION"
  | "UNKNOWN";

export type QueryMode = "direct_chat" | "direct_chat_stream" | string;

export type SourceCitation = {
  docId?: string;
  title?: string;
  source?: string;
  page_numbers?: number[];
  section_headers?: string[];
};

export type QuerySuccessResponse = {
  response: string;
  session_id: string;
  request_id: string;
  chunks_used: number;
  mode: QueryMode;
};

export type StreamThoughtDeltaEvent = {
  type: "response.thought.delta";
  delta: {
    sources?: SourceCitation[];
  };
};

export type StreamOutputTextDeltaEvent = {
  type: "response.output_text.delta";
  delta: string;
  item_id?: string;
  output_index?: number;
  content_index?: number;
};

export type StreamCompletedEvent = {
  type: "response.completed";
  response: {
    id?: string;
    output?: Array<{
      content?: Array<{
        text?: string;
      }>;
    }>;
  };
  session_id?: string;
  request_id?: string;
  chunks_used?: number;
  mode?: QueryMode;
};

export type StreamFailedEvent = {
  type: "response.failed";
  response: {
    error: {
      code: BackendErrorType;
      message: string;
      stage: string;
      retryable: boolean;
      request_id?: string;
      session_id?: string;
      details?: Record<string, unknown>;
    };
  };
};

export type StreamEvent =
  | StreamThoughtDeltaEvent
  | StreamOutputTextDeltaEvent
  | StreamCompletedEvent
  | StreamFailedEvent;

export type HealthStatus = "healthy" | "degraded" | "unhealthy";

export type HealthCheck = {
  service: string;
  status: HealthStatus;
  response_time_ms?: number;
  message?: string;
  error?: BackendErrorType;
};

export type HealthSummary = {
  total: number;
  healthy: number;
  degraded: number;
  unhealthy: number;
};

export type HealthResponse = {
  status: HealthStatus;
  timestamp: string;
  version?: string;
  checks: HealthCheck[];
  summary: HealthSummary;
};

type QueryErrorResponse = {
  error?: string;
  type?: BackendErrorType;
  recoverable?: boolean;
  requestId?: string;
  request_id?: string;
  timestamp?: string;
  details?: Record<string, unknown>;
};

type QueryRequestBody = {
  query: string;
  session_id?: string;
  stream?: boolean;
};

export type QueryOptions = {
  query: string;
  sessionId?: string;
  signal?: AbortSignal;
};

export type StreamQueryOptions = {
  query: string;
  sessionId?: string;
  signal?: AbortSignal;
  onEvent: (event: StreamEvent) => void;
};

export type HealthOptions = {
  signal?: AbortSignal;
};

export type PorkbotApiClientOptions = {
  baseUrl?: string;
  defaultSessionId?: string;
  apiPrefix?: string;
  fetcher?: typeof fetch;
};

export class PorkbotApiError extends Error {
  readonly status?: number;
  readonly type?: BackendErrorType;
  readonly recoverable?: boolean;
  readonly requestId?: string;
  readonly details?: Record<string, unknown>;
  readonly timestamp?: string;

  constructor(
    message: string,
    options: {
      status?: number;
      type?: BackendErrorType;
      recoverable?: boolean;
      requestId?: string;
      details?: Record<string, unknown>;
      timestamp?: string;
    } = {}
  ) {
    super(message);
    this.name = "PorkbotApiError";
    this.status = options.status;
    this.type = options.type;
    this.recoverable = options.recoverable;
    this.requestId = options.requestId;
    this.details = options.details;
    this.timestamp = options.timestamp;
  }
}

export type PorkbotApiClient = {
  query: (options: QueryOptions) => Promise<QuerySuccessResponse>;
  streamQuery: (options: StreamQueryOptions) => Promise<void>;
  health: (options?: HealthOptions) => Promise<HealthResponse>;
};

export function createPorkbotApiClient(
  options: PorkbotApiClientOptions = {}
): PorkbotApiClient {
  const fetcher = options.fetcher ?? fetch;
  const defaultSessionId = cleanString(options.defaultSessionId);
  const apiPrefix = options.apiPrefix ?? DEFAULT_API_PREFIX;

  const buildUrl = (route: string) => buildApiUrl(options.baseUrl, apiPrefix, route);

  async function query(queryOptions: QueryOptions): Promise<QuerySuccessResponse> {
    const payload = buildQueryPayload(queryOptions.query, {
      sessionId: queryOptions.sessionId ?? defaultSessionId ?? undefined,
      stream: false
    });

    const response = await fetcher(buildUrl("/query"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json"
      },
      body: JSON.stringify(payload),
      signal: queryOptions.signal
    });

    if (!response.ok) {
      throw await toApiError(response);
    }

    const data = (await parseJsonBody(response)) as QuerySuccessResponse;
    return data;
  }

  async function streamQuery(streamOptions: StreamQueryOptions): Promise<void> {
    const payload = buildQueryPayload(streamOptions.query, {
      sessionId: streamOptions.sessionId ?? defaultSessionId ?? undefined,
      stream: true
    });

    const response = await fetcher(buildUrl("/query"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: SSE_CONTENT_TYPE
      },
      body: JSON.stringify(payload),
      signal: streamOptions.signal
    });

    if (!response.ok) {
      throw await toApiError(response);
    }

    if (!response.body) {
      throw new PorkbotApiError("Streaming response did not include a readable body.", {
        status: response.status
      });
    }

    const contentType = response.headers.get("content-type") ?? "";
    if (!contentType.includes(SSE_CONTENT_TYPE)) {
      throw new PorkbotApiError(
        `Expected ${SSE_CONTENT_TYPE} but received "${contentType || "unknown"}".`,
        { status: response.status }
      );
    }

    await consumeSse(response.body, streamOptions.onEvent);
  }

  async function health(healthOptions: HealthOptions = {}): Promise<HealthResponse> {
    const response = await fetcher(buildUrl("/health"), {
      method: "GET",
      headers: {
        Accept: "application/json",
        "Cache-Control": "no-cache"
      },
      signal: healthOptions.signal
    });

    if (!response.ok) {
      throw await toApiError(response);
    }

    return (await parseJsonBody(response)) as HealthResponse;
  }

  return {
    query,
    streamQuery,
    health
  };
}

function buildApiUrl(baseUrl: string | undefined, apiPrefix: string, route: string): string {
  const normalizedPrefix = normalizePathPrefix(apiPrefix);
  const normalizedRoute = normalizeRoute(route);
  const path = `${normalizedPrefix}${normalizedRoute}`;

  if (!baseUrl) {
    return path;
  }

  return `${baseUrl.replace(/\/+$/, "")}${path}`;
}

function normalizePathPrefix(prefix: string): string {
  const cleanPrefix = cleanString(prefix) ?? DEFAULT_API_PREFIX;
  return cleanPrefix.startsWith("/") ? cleanPrefix : `/${cleanPrefix}`;
}

function normalizeRoute(route: string): string {
  return route.startsWith("/") ? route : `/${route}`;
}

function buildQueryPayload(
  query: string,
  options: { sessionId?: string; stream: boolean }
): QueryRequestBody {
  const cleanQuery = cleanString(query);
  if (!cleanQuery) {
    throw new PorkbotApiError("Missing required field: query", { status: 400 });
  }

  const payload: QueryRequestBody = {
    query: cleanQuery
  };

  const cleanSessionId = cleanString(options.sessionId);
  if (cleanSessionId) {
    payload.session_id = cleanSessionId;
  }

  if (options.stream) {
    payload.stream = true;
  }

  return payload;
}

async function consumeSse(stream: ReadableStream<Uint8Array>, onEvent: (event: StreamEvent) => void) {
  const reader = stream.getReader();
  const decoder = new TextDecoder();
  let buffer = "";

  while (true) {
    const { done, value } = await reader.read();
    if (done) {
      break;
    }

    buffer += decoder.decode(value, { stream: true });
    const { events, rest } = splitSseFrames(buffer);
    buffer = rest;

    for (const rawEvent of events) {
      const parsed = parseSseEvent(rawEvent);
      if (!parsed) {
        continue;
      }
      onEvent(parsed);
    }
  }

  const trailing = buffer.trim();
  if (trailing) {
    const parsed = parseSseEvent(trailing);
    if (parsed) {
      onEvent(parsed);
    }
  }
}

function splitSseFrames(buffer: string): { events: string[]; rest: string } {
  const parts = buffer.split(/\r?\n\r?\n/u);
  const rest = parts.pop() ?? "";
  return {
    events: parts,
    rest
  };
}

function parseSseEvent(rawEvent: string): StreamEvent | null {
  const lines = rawEvent.split(/\r?\n/u);
  const dataLines: string[] = [];

  for (const line of lines) {
    if (!line.startsWith("data:")) {
      continue;
    }
    dataLines.push(line.slice(5).trimStart());
  }

  if (!dataLines.length) {
    return null;
  }

  const data = dataLines.join("\n");
  if (!data) {
    return null;
  }

  try {
    return JSON.parse(data) as StreamEvent;
  } catch (error) {
    throw new PorkbotApiError("Failed to parse SSE event JSON.", {
      details: {
        parseError: error instanceof Error ? error.message : String(error),
        rawEvent: data
      }
    });
  }
}

async function toApiError(response: Response): Promise<PorkbotApiError> {
  const rawBody = await parseBody(response);
  const payload = isObject(rawBody) ? (rawBody as QueryErrorResponse) : null;

  const message =
    payload?.error ??
    formatRawErrorMessage(rawBody, response.status, response.statusText) ??
    "Request failed";

  return new PorkbotApiError(message, {
    status: response.status,
    type: payload?.type,
    recoverable: payload?.recoverable,
    requestId: payload?.requestId ?? payload?.request_id,
    details: payload?.details,
    timestamp: payload?.timestamp
  });
}

function formatRawErrorMessage(
  rawBody: unknown,
  status: number,
  statusText: string
): string | null {
  if (typeof rawBody !== "string") {
    return statusText || null;
  }

  const trimmed = rawBody.trim();
  if (!trimmed) {
    return statusText || null;
  }

  if (looksLikeHtml(trimmed)) {
    const title = extractHtmlTagText(trimmed, "title");
    const path = extractPathFromHtml(trimmed);
    const statusLabel = status ? `HTTP ${status}` : "HTTP error";

    if (title && path) {
      return `${statusLabel}: ${title} (Path: ${path})`;
    }

    if (title) {
      return `${statusLabel}: ${title}`;
    }

    return `${statusLabel}: Backend returned an HTML error page.`;
  }

  return trimmed;
}

async function parseJsonBody(response: Response): Promise<unknown> {
  const body = await parseBody(response);
  if (body === null) {
    throw new PorkbotApiError("Expected JSON response body but received empty payload.", {
      status: response.status
    });
  }
  return body;
}

async function parseBody(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) {
    return null;
  }

  try {
    return JSON.parse(text) as unknown;
  } catch {
    return text;
  }
}

function looksLikeHtml(value: string): boolean {
  return /<!doctype html>|<html[\s>]|<body[\s>]|<head[\s>]/iu.test(value);
}

function extractHtmlTagText(html: string, tagName: string): string | null {
  const regex = new RegExp(`<${tagName}[^>]*>(.*?)</${tagName}>`, "isu");
  const match = html.match(regex);
  return cleanString(match?.[1] ?? null);
}

function extractPathFromHtml(html: string): string | null {
  const match = html.match(/Path:\s*([^<\n\r]+)/iu);
  return cleanString(match?.[1] ?? null);
}

function cleanString(value: string | undefined | null): string | null {
  if (typeof value !== "string") {
    return null;
  }
  const trimmed = value.trim();
  return trimmed.length ? trimmed : null;
}

function isObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}
