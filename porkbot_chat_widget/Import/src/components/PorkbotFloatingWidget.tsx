"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import _porkbotIconOne from "@/assets/PorkChatBot-01.png";
import _porkbotIconTwo from "@/assets/PorkChatBot-02.png";
import { createPorkbotApiClient, PorkbotApiError } from "@/lib/porkbot-api-client";
import { getDefaultRuntimeConfig, readRuntimeConfigFromWindow } from "@/lib/porkbot-runtime-config";
import { MessageResponse } from "@/components/ai-elements/message";
import { RefreshCcwIcon, SendHorizontalIcon, SparklesIcon, XIcon } from "lucide-react";

// Vite returns a plain string URL; Astro wraps it in { src }
const toUrl = (img: unknown): string =>
  img && typeof img === "object" && "src" in (img as object)
    ? (img as { src: string }).src
    : (img as string);

const porkbotIconOne = toUrl(_porkbotIconOne);
const porkbotIconTwo = toUrl(_porkbotIconTwo);

const WIDGET_STYLES = `
  #porkbot-chat-widget-root, #porkbot-chat-widget-root * { box-sizing: border-box; }

  /* Remove p margins inside message bubbles */
  #porkbot-chat-widget-root .pb-msg-body p { margin: 0; }
  #porkbot-chat-widget-root .pb-msg-body p + p { margin-top: 0.4em; }

  /* Header icon buttons — 44px touch target */
  #porkbot-chat-widget-root .pb-icon-btn {
    background: transparent;
    border: none;
    color: #ffffff;
    cursor: pointer;
    min-width: 44px;
    min-height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 150ms ease;
    font-family: inherit;
  }
  #porkbot-chat-widget-root .pb-icon-btn:hover { background: rgba(255,255,255,0.2); }
  #porkbot-chat-widget-root .pb-icon-btn:focus-visible {
    outline: 3px solid #f1be48;
    outline-offset: 2px;
  }

  /* Starter chip buttons — 44px touch target */
  #porkbot-chat-widget-root .pb-starter-btn {
    border: 1px solid #d7dee9;
    border-radius: 999px;
    background: #ffffff;
    color: #1f2937;
    padding: 0 12px;
    min-height: 44px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 150ms ease, border-color 150ms ease;
    font-family: inherit;
  }
  #porkbot-chat-widget-root .pb-starter-btn:hover {
    background: #fffbeb;
    border-color: #f1be48;
  }
  #porkbot-chat-widget-root .pb-starter-btn:focus-visible {
    outline: 3px solid #003d4c;
    outline-offset: 2px;
  }

  /* Send button — 44px touch target */
  #porkbot-chat-widget-root .pb-send-btn {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 150ms ease, transform 150ms ease;
    font-family: inherit;
  }
  #porkbot-chat-widget-root .pb-send-btn:not(:disabled):hover {
    background: #7c2529 !important;
    transform: translateY(-1px);
  }
  #porkbot-chat-widget-root .pb-send-btn:disabled { cursor: not-allowed; }
  #porkbot-chat-widget-root .pb-send-btn:focus-visible {
    outline: 3px solid #003d4c;
    outline-offset: 2px;
  }

  /* Text input focus */
  #porkbot-chat-widget-root .pb-text-input:focus {
    outline: none;
    border-color: #003d4c;
    box-shadow: 0 0 0 3px rgba(0,61,76,0.2);
  }

  /* Toggle button */
  #porkbot-chat-widget-root .pb-toggle-btn {
    background: none;
    border: 2px solid transparent;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 200ms ease, filter 200ms ease;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.25));
    border-radius: 50%;
    pointer-events: auto;
  }
  #porkbot-chat-widget-root .pb-toggle-btn:hover {
    transform: translateY(-2px);
    filter: drop-shadow(0 6px 12px rgba(0,0,0,0.35));
  }
  #porkbot-chat-widget-root .pb-toggle-btn:focus-visible {
    outline: 3px solid #003d4c;
    outline-offset: 4px;
  }

  /* Responsive: short viewports */
  @media (max-height: 700px) {
    #porkbot-chat-widget-root .pb-panel { height: calc(100svh - 130px) !important; }
  }
  /* Responsive: narrow viewports */
  @media (max-width: 480px) {
    #porkbot-chat-widget-root .pb-panel { width: calc(100vw - 16px) !important; }
    #porkbot-chat-widget-root .pb-outer { right: 8px !important; bottom: 12px !important; }
  }
`;

