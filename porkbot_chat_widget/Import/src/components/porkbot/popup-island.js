const DEFAULT_WEBCHAT_SCRIPT_URL = 'https://cdn.botframework.com/botframework-webchat/latest/webchat.js';

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

const chatIcon = `
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
  </svg>`;

function getChatTitleIcon(config) {
  if (config.openButtonIcon) {
    return `<img class="pb-chat-icon" src="${escapeHtml(config.openButtonIcon)}" alt="" loading="lazy" decoding="async" />`;
  }

  return chatIcon;
}

const restartIcon = `
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
    <path d="M3 3v5h5"></path>
  </svg>`;

const closeIcon = `
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <line x1="18" y1="6" x2="6" y2="18"></line>
    <line x1="6" y1="6" x2="18" y2="18"></line>
  </svg>`;

function iconSvg(kind) {
  if (kind === 'spark') {
    return `
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 2l1.4 6.1L20 12l-6.6 3.9L12 22l-1.4-6.1L4 12l6.6-3.9L12 2z" fill="#f59e0b"></path>
        <path d="M5 4l.7 3.1L9 9l-3.3 1.9L5 14l-.7-3.1L1 9l3.3-1.9L5 4z" fill="#38bdf8" opacity="0.92"></path>
      </svg>`;
  }

  if (kind === 'bulb') {
    return `
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 2a7 7 0 0 0-4 12.7V18a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-3.3A7 7 0 0 0 12 2z" fill="#f59e0b"></path>
        <path d="M9 22h6" stroke="#111827" stroke-width="2" stroke-linecap="round"></path>
      </svg>`;
  }

  return `
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M6 5h12a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H10l-4 3v-3H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3z" fill="#2563eb" opacity="0.94"></path>
      <path d="M8 9h8M8 12h6" stroke="#ffffff" stroke-width="2" stroke-linecap="round"></path>
    </svg>`;
}

function getIslandMarkup(config) {
  return `
    <div class="pb-shell">
      <span class="pb-orb pb-orb--one" aria-hidden="true"></span>
      <span class="pb-orb pb-orb--two" aria-hidden="true"></span>

      <header class="pb-modebar">
        <div>
          <span class="pb-kicker">Iowa State Digital Assistant</span>
          <h2 class="pb-title">${escapeHtml(config.islandTitle || config.assistantName)} <span>Modes</span></h2>
          <p class="pb-subtitle">${escapeHtml(config.subtitle)}</p>
        </div>

        <div class="pb-tab-wrap">
          <div class="pb-tablist" role="tablist" aria-label="Chat mode switcher" data-tablist>
            <button
              id="pb-tab-widget"
              class="pb-tab"
              type="button"
              role="tab"
              aria-selected="true"
              aria-controls="pb-panel-widget"
              data-mode-tab="widget"
            >
              Widget
            </button>
            <button
              id="pb-tab-full"
              class="pb-tab"
              type="button"
              role="tab"
              aria-selected="false"
              aria-controls="pb-panel-full"
              data-mode-tab="full"
              tabindex="-1"
            >
              Full Page
            </button>
          </div>
          <p class="pb-hint">Switch layouts without leaving the page.</p>
        </div>
      </header>

      <div class="pb-panels">
        <section
          id="pb-panel-widget"
          class="pb-panel"
          role="tabpanel"
          aria-labelledby="pb-tab-widget"
          data-mode-panel="widget"
        >
          <div class="pb-widget-stage">
            <div class="pb-widget-deco pb-widget-deco--analytics" aria-hidden="true"></div>
            <div class="pb-widget-deco pb-widget-deco--feed" aria-hidden="true"></div>

            <div class="pb-widget-copy">
              <h3>${escapeHtml(config.widgetTitle)}</h3>
              <p>${escapeHtml(config.widgetBody)}</p>
            </div>

            <aside class="pb-widget-popup" data-widget-popup aria-label="${escapeHtml(config.assistantName)} widget chat">
              <header class="pb-chat-header">
                <div class="pb-chat-title">
                  ${getChatTitleIcon(config)}
                  <span class="pb-chat-title-text">${escapeHtml(config.assistantName)}</span>
                </div>

                <div class="pb-chat-actions">
                  <button class="pb-action" type="button" data-restart-mode="widget" aria-label="Restart widget conversation">
                    ${restartIcon}
                  </button>
                  <button class="pb-action" type="button" data-widget-close aria-label="Close widget chat">
                    ${closeIcon}
                  </button>
                </div>
              </header>

              <section class="pb-chat-intro" aria-label="Widget conversation starters">
                <h4>Need help? <span>Start here.</span></h4>
                <p>Pick a prompt or start typing to begin.</p>
                <div class="pb-starters" data-starters-widget></div>
              </section>

              <div class="pb-webchat" data-webchat-mode="widget" aria-label="Widget chat conversation"></div>
            </aside>

            <button class="pb-widget-launch" type="button" data-widget-open aria-label="${escapeHtml(config.openButtonLabel)}">
              <img src="${escapeHtml(config.openButtonIcon)}" alt="" loading="lazy" decoding="async" />
              <span>Open Porkbot</span>
            </button>
          </div>
        </section>

        <section
          id="pb-panel-full"
          class="pb-panel"
          role="tabpanel"
          aria-labelledby="pb-tab-full"
          data-mode-panel="full"
          hidden
        >
          <div class="pb-full-layout">
            <aside class="pb-full-sidebar">
              <h3>${escapeHtml(config.fullPageTitle)} <span>Mode</span></h3>
              <p>${escapeHtml(config.fullPageBody)}</p>
              <div class="pb-starters is-column" data-starters-full></div>
            </aside>

            <section class="pb-full-chat" aria-label="${escapeHtml(config.assistantName)} full page chat">
              <header class="pb-chat-header">
                <div class="pb-chat-title">
                  ${getChatTitleIcon(config)}
                  <span class="pb-chat-title-text">${escapeHtml(config.assistantName)}</span>
                </div>

                <div class="pb-chat-actions">
                  <button class="pb-action" type="button" data-restart-mode="full" aria-label="Restart full page conversation">
                    ${restartIcon}
                  </button>
                </div>
              </header>

              <div class="pb-webchat" data-webchat-mode="full" aria-label="Full page chat conversation"></div>
            </section>
          </div>
        </section>
      </div>
    </div>
  `;
}

