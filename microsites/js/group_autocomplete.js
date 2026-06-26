(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.micrositesGroupAutocomplete = {
    attach: function (context, settings) {
      const url = settings.microsites && settings.microsites.groupAutocompleteUrl;
      if (!url) return;

      once('microsites-group-autocomplete', 'input[data-autocomplete-path]', context)
        .forEach(function (input) {
          $(input).attr('data-autocomplete-path', url + '?q=');
          input.dataset.autocompletePath = url + '?q=';
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
