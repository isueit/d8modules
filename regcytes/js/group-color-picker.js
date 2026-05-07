/**
 * @file
 * Inline accessibility checker for group color fields.
 *
 * Non-button fields: contrast ratio against white (#ffffff) — the common
 *   use case (text on a white page, or white text on a colored background).
 * Button fields: best text color (white or black) calculated from WCAG
 *   relative luminance, with pass/fail ratio displayed.
 *
 * All calculations are local — no external API calls.
 */

(function (Drupal, drupalSettings, once, $) {
  'use strict';

  // ─── WCAG utilities ────────────────────────────────────────────────────────

  function relativeLuminance(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) {
      hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    }
    const r = parseInt(hex.slice(0, 2), 16) / 255;
    const g = parseInt(hex.slice(2, 4), 16) / 255;
    const b = parseInt(hex.slice(4, 6), 16) / 255;
    const lin = c => c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
  }

  function contrastRatio(hexA, hexB) {
    const lA = relativeLuminance(hexA);
    const lB = relativeLuminance(hexB);
    const lighter = Math.max(lA, lB);
    const darker  = Math.min(lA, lB);
    return (lighter + 0.05) / (darker + 0.05);
  }

  function bestTextColor(hex) {
    const L  = relativeLuminance(hex);
    const cW = 1.05 / (L + 0.05);
    const cB = (L + 0.05) / 0.05;
    return cW >= cB ? '#ffffff' : '#000000';
  }

  function normaliseHex(value) {
    if (!value) return null;
    const v = String(value).trim().replace(/^#/, '');
    if (/^[0-9a-fA-F]{3}$/.test(v)) return '#' + v[0]+v[0]+v[1]+v[1]+v[2]+v[2];
    if (/^[0-9a-fA-F]{6}$/.test(v)) return '#' + v;
    return null;
  }

  // ─── Styles ────────────────────────────────────────────────────────────────

  const STYLES = `
    .rc-contrast-panel {
      margin-top: 8px;
      font-size: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 8px 12px;
      background: #fafafa;
    }
    .rc-contrast-panel h4 {
      margin: 0 0 6px;
      font-size: 0.75rem;
      font-weight: 700;
      color: #555;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    .rc-pair-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 2px;
    }
    .rc-pair-label { flex: 1; color: #444; }
    .rc-ratio { font-weight: 700; min-width: 56px; text-align: right; font-variant-numeric: tabular-nums; }
    .rc-level { font-size: 0.7rem; color: #888; }
    .rc-hint  { margin: 0 0 6px; font-size: 0.72rem; color: #888; font-style: italic; }
    .rc-badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 3px;
      font-size: 0.72rem;
      font-weight: 700;
    }
    .rc-pass { background: #d4edda; color: #155724; }
    .rc-fail { background: #f8d7da; color: #721c24; }
    .rc-loading { color: #888; font-style: italic; margin: 0; }
    .rc-swatch {
      display: inline-block;
      width: 14px;
      height: 14px;
      border-radius: 2px;
      border: 1px solid rgba(0,0,0,0.18);
      flex-shrink: 0;
    }
  `;

  let stylesInjected = false;
  function injectStyles() {
    if (stylesInjected) return;
    const el = document.createElement('style');
    el.textContent = STYLES;
    document.head.appendChild(el);
    stylesInjected = true;
  }

  // ─── Panel rendering ───────────────────────────────────────────────────────

  function badge(pass) {
    return `<span class="rc-badge ${pass ? 'rc-pass' : 'rc-fail'}">${pass ? 'PASS' : 'FAIL'}</span>`;
  }

  function row(swatchA, swatchB, label, ratio) {
    return `
      <div class="rc-pair-row">
        <span class="rc-swatch" style="background:${swatchA}"></span>
        <span class="rc-swatch" style="background:${swatchB}"></span>
        <span class="rc-pair-label">${label}</span>
        <span class="rc-ratio">${ratio.toFixed(2)}:1</span>
        ${badge(ratio >= 4.5)} <span class="rc-level">Standard</span>
        ${badge(ratio >= 7)}   <span class="rc-level">Enhanced</span>
      </div>`;
  }

  function renderPanel(cfg, hex, dynamicBgHex) {
    let html = '<h4>Readability check</h4><p class="rc-hint">Standard = meets minimum web accessibility requirement &nbsp;&middot;&nbsp; Enhanced = exceeds it</p>';

    if (cfg.isButton) {
      // Filled button — white or black text on button background.
      const textColor   = bestTextColor(hex);
      const filledRatio = contrastRatio(hex, textColor);
      const textLabel   = textColor === '#ffffff' ? 'white' : 'black';
      html += row(hex, textColor, `Filled button &mdash; <strong>${textLabel} text</strong> will be used on this background color`, filledRatio);

      // Outline button — button color as text on white background.
      const outlineRatio = contrastRatio(hex, '#ffffff');
      if (outlineRatio >= 4.5) {
        html += row(hex, '#ffffff', 'Outline button &mdash; this color used as text on a white background', outlineRatio);
      } else {
        html += row(hex, '#ffffff', 'Outline button &mdash; this color as text on white is too low contrast', outlineRatio);
        html += row('#000000', '#ffffff', 'Outline button &mdash; <strong>black text</strong> will be used as a fallback', contrastRatio('#000000', '#ffffff'));
      }
    } else {
      (cfg.backgrounds || [{ hex: '#ffffff', label: 'On white' }]).forEach(bg => {
        html += row(hex, bg.hex, bg.label, contrastRatio(hex, bg.hex));
      });
      if (cfg.dynamicBg && dynamicBgHex) {
        html += row(hex, dynamicBgHex, cfg.dynamicBg.label, contrastRatio(hex, dynamicBgHex));
      }
    }

    return html;
  }

  // ─── Behaviour ─────────────────────────────────────────────────────────────

  Drupal.behaviors.regcytesGroupColorPicker = {
    attach(context) {
      const settings = drupalSettings.regcytesGroupColors;
      if (!settings) return;

      once('regcytes-color-picker', 'form', context).forEach(form => {
        injectStyles();

        settings.fields.forEach(cfg => {
          const input = form.querySelector(cfg.inputSelector);
          if (!input) return;

          // Prefer the outer field wrapper (has a reliable data-drupal-selector),
          // fall back to .js-form-wrapper or the immediate .form-item.
          const outerKey = 'edit-' + cfg.fieldName.replace(/_/g, '-') + '-wrapper';
          const wrapper  = form.querySelector('[data-drupal-selector="' + outerKey + '"]')
            || input.closest('.js-form-wrapper')
            || input.closest('.form-item')
            || input.parentElement;

          const panel = document.createElement('div');
          panel.className = 'rc-contrast-panel';
          panel.id = 'rc-panel-' + cfg.fieldName;
          panel.innerHTML = '<h4>Accessibility</h4><p class="rc-loading">Pick a color to see contrast info.</p>';
          if (wrapper) wrapper.appendChild(panel);

          function refresh() {
            const hex = normaliseHex(input.value) || normaliseHex(cfg.default);
            if (!hex) return;
            let dynamicBgHex = null;
            if (cfg.dynamicBg) {
              const bgInput = form.querySelector(cfg.dynamicBg.selector);
              dynamicBgHex = normaliseHex(bgInput ? bgInput.value : null)
                          || normaliseHex(cfg.dynamicBg.default);
            }
            panel.innerHTML = renderPanel(cfg, hex, dynamicBgHex);
          }

          // Spectrum fires change.spectrum / move.spectrum on the hidden input.
          // Also listen for native change/input for non-Spectrum widgets.
          $(input).on('change.spectrum move.spectrum change input', refresh);

          // Re-run this field's check whenever its dynamic background source changes.
          if (cfg.dynamicBg) {
            const bgInput = form.querySelector(cfg.dynamicBg.selector);
            if (bgInput) $(bgInput).on('change.spectrum move.spectrum change input', refresh);
          }

          if (input.value) refresh();
        });
      });
    },
  };

}(Drupal, drupalSettings, once, jQuery));
