"use client";

import {
  type ComponentProps,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState
} from "react";
import { Conversation, ConversationContent, ConversationEmptyState, ConversationScrollButton } from "@/components/ai-elements/conversation";
import { Message, MessageContent, MessageResponse } from "@/components/ai-elements/message";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { cn } from "@/lib/utils";
import porkbotIconOne from "@/assets/PorkChatBot-01.png";
import porkbotIconTwo from "@/assets/PorkChatBot-02.png";
import { createPorkbotApiClient, PorkbotApiError } from "@/lib/porkbot-api-client";
import {
  type PorkbotRuntimeConfig,
  getDefaultRuntimeConfig,
  readRuntimeConfigFromWindow
} from "@/lib/porkbot-runtime-config";
import { RefreshCcwIcon, SendHorizontalIcon, SparklesIcon } from "lucide-react";

type ChatRole = "user" | "assistant";

type ChatMessage = {
  id: string;
  role: ChatRole;
  text: string;
};

type FormSubmitEvent = Parameters<NonNullable<ComponentProps<"form">["onSubmit"]>>[0];

type StarterPreset = {
  id: string;
  label: string;
  prompt: string;
};

const STARTER_PRESETS: StarterPreset[] = [
  {
    id: "farrowing-temp",
    label: "Farrowing Temperature",
    prompt: "What is the ideal temperature range for a farrowing room?"
  },
  {
    id: "feed-efficiency",
    label: "Feed Efficiency",
    prompt: "How can I improve feed efficiency in grow-finish pigs?"
  },
  {
    id: "biosecurity",
    label: "Biosecurity",
    prompt: "What are the most important daily biosecurity practices for pork production?"
  },
  {
    id: "piglet-scours",
    label: "Piglet Scours",
    prompt: "What are common causes of piglet scours and first response steps?"
  }
];
const WIDGET_STARTER_PRESETS = STARTER_PRESETS.slice(0, 3);

const INITIAL_MESSAGE = "Hi, I'm Porkbot. Ask me about herd care, nutrition, housing, or biosecurity.";
const RESTART_MESSAGE = "Conversation restarted. What pork production question can I help with now?";

const createId = () =>
  (typeof crypto !== "undefined" && "randomUUID" in crypto
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(16).slice(2)}`);

const getErrorMessage = (
  error: unknown,
  apiBaseUrl?: string,
  apiPrefix = "/api"
) => {
  if (error instanceof PorkbotApiError) {
    if (error.status === 404 && !apiBaseUrl) {
      return `Chat API route ${apiPrefix}/query was not found. Set PUBLIC_PORKBOT_API_BASE_URL (for example http://localhost:7071).`;
    }

    return error.message;
  }

  return "I couldn't reach the chat service. Please try again.";
};

const isAbortError = (error: unknown) =>
  error instanceof Error && error.name === "AbortError";

const extractCompletedText = (event: {
  response?: { output?: Array<{ content?: Array<{ text?: string }> }> };
}) => event.response?.output?.[0]?.content?.[0]?.text?.trim() ?? "";

type ChatSurfaceProps = {
  compact?: boolean;
  composerValue: string;
  errorMessage: string | null;
  inputRef: React.RefObject<HTMLInputElement | null>;
  isSubmitting: boolean;
  isWaitingForFirstToken: boolean;
  messages: ChatMessage[];
  onComposerChange: (nextValue: string) => void;
  onFormSubmit: ComponentProps<"form">["onSubmit"];
  onPromptSelect: (prompt: string) => void;
  onReset: () => void;
  activeAssistantMessageId: string | null;
};

