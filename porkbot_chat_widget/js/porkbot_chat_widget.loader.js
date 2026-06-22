(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.porkbotChatWidgetLoader = {
    attach: function () {
      if (window.__porkbotChatWidgetScriptInjected) {
        return;
      }

      var settings = drupalSettings.porkbotChatWidget || {};
      var scriptUrl = settings.scriptUrl || '';
      if (!scriptUrl) {
        return;
      }
      var runtimeConfig = asObject(settings.runtimeConfig);
      var initOptions = mergeInitOptions(settings.initOptions, runtimeConfig);

      window.__porkbotChatWidgetScriptInjected = true;

      if (Object.keys(runtimeConfig).length) {
        window.__PORKBOT_CHAT_WIDGET_CONFIG__ = Object.assign(
          {},
          asObject(window.__PORKBOT_CHAT_WIDGET_CONFIG__),
          runtimeConfig
        );
        window.PorkbotChatWidgetConfig = window.__PORKBOT_CHAT_WIDGET_CONFIG__;
      }

      if (document.querySelector('script[data-porkbot-chat-widget]')) {
        return;
      }

      var script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      script.defer = true;
      script.setAttribute('data-porkbot-chat-widget', '1');

      script.addEventListener('load', function () {
        var initMethodPath = settings.initMethod || '';
        if (!initMethodPath) {
          return;
        }

        var resolved = resolveMethodByPath(initMethodPath);
        if (!resolved) {
          return;
        }

        resolved.method.call(resolved.context, initOptions);
      });

      document.head.appendChild(script);
    }
  };

  function resolveMethodByPath(path) {
    if (!path) {
      return null;
    }

    var segments = path.split('.');
    var context = window;

    for (var i = 0; i < segments.length - 1; i += 1) {
      var segment = segments[i];
      if (
        !context ||
        (typeof context !== 'object' && typeof context !== 'function') ||
        !(segment in context)
      ) {
        return null;
      }
      context = context[segment];
    }

    var methodName = segments[segments.length - 1];
    if (
      !context ||
      (typeof context !== 'object' && typeof context !== 'function') ||
      !(methodName in context) ||
      typeof context[methodName] !== 'function'
    ) {
      return null;
    }

    return {
      context: context,
      method: context[methodName]
    };
  }

  function asObject(value) {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      return {};
    }

    return value;
  }

  function mergeInitOptions(initOptions, runtimeConfig) {
    var baseOptions = asObject(initOptions);
    var baseRuntimeConfig = asObject(baseOptions.runtimeConfig);
    var mergedRuntimeConfig = Object.assign({}, baseRuntimeConfig, runtimeConfig);

    return Object.assign({}, baseOptions, mergedRuntimeConfig, {
      runtimeConfig: mergedRuntimeConfig
    });
  }
})(Drupal, drupalSettings);
