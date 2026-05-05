(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.regcytesGroupAutocomplete = {
    attach: function (context, settings) {
      const url = settings.regcytes.groupAutocompleteUrl;
      if (!url) return;

      // Find the link field URI input and wait for autocomplete to init.
      once('regcytes-group-autocomplete', 'input[data-autocomplete-path]', context)
        .forEach(function (input) {
          // Replace whatever autocomplete URL was set by the widget.
          $(input).attr('data-autocomplete-path', url + '?q=');
          input.dataset.autocompletePath = url + '?q=';
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