function ChatSurface({
  compact = false,
  composerValue,
  errorMessage,
  inputRef,
  isSubmitting,
  isWaitingForFirstToken,
  messages,
  onComposerChange,
  onFormSubmit,
  onPromptSelect,
  onReset,
  activeAssistantMessageId
}: ChatSurfaceProps) {
  const visibleMessages = messages.filter(
    (message) =>
      !(message.id === activeAssistantMessageId && message.role === "assistant" && !message.text.trim())
  );
  const showPromptRail = !composerValue.trim() && !isSubmitting;
  const promptRailStarters = compact ? WIDGET_STARTER_PRESETS : STARTER_PRESETS;

  return (
    <Card
      className={cn(
        "pb-chat-surface flex flex-col overflow-hidden border-border/80 !gap-0 !py-0 bg-card/95 shadow-lg backdrop-blur-sm",
        compact ? "h-[580px]" : "h-[680px]"
      )}
    >
      <CardHeader className="pb-chat-header shrink-0 border-border/70 border-b !gap-0 !px-3 !py-2 text-white">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0 py-0.5">
            <CardTitle className="flex items-center gap-2 text-base font-semibold tracking-tight">
              <img src={porkbotIconOne.src} alt="" className="size-5 shrink-0 rounded-[3px] object-contain" />
              <span className="truncate">Porkbot</span>
            </CardTitle>
          </div>
          <Button
            type="button"
            size="sm"
            variant="secondary"
            className="h-7 cursor-pointer rounded-full px-2.5 text-xs transition-colors duration-200 motion-reduce:transition-none"
            onClick={onReset}
          >
            <RefreshCcwIcon className="size-3.5" />
            Reset
          </Button>
        </div>
      </CardHeader>

      <CardContent className="flex min-h-0 flex-1 flex-col p-0">
        <Conversation className="min-h-0 flex-1">
          <ConversationContent className="gap-3 px-3 py-4">
            {visibleMessages.length === 0 ? (
              <ConversationEmptyState
                title="Start a conversation"
                description="Ask a pork production question below to get started."
              />
            ) : (
              visibleMessages.map((message) => (
                <Message from={message.role} key={message.id}>
                  <MessageContent
                    className={cn(
                      "pb-message max-w-full min-w-0 rounded-2xl px-3.5 py-2.5 text-sm leading-6 shadow-sm",
                      message.role === "user"
                        ? "pb-message-user"
                        : "pb-message-assistant"
                    )}
                  >
                    {message.role === "assistant" ? (
                      <MessageResponse className="min-w-0 whitespace-pre-wrap break-words leading-relaxed [&_*]:break-words [&_ol]:pl-5 [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_ul]:pl-5">
                        {message.text}
                      </MessageResponse>
                    ) : (
                      <p className="whitespace-pre-wrap break-words">{message.text}</p>
                    )}
                  </MessageContent>
                </Message>
              ))
            )}

            {isWaitingForFirstToken ? (
              <Message from="assistant">
                <MessageContent
                  className="pb-message pb-message-assistant max-w-full min-w-0 rounded-2xl px-3.5 py-2.5 text-sm text-slate-600 shadow-sm"
                  aria-live="polite"
                  aria-label="Porkbot is typing"
                >
                  <span className="sr-only">Porkbot is typing</span>
                  <span className="pb-typing-bubble" aria-hidden="true">
                    <span className="pb-typing-dot pb-typing-dot--1" />
                    <span className="pb-typing-dot pb-typing-dot--2" />
                    <span className="pb-typing-dot pb-typing-dot--3" />
                  </span>
                </MessageContent>
              </Message>
            ) : null}
          </ConversationContent>
          <ConversationScrollButton />
        </Conversation>

        <div className="border-border/70 border-t bg-background/85 px-3 py-3">
          {showPromptRail ? (
            <div className="mb-3 flex flex-wrap gap-2">
              {promptRailStarters.map((starter) => (
                <Button
                  key={starter.id}
                  type="button"
                  size="sm"
                  variant="outline"
                  className={cn(
                    "pb-starter-chip rounded-full whitespace-normal transition-colors duration-200 motion-reduce:transition-none",
                    compact ? "pb-starter-chip-compact h-7 px-2.5 text-[11px]" : "h-8 px-3 text-xs"
                  )}
                  title={starter.prompt}
                  onClick={() => onPromptSelect(starter.prompt)}
                >
                  <SparklesIcon className="size-3.5" />
                  {starter.label}
                </Button>
              ))}
            </div>
          ) : null}

          <form className="flex items-center gap-2" onSubmit={onFormSubmit}>
            <Input
              ref={inputRef}
              aria-label="Send a message to Porkbot"
              value={composerValue}
              onChange={(event) => onComposerChange(event.target.value)}
              placeholder="Ask about herd health, feed, facilities, or biosecurity..."
              className="h-11"
            />
            <Button
              type="submit"
              className="h-11 min-w-24 cursor-pointer transition-transform duration-200 hover:-translate-y-0.5 motion-reduce:transform-none motion-reduce:transition-none"
              disabled={isSubmitting || !composerValue.trim()}
            >
              {isSubmitting ? "Waiting..." : "Send"}
              {!isSubmitting ? <SendHorizontalIcon className="size-4" /> : null}
            </Button>
          </form>

          {errorMessage ? (
            <p className="mt-2 text-sm text-destructive">{errorMessage}</p>
          ) : null}
        </div>
      </CardContent>
    </Card>
  );
}

