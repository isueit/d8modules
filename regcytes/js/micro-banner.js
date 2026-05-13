/**
 * @file
 * Inserts the page title below the micro banner when one is present.
 *
 * The node title is passed via drupalSettings because exclude_node_title
 * suppresses the H1 from the DOM entirely. If no title is set (e.g. the
 * node title was intentionally cleared), nothing is rendered.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.regcytesMicroBannerTitle = {
    attach(context) {
      const banners = once(
        'micro-banner-title',
        '.microsite-micro-banner, .microsite-micro-banner-details',
        context
      );
      if (!banners.length) return;

      const title = drupalSettings.regcytes && drupalSettings.regcytes.nodeTitle;
      if (!title) return;

      // Safely escape the title then build the strip.
      const escapedTitle = $('<span>').text(title).html();
      const $strip = $(
        '<div class="micro-banner-page-title">' +
          '<div class="container">' +
            '<h1 class="isu-page-title">' + escapedTitle + '</h1>' +
          '</div>' +
        '</div>'
      );

      $(banners[0]).after($strip);
    }
  };

})(jQuery, Drupal, drupalSettings, once);