function getRegionalSettingsUrl(tokenEndpoint) {
  const environmentEndpoint = tokenEndpoint.slice(0, tokenEndpoint.indexOf('/powervirtualagents'));
  const apiVersion = tokenEndpoint.slice(tokenEndpoint.indexOf('api-version')).split('=')[1];
  return `${environmentEndpoint}/powervirtualagents/regionalchannelsettings?api-version=${apiVersion}`;
}

function createCustomStore(WebChat) {
  return WebChat.createStore(
    {},
    ({ dispatch }) =>
      (next) =>
      (action) => {
        if (action.type === 'DIRECT_LINE/CONNECT_FULFILLED') {
          dispatch({
            type: 'DIRECT_LINE/POST_ACTIVITY',
            meta: { method: 'keyboard' },
            payload: {
              activity: {
                channelData: { postBack: true },
                name: 'startConversation',
                type: 'event'
              }
            }
          });
        }

        return next(action);
      }
  );
}

function ensureWebChatScript(scriptUrl) {
  if (window.WebChat) {
    return Promise.resolve(window.WebChat);
  }

  if (window.__porkbotWebChatPromise) {
    return window.__porkbotWebChatPromise;
  }

  window.__porkbotWebChatPromise = new Promise((resolve, reject) => {
    const existingScript = document.querySelector('script[data-porkbot-webchat-script]');

    if (existingScript) {
      existingScript.addEventListener('load', () => resolve(window.WebChat), { once: true });
      existingScript.addEventListener('error', () => reject(new Error('Failed to load Web Chat script.')), {
        once: true
      });
      return;
    }

    const script = document.createElement('script');
    script.src = scriptUrl;
    script.async = true;
    script.dataset.porkbotWebchatScript = 'true';
    script.addEventListener('load', () => resolve(window.WebChat), { once: true });
    script.addEventListener('error', () => reject(new Error('Failed to load Web Chat script.')), {
      once: true
    });

    document.head.appendChild(script);
  });

  return window.__porkbotWebChatPromise;
}

function createState(root, config) {
  root.innerHTML = getIslandMarkup(config);

  return {
    root,
    config,
    currentMode: config.defaultMode === 'full' ? 'full' : 'widget',
    directLineUrl: null,
    sessions: {
      widget: { directLine: null, initialized: false },
      full: { directLine: null, initialized: false }
    },
    elements: {
      tabList: root.querySelector('[data-tablist]'),
      tabButtons: Array.from(root.querySelectorAll('[data-mode-tab]')),
      panels: {
        widget: root.querySelector('[data-mode-panel="widget"]'),
        full: root.querySelector('[data-mode-panel="full"]')
      },
      widget: {
        popup: root.querySelector('[data-widget-popup]'),
        open: root.querySelector('[data-widget-open]'),
        close: root.querySelector('[data-widget-close]'),
        starters: root.querySelector('[data-starters-widget]'),
        webchat: root.querySelector('[data-webchat-mode="widget"]')
      },
      full: {
        starters: root.querySelector('[data-starters-full]'),
        webchat: root.querySelector('[data-webchat-mode="full"]')
      },
      restarts: Array.from(root.querySelectorAll('[data-restart-mode]'))
    }
  };
}