export default function PorkbotAiElementsIsland() {
  const defaultRuntimeConfig = useMemo(() => getDefaultRuntimeConfig(), []);
  const [runtimeConfig, setRuntimeConfig] = useState<PorkbotRuntimeConfig>(
    defaultRuntimeConfig
  );

  useEffect(() => {
    setRuntimeConfig((current) => readRuntimeConfigFromWindow(current));
  }, []);

  const apiClient = useMemo(
    () =>
      createPorkbotApiClient({
        baseUrl: runtimeConfig.apiBaseUrl,
        apiPrefix: runtimeConfig.apiPrefix
      }),
    [runtimeConfig.apiBaseUrl, runtimeConfig.apiPrefix]
  );
  const activeRequestRef = useRef<AbortController | null>(null);
  const widgetInputRef = useRef<HTMLInputElement | null>(null);
  const fullInputRef = useRef<HTMLInputElement | null>(null);

  const [activeTab, setActiveTab] = useState<"widget" | "full">(
    defaultRuntimeConfig.defaultMode
  );
  const [widgetOpen, setWidgetOpen] = useState(
    defaultRuntimeConfig.defaultMode === "widget" && defaultRuntimeConfig.openByDefault
  );
  const [sessionId, setSessionId] = useState<string | undefined>(undefined);
  const [composerValue, setComposerValue] = useState("");
  const [status, setStatus] = useState<"ready" | "submitted" | "error">("ready");
  const [activeAssistantMessageId, setActiveAssistantMessageId] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: createId(),
      role: "assistant",
      text: INITIAL_MESSAGE
    }
  ]);

  const isSubmitting = status === "submitted";

  useEffect(() => {
    setActiveTab(runtimeConfig.defaultMode);
    setWidgetOpen(
      runtimeConfig.defaultMode === "widget" && runtimeConfig.openByDefault
    );
  }, [runtimeConfig.defaultMode, runtimeConfig.openByDefault]);

  const resetConversation = useCallback(() => {
    activeRequestRef.current?.abort();
    activeRequestRef.current = null;
    setSessionId(undefined);
    setErrorMessage(null);
    setStatus("ready");
    setActiveAssistantMessageId(null);
    setMessages([
      {
        id: createId(),
        role: "assistant",
        text: RESTART_MESSAGE
      }
    ]);
  }, []);

  const submitPrompt = useCallback(
    async (rawPrompt: string) => {
      const prompt = rawPrompt.trim();
      if (!prompt || isSubmitting) {
        return;
      }

      setErrorMessage(null);
      setStatus("submitted");
      setComposerValue("");

      const userMessage: ChatMessage = {
        id: createId(),
        role: "user",
        text: prompt
      };

      setMessages((current) => [...current, userMessage]);

      const controller = new AbortController();
      activeRequestRef.current = controller;
      const assistantMessageId = createId();
      let receivedStreamDelta = false;
      setActiveAssistantMessageId(assistantMessageId);

      setMessages((current) => [
        ...current,
        { id: assistantMessageId, role: "assistant", text: "" }
      ]);

      try {
        await apiClient.streamQuery({
          query: prompt,
          sessionId,
          signal: controller.signal,
          onEvent: (event) => {
            if (event.type === "response.output_text.delta") {
              receivedStreamDelta = true;
              setMessages((current) =>
                current.map((message) =>
                  message.id === assistantMessageId
                    ? { ...message, text: `${message.text}${event.delta}` }
                    : message
                )
              );
              return;
            }

            if (event.type === "response.completed") {
              if (event.session_id) {
                setSessionId(event.session_id);
              }

              if (!receivedStreamDelta) {
                const completedText = extractCompletedText(event);
                if (completedText) {
                  setMessages((current) =>
                    current.map((message) =>
                      message.id === assistantMessageId
                        ? { ...message, text: completedText }
                        : message
                    )
                  );
                }
              }
              return;
            }

            if (event.type === "response.failed") {
              throw new PorkbotApiError(event.response.error.message, {
                type: event.response.error.code,
                recoverable: event.response.error.retryable,
                requestId: event.response.error.request_id,
                details: event.response.error.details
              });
            }
          }
        });

        setStatus("ready");
      } catch (error) {
        if (isAbortError(error)) {
          setMessages((current) =>
            current.filter((message) => message.id !== assistantMessageId)
          );
          setStatus("ready");
          return;
        }

        if (!receivedStreamDelta) {
          try {
            const fallbackResponse = await apiClient.query({
              query: prompt,
              sessionId,
              signal: controller.signal
            });

            setSessionId(fallbackResponse.session_id);
            setMessages((current) =>
              current.map((message) =>
                message.id === assistantMessageId
                  ? { ...message, text: fallbackResponse.response }
                  : message
              )
            );
            setStatus("ready");
            return;
          } catch (fallbackError) {
            if (isAbortError(fallbackError)) {
              setMessages((current) =>
                current.filter((message) => message.id !== assistantMessageId)
              );
              setStatus("ready");
              return;
            }
          }
        }

        setErrorMessage(
          getErrorMessage(error, runtimeConfig.apiBaseUrl, runtimeConfig.apiPrefix)
        );
        setMessages((current) =>
          current.map((message) =>
            message.id === assistantMessageId
              ? {
                  ...message,
                  text:
                    message.text.trim() ||
                    "I hit an error while sending that message. Please retry in a few seconds."
                }
              : message
          )
        );
        setStatus("error");
      } finally {
        activeRequestRef.current = null;
        setActiveAssistantMessageId(null);
      }
    },
    [apiClient, isSubmitting, runtimeConfig.apiBaseUrl, sessionId]
  );

  const handleFormSubmit: ComponentProps<"form">["onSubmit"] = useCallback(
    (event: FormSubmitEvent) => {
      event.preventDefault();
      void submitPrompt(composerValue);
    },
    [composerValue, submitPrompt]
  );

  const focusComposerForMode = useCallback((mode: "widget" | "full") => {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const input = mode === "widget" ? widgetInputRef.current : fullInputRef.current;
        if (!input) {
          return;
        }

        input.focus();
        const cursorPosition = input.value.length;
        input.setSelectionRange(cursorPosition, cursorPosition);
      });
    });
  }, []);

  const handleStarterPrefill = useCallback(
    (prompt: string, nextMode: "widget" | "full") => {
      if (isSubmitting) {
        return;
      }

      if (nextMode === "widget") {
        setActiveTab("widget");
        setWidgetOpen(true);
      } else {
        setActiveTab("full");
      }

      setErrorMessage(null);
      setComposerValue(prompt);
      focusComposerForMode(nextMode);
    },
    [focusComposerForMode, isSubmitting]
  );

  const handleInlinePrompt = useCallback(
    (prompt: string) => {
      if (isSubmitting) {
        return;
      }

      setErrorMessage(null);
      setComposerValue(prompt);
      focusComposerForMode(activeTab);
    },
    [activeTab, focusComposerForMode, isSubmitting]
  );

  const streamingAssistantText =
    activeAssistantMessageId != null
      ? messages.find((message) => message.id === activeAssistantMessageId)?.text ?? ""
      : "";
  const isWaitingForFirstToken =
    isSubmitting && !!activeAssistantMessageId && !streamingAssistantText.trim();

  return (
    <section className="porkbot-ai pb-shell relative rounded-3xl p-4 md:p-6">
      <div className="pointer-events-none absolute -right-16 -top-16 size-48 rounded-full bg-[#ffcfdb]/60 blur-3xl" />
      <div className="pointer-events-none absolute -left-12 -bottom-14 size-44 rounded-full bg-[#fbe3a1]/60 blur-3xl" />

      <div className="relative">
        <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#8e0a20]">
              Pork Production Assistant
            </p>
            <h2 className="font-[Figtree] text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
              Porkbot Chat Workspace
            </h2>
            <p className="mt-1 max-w-3xl text-base text-slate-600">
              Switch between a quick widget and a full conversation view while keeping one shared Porkbot thread.
            </p>
          </div>
        </div>

        <Tabs
          defaultValue="widget"
          value={activeTab}
          onValueChange={(value) => setActiveTab(value as "widget" | "full")}
          className="gap-4"
        >
          <TabsList className="w-fit rounded-full border border-border bg-white/80 p-1">
            <TabsTrigger
              value="widget"
              className="cursor-pointer rounded-full px-5 transition-all duration-200 motion-reduce:transition-none"
            >
              Widget
            </TabsTrigger>
            <TabsTrigger
              value="full"
              className="cursor-pointer rounded-full px-5 transition-all duration-200 motion-reduce:transition-none"
            >
              Full Page
            </TabsTrigger>
          </TabsList>

          <TabsContent
            value="widget"
            className="mt-2 data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:duration-200 motion-reduce:data-[state=active]:animate-none"
          >
            <Card className="relative min-h-[720px] overflow-hidden border-border/80 bg-white/75 p-4 shadow-lg backdrop-blur-sm md:p-6">
              <div className="max-w-xl">
                <CardTitle className="font-[Figtree] text-4xl tracking-tight text-slate-900 md:text-5xl">
                  Quick Barn Widget
                </CardTitle>
                <CardDescription className="mt-3 text-base text-slate-600">
                  Keep this compact mode open while you work. Starter prompts pre-fill the composer so you can edit before sending.
                </CardDescription>

                <div className="mt-4 flex flex-wrap gap-2">
                  {WIDGET_STARTER_PRESETS.map((starter) => (
                    <Button
                      key={starter.id}
                      type="button"
                      variant="outline"
                      className="pb-starter-chip h-10 max-w-full rounded-full px-4 text-sm whitespace-normal transition-transform duration-200 hover:-translate-y-0.5 motion-reduce:transform-none motion-reduce:transition-none"
                      title={starter.prompt}
                      onClick={() => handleStarterPrefill(starter.prompt, "widget")}
                    >
                      <SparklesIcon className="size-3.5" />
                      {starter.label}
                    </Button>
                  ))}
                </div>
              </div>

              <Button
                type="button"
                className="absolute bottom-6 right-6 h-12 cursor-pointer rounded-full px-5 shadow-lg transition-transform duration-200 hover:-translate-y-0.5 motion-reduce:transform-none motion-reduce:transition-none"
                onClick={() => setWidgetOpen((open) => !open)}
              >
                <img src={porkbotIconTwo.src} alt="" className="size-4 shrink-0 rounded-[2px] object-contain" />
                {widgetOpen ? "Hide Porkbot" : "Open Porkbot"}
              </Button>

              <div
                className={cn(
                  "absolute bottom-24 right-4 w-[min(470px,calc(100%-2rem))] transition-all duration-200 motion-reduce:transition-none md:right-6",
                  widgetOpen
                    ? "pointer-events-auto translate-y-0 scale-100 opacity-100"
                    : "pointer-events-none translate-y-3 scale-[0.98] opacity-0"
                )}
              >
                <ChatSurface
                  compact
                  composerValue={composerValue}
                  errorMessage={errorMessage}
                  inputRef={widgetInputRef}
                  isSubmitting={isSubmitting}
                  isWaitingForFirstToken={isWaitingForFirstToken}
                  messages={messages}
                  onComposerChange={setComposerValue}
                  onFormSubmit={handleFormSubmit}
                  onPromptSelect={handleInlinePrompt}
                  onReset={resetConversation}
                  activeAssistantMessageId={activeAssistantMessageId}
                />
              </div>
            </Card>
          </TabsContent>

          <TabsContent
            value="full"
            className="mt-2 data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:duration-200 motion-reduce:data-[state=active]:animate-none"
          >
            <div className="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">
              <Card className="border-border/80 bg-white/80">
                <CardHeader>
                  <CardTitle className="font-[Figtree] text-2xl tracking-tight text-slate-900">
                    Full Conversation Mode
                  </CardTitle>
                  <CardDescription>
                    Best for multi-step herd planning and deeper diagnostics. Choose a starter to pre-fill the composer.
                  </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col gap-2">
                  {STARTER_PRESETS.map((starter) => (
                    <Button
                      key={starter.id}
                      type="button"
                      variant="outline"
                      className="pb-starter-chip h-auto min-h-11 justify-start rounded-xl py-2 text-left whitespace-normal transition-colors duration-200 motion-reduce:transition-none"
                      title={starter.prompt}
                      onClick={() => handleStarterPrefill(starter.prompt, "full")}
                    >
                      <SparklesIcon className="size-4" />
                      {starter.label}
                    </Button>
                  ))}
                </CardContent>
              </Card>

              <ChatSurface
                composerValue={composerValue}
                errorMessage={errorMessage}
                inputRef={fullInputRef}
                isSubmitting={isSubmitting}
                isWaitingForFirstToken={isWaitingForFirstToken}
                messages={messages}
                onComposerChange={setComposerValue}
                onFormSubmit={handleFormSubmit}
                onPromptSelect={handleInlinePrompt}
                onReset={resetConversation}
                activeAssistantMessageId={activeAssistantMessageId}
              />
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </section>
  );
}
