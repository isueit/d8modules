(function (Drupal, once) {
  'use strict';

  var CHAT_TITLE = 'Iowa State Extension Porkbot';
  var DIRECT_LINE_TOKEN_ENDPOINT = 'https://extension-porkbot-eqc3ekd5ena0hdb5.centralus-01.azurewebsites.net/api/directline/token';

  Drupal.behaviors.porkbot = {
    attach: function (context) {
      once('porkbot-init', '#porkbot-toggle', context).forEach(function (toggleButton) {
        var widget = document.getElementById('porkbot-widget');
        var closeButton = document.getElementById('porkbot-close');
        var statusElement = document.getElementById('porkbot-status');
        var webChatElement = document.getElementById('porkbot-webchat');
        var disclaimer = document.getElementById('porkbot-disclaimer');
        var disclaimerToggle = document.getElementById('porkbot-disclaimer-toggle');
        var disclaimerFull = document.getElementById('porkbot-disclaimer-full');

        var initialized = false;
        var loading = false;
        var waitingForBotResponse = false;
        var sendBoxObserver = null;

        function setWidgetOpen(isOpen) {
          widget.classList.toggle('is-hidden', !isOpen);
          toggleButton.setAttribute('aria-expanded', String(isOpen));
          toggleButton.setAttribute('aria-label', (isOpen ? 'Close ' : 'Open ') + CHAT_TITLE);

          if (isOpen) {
            closeButton.focus();
          } else {
            toggleButton.focus();
          }
        }

        function setStatus(message) {
          statusElement.textContent = message || '';
          statusElement.hidden = !message;
        }

        function applyChatInputDisabledState() {
          webChatElement.classList.toggle('chat-input-disabled', waitingForBotResponse);

          var sendBoxControls = webChatElement.querySelectorAll(
            "[class*='send-box'] textarea, [class*='send-box'] input, [class*='send-box'] button, [class*='send-box'] [contenteditable]"
          );

          sendBoxControls.forEach(function (control) {
            if ('disabled' in control) {
              control.disabled = waitingForBotResponse;
            }

            control.setAttribute('aria-disabled', String(waitingForBotResponse));

            if (control.getAttribute('contenteditable') === 'true' && waitingForBotResponse) {
              control.setAttribute('contenteditable', 'false');
            } else if (control.getAttribute('contenteditable') === 'false' && !waitingForBotResponse) {
              control.setAttribute('contenteditable', 'true');
            }
          });
        }

        function setChatInputDisabled(isDisabled) {
          waitingForBotResponse = isDisabled;
          applyChatInputDisabledState();
        }

        function observeWebChatSendBox() {
          if (sendBoxObserver) {
            return;
          }

          sendBoxObserver = new MutationObserver(applyChatInputDisabledState);
          sendBoxObserver.observe(webChatElement, {
            childList: true,
            subtree: true
          });
        }

        function isUserSubmittedActivity(action) {
          var webChatSendAction = [
            'WEB_CHAT/SEND_MESSAGE',
            'WEB_CHAT/SEND_MESSAGE_BACK',
            'WEB_CHAT/SEND_POST_BACK'
          ].includes(action.type);

          var activity = action.payload && action.payload.activity;
          var directLineUserMessage =
            action.type === 'DIRECT_LINE/POST_ACTIVITY' &&
            activity &&
            activity.type === 'message' &&
            activity.from &&
            activity.from.role === 'user';

          return webChatSendAction || directLineUserMessage;
        }

        function isBotMessageActivity(action) {
          var activity = action.payload && action.payload.activity;

          return (
            action.type === 'DIRECT_LINE/INCOMING_ACTIVITY' &&
            activity &&
            activity.type === 'message' &&
            activity.from &&
            activity.from.role === 'bot'
          );
        }

        function isPostActivityFailure(action) {
          return [
            'DIRECT_LINE/POST_ACTIVITY_REJECTED',
            'DIRECT_LINE/POST_ACTIVITY_FAILED',
            'WEB_CHAT/SEND_MESSAGE_REJECTED'
          ].includes(action.type);
        }

        async function createDirectLine() {
          if (!DIRECT_LINE_TOKEN_ENDPOINT) {
            throw new Error('Direct Line token broker URL is not configured.');
          }

          var response = await fetch(DIRECT_LINE_TOKEN_ENDPOINT, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            }
          });

          if (!response.ok) {
            throw new Error('Direct Line token endpoint failed with ' + response.status + '.');
          }

          var body = await response.json();
          if (!body.token) {
            throw new Error('Direct Line token endpoint did not return a token.');
          }

          return window.WebChat.createDirectLine({ token: body.token });
        }

        async function loadChat() {
          if (initialized || loading) {
            setWidgetOpen(true);
            return;
          }

          loading = true;
          setWidgetOpen(true);
          setStatus('Loading chat...');

          try {
            if (!window.WebChat) {
              throw new Error('Bot Framework Web Chat did not load.');
            }

            var welcomeMessageSent = false;
            var store = window.WebChat.createStore({}, function (_ref) {
              var dispatch = _ref.dispatch;

              return function (next) {
                return function (action) {
                  if (action.type === 'DIRECT_LINE/CONNECT_FULFILLED' && !welcomeMessageSent) {
                    welcomeMessageSent = true;
                    dispatch({
                      type: 'DIRECT_LINE/POST_ACTIVITY',
                      meta: {
                        method: "keyboard"
                      },
                      payload: {
                        activity: {
                          channelData: {
                            postBack: true
                          },
                          name: "startConversation",
                          type: "event"
                        }
                      }
                    });
                  }

                  var result = next(action);

                  if (isUserSubmittedActivity(action)) {
                    setChatInputDisabled(true);
                  } else if (isBotMessageActivity(action) || isPostActivityFailure(action)) {
                    setChatInputDisabled(false);
                  }

                  return result;
                };
              };
            });

            window.WebChat.renderWebChat(
              {
                directLine: await createDirectLine(),
                store: store,
                styleOptions: {
                  hideUploadButton: true,
                  botAvatarInitials: '',
                  userAvatarInitials: '',
                  avatarSize: 0,
                  botAvatarBackgroundColor: 'transparent',
                  userAvatarBackgroundColor: 'transparent',
                  bubbleBackground: '#f5f5f5',
                  bubbleBorderColor: '#e3ded4',
                  bubbleBorderRadius: 14,
                  bubbleFromUserBackground: '#c8102e',
                  bubbleFromUserBorderRadius: 14,
                  bubbleFromUserTextColor: '#ffffff',
                  sendBoxBackground: '#ffffff',
                  sendBoxButtonColor: '#c8102e',
                  sendBoxButtonColorOnFocus: '#9b0c23',
                  sendBoxButtonColorOnHover: '#9b0c23',
                  sendBoxTextColor: '#1f2933',
                  subtle: '#5f6368',
                  timestampColor: '#5f6368'
                }
              },
              webChatElement
            );

            observeWebChatSendBox();
            applyChatInputDisabledState();
            initialized = true;
            setStatus('');
          } catch (error) {
            console.error('Failed to initialize Porkbot chat:', error);
            setStatus('Chat is unavailable right now. Please try again shortly.');
          } finally {
            loading = false;
          }
        }

        toggleButton.addEventListener('click', function () {
          if (widget.classList.contains('is-hidden')) {
            loadChat();
          } else {
            setWidgetOpen(false);
          }
        });

        closeButton.addEventListener('click', function () {
          setWidgetOpen(false);
        });

        disclaimerToggle.addEventListener('click', function () {
          var isExpanded = disclaimerToggle.getAttribute('aria-expanded') === 'true';

          disclaimerToggle.setAttribute('aria-expanded', String(!isExpanded));
          disclaimer.classList.toggle('is-collapsed', isExpanded);
          disclaimerFull.hidden = isExpanded;
        });

        document.addEventListener('keydown', function (event) {
          if (event.key !== 'Escape') {
            return;
          }

          if (widget.classList.contains('is-hidden') || !widget.contains(document.activeElement)) {
            return;
          }

          setWidgetOpen(false);
        });
      });
    }
  };
})(Drupal, once);
