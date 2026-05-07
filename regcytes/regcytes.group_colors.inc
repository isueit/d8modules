<?php

/**
 * @file
 * Group color branding helpers for regcytes module.
 *
 * Color fields expected on the group type (field machine names):
 *   field_subsite_design_type         (list, isu_theme|custom_theme)
 *   field_subsite_color_primary       (color / string, default #c8102e)
 *   field_subsite_color_secondary     (color / string, default #7c2529)
 *   field_subsite_color_accent        (color / string, default #f1be48)
 *   field_subsite_color_link          (color / string, default #7c2529)
 *   field_subsite_color_btn_success   (color / string, default #008540)
 *   field_subsite_color_btn_danger    (color / string, default #c8102e)
 *   field_subsite_color_btn_primary   (color / string, default #003d4c)
 *   field_subsite_color_btn_second    (color / string, default #7c2529)
 *
 * Each color field maps to a CSS custom property on :root when
 * field_subsite_design_type is 'custom_theme' for the active group.
 */

/**
 * Default color values matching the ISU theme variables.
 */
function regcytes_color_defaults(): array {
  return [
    'field_subsite_color_primary'      => '#c8102e',
    'field_subsite_color_secondary'    => '#7c2529',
    'field_subsite_color_accent'       => '#f1be48',
    'field_subsite_color_link'         => '#7c2529',
    'field_subsite_color_btn_success'  => '#008540',
    'field_subsite_color_btn_danger'   => '#c8102e',
    'field_subsite_color_btn_primary'  => '#003d4c',
    'field_subsite_color_btn_second'   => '#7c2529',
  ];
}

/**
 * Maps CSS-map keys to the actual entity field machine names where they differ.
 *
 * Three fields use an older naming convention on the entity:
 *   field_subsite_*_color  (entity)  vs  field_subsite_color_*  (CSS map key)
 */
function regcytes_entity_field_names(): array {
  return [
    'field_subsite_color_primary'   => 'field_subsite_primary_color',
    'field_subsite_color_secondary' => 'field_subsite_secondary_color',
    'field_subsite_color_link'      => 'field_subsite_link_color',
  ];
}

/**
 * Maps field machine names to CSS custom property names.
 */
function regcytes_color_css_map(): array {
  return [
    'field_subsite_color_primary'     => '--color-primary',
    'field_subsite_color_secondary'   => '--color-secondary',
    'field_subsite_color_accent'      => '--color-accent',
    'field_subsite_color_link'        => '--color-link',
    'field_subsite_color_btn_success' => '--color-btn-success',
    'field_subsite_color_btn_danger'  => '--color-btn-danger',
    'field_subsite_color_btn_primary' => '--color-btn-primary',
    'field_subsite_color_btn_second'  => '--color-btn-secondary',
  ];
}

/**
 * Returns the active group entity for the current request, or NULL.
 *
 * Resolves the group via the regcytes URL path-prefix context provider.
 */
function regcytes_get_active_group(): ?\Drupal\group\Entity\GroupInterface {
  /** @var \Drupal\Core\Plugin\Context\ContextProviderInterface $provider */
  $provider = \Drupal::service('regcytes.path_prefix_context');
  $contexts = $provider->getRuntimeContexts(['group']);
  if (empty($contexts['group']) || !$contexts['group']->hasContextValue()) {
    return NULL;
  }
  $group = $contexts['group']->getContextValue();
  return $group instanceof \Drupal\group\Entity\GroupInterface ? $group : NULL;
}

/**
 * #after_build callback — runs after Field Group has added its containers.
 *
 * Applies #states to group_custom_theme_options so color fields only show
 * when "Custom Theme" is selected in field_subsite_design_type.
 */