function applyWebchatBackground(backgroundImage, webchatElement) {
  if (!backgroundImage || !webchatElement || webchatElement.dataset.hasOverlay === 'true') {
    return;
  }

  webchatElement.style.backgroundImage = `url(${backgroundImage})`;
  webchatElement.style.backgroundSize = 'cover';
  webchatElement.style.backgroundPosition = 'center';
  webchatElement.style.backgroundRepeat = 'no-repeat';

  const overlay = document.createElement('div');
  overlay.className = 'webchat-overlay';
  webchatElement.appendChild(overlay);
  webchatElement.dataset.hasOverlay = 'true';
}

async function fetchDirectLineUrl(tokenEndpoint) {
  const response = await fetch(getRegionalSettingsUrl(tokenEndpoint));
  const data = await response.json();
  const directLineUrl = data.channelUrlsById?.directline;

  if (!directLineUrl) {
    throw new Error('Failed to get DirectLine URL');
  }

  return directLineUrl;
}

async function fetchConversationToken(tokenEndpoint) {
  const response = await fetch(tokenEndpoint);
  const conversationInfo = await response.json();

  if (!conversationInfo.token) {
    throw new Error('Failed to get conversation token');
  }

  return conversationInfo.token;
}

function getWebchatElement(state, mode) {
  return mode === 'widget' ? state.elements.widget.webchat : state.elements.full.webchat;
}

async function ensureModeChat(state, mode, { restart = false } = {}) {
  const session = state.sessions[mode];
  const webchatElement = getWebchatElement(state, mode);

  if (!webchatElement) {
    return;
  }

  if (session.initialized && !restart) {
    return;
  }

  const WebChat = await ensureWebChatScript(
    state.config.webChatScriptUrl || DEFAULT_WEBCHAT_SCRIPT_URL
  );

  if (!WebChat) {
    throw new Error('Web Chat runtime unavailable.');
  }

  if (!state.directLineUrl) {
    state.directLineUrl = await fetchDirectLineUrl(state.config.tokenEndpoint);
  }

  if (restart) {
    webchatElement.innerHTML = '';
    session.initialized = false;
  }

  const token = await fetchConversationToken(state.config.tokenEndpoint);
  session.directLine = WebChat.createDirectLine({
    domain: `${state.directLineUrl}v3/directline`,
    token
  });

  WebChat.renderWebChat(
    {
      directLine: session.directLine,
      styleOptions: state.config.styleOptions,
      store: createCustomStore(WebChat),
      locale: 'en-US'
    },
    webchatElement
  );

  applyWebchatBackground(state.config.backgroundImage, webchatElement);
  session.initialized = true;
}

function showWidgetPopup(state) {
  state.elements.widget.popup?.classList.add('is-open');
  state.elements.widget.open?.setAttribute('aria-expanded', 'true');
}

function hideWidgetPopup(state) {
  state.elements.widget.popup?.classList.remove('is-open');
  state.elements.widget.open?.setAttribute('aria-expanded', 'false');
}

function focusComposer(mode, state) {
  const webchatElement = getWebchatElement(state, mode);
  if (!webchatElement) {
    return;
  }

  setTimeout(() => {
    const input = webchatElement.querySelector("input[type='text'], textarea");
    input?.focus();
  }, 200);
}

async function sendMessage(state, mode, text) {
  try {
    await ensureModeChat(state, mode);
  } catch (error) {
    console.error(`Failed to initialize ${mode} chat before sending message:`, error);
    return;
  }

  const session = state.sessions[mode];
  if (!session.directLine) {
    return;
  }

  session.directLine
    .postActivity({
      type: 'message',
      from: { id: 'user' },
      text
    })
    .subscribe();

  focusComposer(mode, state);
}

function createStarterButton(starter, onClick) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'pb-starter';
  button.setAttribute('aria-label', `Conversation starter: ${starter.send}`);

  const icon = document.createElement('span');
  icon.className = 'pb-starter-icon';
  icon.innerHTML = iconSvg(starter.icon);

  const text = document.createElement('span');
  text.textContent = starter.label;

  button.appendChild(icon);
  button.appendChild(text);
  button.addEventListener('click', onClick);

  return button;
}

