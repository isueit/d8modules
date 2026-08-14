# Feature with Large Image

Recreates the "feature-with-large-image" pattern from the iastate22-frontend
component library as a reusable block type: a large image, a title, rich
text, and one or more CTA buttons.

## Fields

- `field_flwi_image` - Media reference (image/SmugMug), required.
- `field_flwi_title` - Plain text title, required, rendered as an `<h2>`.
- `field_flwi_text` - Rich text (WYSIWYG).
- `field_flwi_links` - Unlimited link field for one or more CTA buttons.

## Background / accent styling

This module intentionally does **not** ship a color/background field on the
block type, and does not install any `layout_builder_styles.style` config.
Background and accent treatments (e.g. the red accent background, class
`feature-with-large-image--red-accent`, styled in
`iastate_theme/css/layout-builder.css`) are applied per-placement through
Layout Builder Styles, managed separately from this module.

## Rendering

Like `isueo_card_deck`, this module only owns the field schema and CSS. The
block markup itself is provided by the active theme via a
`block--feature-with-large-image.html.twig` template (see
`iastate_theme/templates/blocks/`).

## Installation

Install as you would normally install a Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Maintainers

- Iowa State University Extension and Outreach Web Development Team