function regcytes_group_color_after_build(array $form, \Drupal\Core\Form\FormStateInterface $form_state): array {
  $condition = [
    ':input[name="field_subsite_design_type"]' => ['value' => 'custom_theme'],
  ];
  _regcytes_apply_group_states($form, $condition);

  // Ensure color inputs always have a valid #rrggbb default so the HTML5
  // color input doesn't fail browser validation on empty fields.
  $entity_names = regcytes_entity_field_names();
  foreach (regcytes_color_defaults() as $field_name => $default_color) {
    $actual = $entity_names[$field_name] ?? $field_name;
    _regcytes_set_color_default($form, $actual, $default_color);
  }

  $button_fields = [
    'field_subsite_color_btn_success',
    'field_subsite_color_btn_danger',
    'field_subsite_color_btn_primary',
    'field_subsite_color_btn_second',
  ];

  // Backgrounds to check for each non-button field.
  // Link and primary are used as foreground text/hover colors on page backgrounds.
  $link_backgrounds = [
    ['hex' => '#ffffff', 'label' => 'Link text on a white page background'],
    ['hex' => '#f5f5f5', 'label' => 'Link text on a light gray section background'],
  ];
  $field_backgrounds = [
    'field_subsite_color_link'    => $link_backgrounds,
    'field_subsite_color_primary' => [
      ['hex' => '#ffffff', 'label' => 'Used as link hover + agenda date/time text on a white background'],
      ['hex' => '#f5f5f5', 'label' => 'Used as link hover text on a light gray section background'],
    ],
  ];
  $default_backgrounds = [['hex' => '#ffffff', 'label' => 'This color as text on a white background']];

  $defaults = regcytes_color_defaults();
  $fields_config = [];

  $primary_actual = $entity_names['field_subsite_color_primary'] ?? 'field_subsite_color_primary';
  $primary_selector = 'input[name="' . $primary_actual . '[0][color]"], input[name="' . $primary_actual . '[0][value]"]';

  foreach (regcytes_color_css_map() as $field_name => $css_var) {
    $actual = $entity_names[$field_name] ?? $field_name;
    $entry = [
      'fieldName'     => $actual,
      'inputSelector' => 'input[name="' . $actual . '[0][color]"], input[name="' . $actual . '[0][value]"]',
      'default'       => $defaults[$field_name],
      'isButton'      => in_array($field_name, $button_fields),
      'backgrounds'   => $field_backgrounds[$field_name] ?? $default_backgrounds,
    ];
    if ($field_name === 'field_subsite_color_accent') {
      $entry['dynamicBg'] = [
        'selector' => $primary_selector,
        'label'    => 'As hero date text on the primary color background',
        'default'  => $defaults['field_subsite_color_primary'],
      ];
    }
    $fields_config[] = $entry;
  }

  $form['#attached']['drupalSettings']['regcytesGroupColors'] = [
    'fields' => $fields_config,
  ];
  $form['#attached']['library'][] = 'regcytes/group_color_picker';

  return $form;
}

/**
 * Finds a color field anywhere in the form tree and sets its default value
 * if the current value is empty, so HTML5 color inputs always have #rrggbb.
 */
function _regcytes_set_color_default(array &$element, string $field_name, string $default): void {
  if (array_key_exists($field_name, $element)) {
    $widget = &$element[$field_name];
    // color_field widgets use [color]; plain string fields use [value].
    // In #after_build, #default_value is already resolved — set #value instead.
    foreach (['color', 'value'] as $sub_key) {
      if (isset($widget['widget'][0][$sub_key]) &&
          empty($widget['widget'][0][$sub_key]['#value'])) {
        $widget['widget'][0][$sub_key]['#value'] = $default;
        break;
      }
    }
    return;
  }
  foreach (array_keys($element) as $key) {
    if ($key[0] === '#' || !is_array($element[$key])) {
      continue;
    }
    _regcytes_set_color_default($element[$key], $field_name, $default);
  }
}

/**
 * Recursively finds group_custom_theme_options and applies #states to it.
 */