function renderStarters(state) {
  const starters = Array.isArray(state.config.conversationStarters)
    ? state.config.conversationStarters
    : [];

  const widgetRoot = state.elements.widget.starters;
  const fullRoot = state.elements.full.starters;

  if (widgetRoot) {
    widgetRoot.innerHTML = '';
    starters.forEach((starter) => {
      widgetRoot.appendChild(
        createStarterButton(starter, async () => {
          showWidgetPopup(state);
          await sendMessage(state, 'widget', starter.send);
        })
      );
    });
  }

  if (fullRoot) {
    fullRoot.innerHTML = '';
    starters.forEach((starter) => {
      fullRoot.appendChild(
        createStarterButton(starter, async () => {
          setMode(state, 'full');
          await sendMessage(state, 'full', starter.send);
        })
      );
    });
  }
}

function setMode(state, mode, { focusTab = false } = {}) {
  if (mode !== 'widget' && mode !== 'full') {
    return;
  }

  state.currentMode = mode;
  state.root.dataset.activeMode = mode;

  state.elements.tabButtons.forEach((button) => {
    const buttonMode = button.getAttribute('data-mode-tab');
    const isSelected = buttonMode === mode;
    button.setAttribute('aria-selected', String(isSelected));
    button.setAttribute('tabindex', isSelected ? '0' : '-1');

    if (isSelected && focusTab) {
      button.focus();
    }
  });

  Object.entries(state.elements.panels).forEach(([panelMode, panel]) => {
    if (!panel) {
      return;
    }

    panel.hidden = panelMode !== mode;
  });

  if (mode === 'full') {
    hideWidgetPopup(state);
    ensureModeChat(state, 'full').catch((error) => {
      console.error('Failed to initialize full page chat:', error);
    });
  }
}

function wireTabs(state) {
  state.elements.tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const mode = button.getAttribute('data-mode-tab');
      setMode(state, mode || 'widget');
    });
  });

  state.elements.tabList?.addEventListener('keydown', (event) => {
    const { key } = event;
    if (key !== 'ArrowLeft' && key !== 'ArrowRight') {
      return;
    }

    event.preventDefault();

    const modes = ['widget', 'full'];
    const currentIndex = modes.indexOf(state.currentMode);
    const delta = key === 'ArrowRight' ? 1 : -1;
    const nextMode = modes[(currentIndex + delta + modes.length) % modes.length];
    setMode(state, nextMode, { focusTab: true });
  });
}

function wireActions(state) {
  state.elements.widget.open?.addEventListener('click', () => {
    setMode(state, 'widget');
    showWidgetPopup(state);
    ensureModeChat(state, 'widget').catch((error) => {
      console.error('Failed to initialize widget chat:', error);
    });
  });

  state.elements.widget.close?.addEventListener('click', () => hideWidgetPopup(state));

  state.elements.restarts.forEach((button) => {
    button.addEventListener('click', async () => {
      const mode = button.getAttribute('data-restart-mode');
      if (!mode) {
        return;
      }

      try {
        await ensureModeChat(state, mode, { restart: true });
        focusComposer(mode, state);
      } catch (error) {
        console.error(`Failed to restart ${mode} chat:`, error);
      }
    });
  });
}

export function mountPorkbotPopup(root, config) {
  if (!root || root.dataset.porkbotMounted === 'true') {
    return;
  }

  root.dataset.porkbotMounted = 'true';

  const state = createState(root, config);
  renderStarters(state);
  wireTabs(state);
  wireActions(state);

  setMode(state, state.currentMode);

  if (config.openByDefault) {
    showWidgetPopup(state);
    ensureModeChat(state, 'widget').catch((error) => {
      console.error('Failed to initialize default widget chat:', error);
    });
  }

  if (state.currentMode === 'full') {
    ensureModeChat(state, 'full').catch((error) => {
      console.error('Failed to initialize default full chat:', error);
    });
  }
}

function mountFromRoot(root) {
  const configRaw = root.getAttribute('data-porkbot-config');
  if (!configRaw) {
    return;
  }

  try {
    const config = JSON.parse(configRaw);
    mountPorkbotPopup(root, config);
  } catch (error) {
    console.error('Failed to parse Porkbot config:', error);
  }
}

function boot() {
  const roots = document.querySelectorAll('[data-porkbot-root]');
  roots.forEach((root) => mountFromRoot(root));
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}