type ChatRole = "user" | "assistant";
type ChatMessage = { id: string; role: ChatRole; text: string };

const STARTERS = [
  { id: "ft", label: "Farrowing Temp", prompt: "What is the ideal temperature range for a farrowing room?" },
  { id: "fe", label: "Feed Efficiency", prompt: "How can I improve feed efficiency in grow-finish pigs?" },
  { id: "bs", label: "Biosecurity", prompt: "What are the most important daily biosecurity practices for pork production?" },
];

const INITIAL_MESSAGE = "Hi, I'm Porkbot. Ask me about herd care, nutrition, housing, or biosecurity.";
const RESTART_MESSAGE = "Conversation restarted. What pork production question can I help with now?";
const createId = () =>
  typeof crypto !== "undefined" && "randomUUID" in crypto
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
const isAbortError = (e: unknown) => e instanceof Error && e.name === "AbortError";
const getErrorMsg = (e: unknown) =>
  e instanceof PorkbotApiError ? e.message : "Couldn't reach the chat service. Please try again.";

export default function PorkbotFloatingWidget() {
  const defaultConfig = useMemo(() => getDefaultRuntimeConfig(), []);
  const [runtimeConfig, setRuntimeConfig] = useState(defaultConfig);

  // Inject scoped styles once
  useEffect(() => {
    const id = "pb-widget-styles";
    if (!document.getElementById(id)) {
      const el = document.createElement("style");
      el.id = id;
      el.textContent = WIDGET_STYLES;
      document.head.appendChild(el);
    }
  }, []);

  useEffect(() => {
    setRuntimeConfig((c) => readRuntimeConfigFromWindow(c));
  }, []);

  const apiClient = useMemo(
    () => createPorkbotApiClient({ baseUrl: runtimeConfig.apiBaseUrl, apiPrefix: runtimeConfig.apiPrefix }),
    [runtimeConfig.apiBaseUrl, runtimeConfig.apiPrefix]
  );

  const [open, setOpen] = useState(
    runtimeConfig.defaultMode === "widget" && runtimeConfig.openByDefault
  );
  const [messages, setMessages] = useState<ChatMessage[]>([
    { id: createId(), role: "assistant", text: INITIAL_MESSAGE },
  ]);
  const [composerValue, setComposerValue] = useState("");
  const [status, setStatus] = useState<"ready" | "submitted" | "error">("ready");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [sessionId, setSessionId] = useState<string | undefined>(undefined);
  const [activeAssistantId, setActiveAssistantId] = useState<string | null>(null);

  const inputRef = useRef<HTMLInputElement | null>(null);
  const toggleBtnRef = useRef<HTMLButtonElement | null>(null);
  const activeRequestRef = useRef<AbortController | null>(null);
  const messagesEndRef = useRef<HTMLDivElement | null>(null);
  const isMounted = useRef(false);

  const isSubmitting = status === "submitted";
  const streamingText = activeAssistantId
    ? (messages.find((m) => m.id === activeAssistantId)?.text ?? "")
    : "";
  const isWaitingForFirstToken = isSubmitting && !!activeAssistantId && !streamingText.trim();

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  // Focus input on open; return focus to toggle on close (skip initial mount)
  useEffect(() => {
    if (!isMounted.current) {
      isMounted.current = true;
      return;
    }
    if (open) {
      requestAnimationFrame(() => inputRef.current?.focus());
    } else {
      toggleBtnRef.current?.focus();
    }
  }, [open]);

  // Escape key closes the panel
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => { if (e.key === "Escape") setOpen(false); };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, [open]);

  const resetConversation = useCallback(() => {
    activeRequestRef.current?.abort();
    activeRequestRef.current = null;
    setSessionId(undefined);
    setErrorMessage(null);
    setStatus("ready");
    setActiveAssistantId(null);
    setMessages([{ id: createId(), role: "assistant", text: RESTART_MESSAGE }]);
  }, []);

  const submitPrompt = useCallback(
    async (rawPrompt: string) => {
      const prompt = rawPrompt.trim();
      if (!prompt || isSubmitting) return;

      setErrorMessage(null);
      setStatus("submitted");
      setComposerValue("");
      setMessages((m) => [...m, { id: createId(), role: "user", text: prompt }]);

      const controller = new AbortController();
      activeRequestRef.current = controller;
      const assistantId = createId();
      let gotDelta = false;
      setActiveAssistantId(assistantId);
      setMessages((m) => [...m, { id: assistantId, role: "assistant", text: "" }]);

      try {
        await apiClient.streamQuery({
          query: prompt,
          sessionId,
          signal: controller.signal,
          onEvent: (event) => {
            if (event.type === "response.output_text.delta") {
              gotDelta = true;
              setMessages((m) =>
                m.map((msg) => (msg.id === assistantId ? { ...msg, text: msg.text + event.delta } : msg))
              );
            } else if (event.type === "response.completed") {
              if (event.session_id) setSessionId(event.session_id);
              if (!gotDelta) {
                const text = event.response?.output?.[0]?.content?.[0]?.text?.trim() ?? "";
                if (text) setMessages((m) => m.map((msg) => (msg.id === assistantId ? { ...msg, text } : msg)));
              }
            }
          },
        });
        setStatus("ready");
      } catch (error) {
        if (isAbortError(error)) {
          setMessages((m) => m.filter((msg) => msg.id !== assistantId));
          setStatus("ready");
          return;
        }
        setErrorMessage(getErrorMsg(error));
        setMessages((m) =>
          m.map((msg) =>
            msg.id === assistantId
              ? { ...msg, text: msg.text.trim() || "I hit an error. Please retry in a few seconds." }
              : msg
          )
        );
        setStatus("error");
      } finally {
        activeRequestRef.current = null;
        setActiveAssistantId(null);
      }
    },
    [apiClient, isSubmitting, sessionId]
  );

  const visibleMessages = messages.filter(
    (m) => !(m.id === activeAssistantId && m.role === "assistant" && !m.text.trim())
  );
  const showStarters = !composerValue.trim() && !isSubmitting && messages.length <= 1;
  const sendDisabled = isSubmitting || !composerValue.trim();

  return (
    <div
      className="pb-outer"
      style={{
        position: "fixed",
        bottom: "24px",
        right: "24px",
        zIndex: 999999,
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-end",
        gap: "12px",
        pointerEvents: "none",
        fontFamily: "'Arial', 'Helvetica Neue', sans-serif",
      }}
    >
      {/* Chat panel */}
      <div
        className="pb-panel"
        role="dialog"
        aria-label="Porkbot chat"
        aria-modal="false"
        style={{
          pointerEvents: open ? "auto" : "none",
          opacity: open ? 1 : 0,
          transform: open ? "translateY(0) scale(1)" : "translateY(12px) scale(0.97)",
          transition: "opacity 200ms ease, transform 200ms ease",
          width: "min(420px, calc(100vw - 48px))",
          height: "560px",
          borderRadius: "16px",
          border: "1px solid rgba(148,163,184,0.3)",
          background: "#ffffff",
          boxShadow: "0 24px 50px rgba(15,23,42,0.18)",
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
        }}
      >
        {/* Header */}
        <div
          style={{
            background: "#c8102e",
            color: "#ffffff",
            padding: "0 4px 0 12px",
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            flexShrink: 0,
            minHeight: "64px",
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: "8px", fontWeight: 700, fontSize: "1.3125rem", fontFamily: "'Merriweather', serif" }}>
            <img src={porkbotIconOne} alt="" aria-hidden="true" style={{ width: "60px", height: "60px", borderRadius: "3px", objectFit: "contain" }} />
            Porkbot
          </div>
          <div style={{ display: "flex", alignItems: "center" }}>
            <button className="pb-icon-btn" onClick={resetConversation} aria-label="Reset conversation">
              <RefreshCcwIcon size={16} aria-hidden="true" />
            </button>
            <button className="pb-icon-btn" onClick={() => setOpen(false)} aria-label="Close chat">
              <XIcon size={16} aria-hidden="true" />
            </button>
          </div>
        </div>

        {/* Messages */}
        <div
          role="log"
          aria-label="Conversation"
          aria-live="polite"
          aria-relevant="additions"
          style={{ flex: 1, overflowY: "auto", padding: "12px", display: "flex", flexDirection: "column", gap: "10px" }}
        >
          {visibleMessages.map((msg) => (
            <div
              key={msg.id}
              style={{ display: "flex", justifyContent: msg.role === "user" ? "flex-end" : "flex-start" }}
            >
              <div
                style={{
                  maxWidth: "88%",
                  padding: "10px 13px",
                  borderRadius: msg.role === "user" ? "16px 16px 4px 16px" : "16px 16px 16px 4px",
                  background: msg.role === "user" ? "#ebebeb" : "#ffffff",
                  border: msg.role === "user" ? "1px solid rgba(200,16,46,0.3)" : "1px solid #e2e8f0",
                  fontSize: "0.875rem",
                  lineHeight: "1.55",
                  color: "#111827",
                  boxShadow: "0 1px 3px rgba(0,0,0,0.06)",
                }}
              >
                {msg.role === "assistant" ? (
                  <MessageResponse className="pb-msg-body min-w-0 whitespace-pre-wrap break-words [&_ol]:pl-5 [&_ul]:pl-5">
                    {msg.text}
                  </MessageResponse>
                ) : (
                  <span style={{ whiteSpace: "pre-wrap", wordBreak: "break-word" }}>{msg.text}</span>
                )}
              </div>
            </div>
          ))}

          {isWaitingForFirstToken && (
            <div style={{ display: "flex", justifyContent: "flex-start" }}>
              <div
                style={{
                  padding: "10px 14px",
                  borderRadius: "16px 16px 16px 4px",
                  background: "#ffffff",
                  border: "1px solid #e2e8f0",
                  boxShadow: "0 1px 3px rgba(0,0,0,0.06)",
                }}
              >
                <span className="pb-typing-bubble" role="status" aria-label="Porkbot is typing">
                  <span className="pb-typing-dot pb-typing-dot--1" />
                  <span className="pb-typing-dot pb-typing-dot--2" />
                  <span className="pb-typing-dot pb-typing-dot--3" />
                </span>
              </div>
            </div>
          )}
          <div ref={messagesEndRef} aria-hidden="true" />
        </div>

        {/* Starters + input */}
        <div
          style={{
            borderTop: "1px solid #e2e8f0",
            background: "rgba(248,250,252,0.9)",
            padding: "10px 12px",
            flexShrink: 0,
          }}
        >
          {showStarters && (
            <div role="group" aria-label="Conversation starters" style={{ display: "flex", flexWrap: "wrap", gap: "6px", marginBottom: "10px" }}>
              {STARTERS.map((s) => (
                <button
                  key={s.id}
                  className="pb-starter-btn"
                  onClick={() => setComposerValue(s.prompt)}
                  aria-label={`Starter: ${s.prompt}`}
                >
                  <SparklesIcon size={11} aria-hidden="true" />
                  {s.label}
                </button>
              ))}
            </div>
          )}

          {errorMessage && (
            <p role="alert" style={{ fontSize: "0.75rem", color: "#b91c1c", margin: "0 0 6px" }}>
              {errorMessage}
            </p>
          )}

          <form
            onSubmit={(e) => { e.preventDefault(); void submitPrompt(composerValue); }}
            style={{ display: "flex", gap: "8px" }}
          >
            <input
              ref={inputRef}
              className="pb-text-input"
              value={composerValue}
              onChange={(e) => setComposerValue(e.target.value)}
              placeholder="Ask about herd health, feed, or biosecurity..."
              aria-label="Message Porkbot"
              disabled={isSubmitting}
              style={{
                flex: 1,
                height: "44px",
                padding: "0 12px",
                borderRadius: "8px",
                border: "1px solid #d1d5db",
                fontSize: "0.8125rem",
                background: "#ffffff",
                color: "#111827",
                fontFamily: "inherit",
              }}
            />
            <button
              type="submit"
              className="pb-send-btn"
              disabled={sendDisabled}
              aria-label="Send message"
              aria-disabled={sendDisabled}
              style={{
                background: sendDisabled ? "#e5e7eb" : "#c8102e",
                color: sendDisabled ? "#6b7280" : "#ffffff",
              }}
            >
              <SendHorizontalIcon size={16} aria-hidden="true" />
            </button>
          </form>
        </div>
      </div>

      {/* Toggle button */}
      <button
        ref={toggleBtnRef}
        className="pb-toggle-btn"
        onClick={() => setOpen((o) => !o)}
        aria-label={open ? "Close Porkbot chat" : "Open Porkbot chat"}
        aria-expanded={open}
        aria-controls="porkbot-chat-widget-root"
      >
        <img
          src={porkbotIconTwo}
          alt=""
          aria-hidden="true"
          style={{ width: "100px", height: "100px", objectFit: "contain" }}
        />
      </button>
    </div>
  );
}