function _regcytes_apply_group_states(array &$element, array $condition): bool {
  if (array_key_exists('group_custom_theme_options', $element)) {
    $element['group_custom_theme_options']['#states']['visible'] = $condition;
    return TRUE;
  }
  foreach (array_keys($element) as $key) {
    if ($key[0] === '#' || !is_array($element[$key])) {
      continue;
    }
    if (_regcytes_apply_group_states($element[$key], $condition)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Builds the CSS custom property block for a group's color overrides.
 *
 * Returns an empty string if field_subsite_color_enabled is not set or FALSE.
 */
function regcytes_group_color_css(\Drupal\group\Entity\GroupInterface $group): string {
  // Only inject colors when the group has chosen a custom theme.
  if (!$group->hasField('field_subsite_design_type') ||
      $group->get('field_subsite_design_type')->value !== 'custom_theme') {
    return '';
  }

  $defaults     = regcytes_color_defaults();
  $map          = regcytes_color_css_map();
  $entity_names = regcytes_entity_field_names();
  $resolved     = [];
  $props        = [];

  foreach ($map as $field => $css_var) {
    $entity_field = $entity_names[$field] ?? $field;
    if ($group->hasField($entity_field)) {
      $item  = $group->get($entity_field);
      // color_field stores the hex in ->color; plain string fields use ->value.
      $value = trim($item->color ?? $item->value ?? '');
    }
    else {
      $value = '';
    }

    $color = ($value !== '') ? $value : $defaults[$field];
    $resolved[$field] = $color;
    $props[] = "  {$css_var}: {$color};";

    if (str_starts_with($field, 'field_subsite_color_btn_')) {
      // Filled button: white or black text on button-color background.
      $text_var = str_replace('--color-btn-', '--color-btn-text-', $css_var);
      $props[]  = "  {$text_var}: " . regcytes_best_text_color($color) . ";";

      // Outline button: button color as text on white. Fall back to black
      // if the button color does not meet AA contrast against white (4.5:1).
      $outline_var  = str_replace('--color-btn-', '--color-btn-outline-text-', $css_var);
      $outline_text = regcytes_contrast_ratio($color, '#ffffff') >= 4.5 ? $color : '#000000';
      $props[]      = "  {$outline_var}: {$outline_text};";
    }
  }

  // ── Hero banner text colors ───────────────────────────────────────────────
  // The hero label container uses primary color as its background.
  // --color-hero-text : white or black, whichever passes contrast on primary.
  // --color-hero-date : secondary if it passes AA (≥4.5:1) on primary;
  //                     else accent if it passes; else fall back to hero-text.
  $primary   = $resolved['field_subsite_color_primary'];
  $secondary = $resolved['field_subsite_color_secondary'];
  $accent    = $resolved['field_subsite_color_accent'];

  $hero_text = regcytes_best_text_color($primary);
  $props[]   = "  --color-hero-text: {$hero_text};";

  if (regcytes_contrast_ratio($secondary, $primary) >= 4.5) {
    $hero_date = $secondary;
  }
  elseif (regcytes_contrast_ratio($accent, $primary) >= 4.5) {
    $hero_date = $accent;
  }
  else {
    $hero_date = $hero_text;
  }
  $props[] = "  --color-hero-date: {$hero_date};";

  return ":root {\n" . implode("\n", $props) . "\n}";
}

/**
 * Returns the WCAG relative luminance (0–1) for a hex color.
 */
function regcytes_luminance(string $hex): float {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) {
    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
  }
  $r = hexdec(substr($hex, 0, 2)) / 255;
  $g = hexdec(substr($hex, 2, 2)) / 255;
  $b = hexdec(substr($hex, 4, 2)) / 255;
  $lin = fn(float $c): float =>
    $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
  return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
}

/**
 * Returns the WCAG contrast ratio between two hex colors.
 */
function regcytes_contrast_ratio(string $hexA, string $hexB): float {
  $lA = regcytes_luminance($hexA);
  $lB = regcytes_luminance($hexB);
  $lighter = max($lA, $lB);
  $darker  = min($lA, $lB);
  return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Returns '#ffffff' or '#000000' — whichever gives better contrast against
 * the supplied background hex, using the WCAG relative luminance formula.
 */
function regcytes_best_text_color(string $hex): string {
  $luminance = regcytes_luminance($hex);

  // Contrast ratio against white (L=1) vs black (L=0).
  $contrast_white = (1.05) / ($luminance + 0.05);
  $contrast_black = ($luminance + 0.05) / (0.05);

  return $contrast_white >= $contrast_black ? '#ffffff' : '#000000';
}
