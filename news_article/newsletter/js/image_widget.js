(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.moduleImageWidget = {
    attach: function (context) {
      once('module-image-widget', '.module-image-picker', context).forEach(function (element) {
        const $widget = $(element);
        const $radioButtons = $widget.find('.form-radio');

        // Add selected class to initially checked option
        $radioButtons.filter(':checked').each(function () {
          $(this).closest('.form-type-radio').addClass('selected');
        });

        // Handle selection changes
        $radioButtons.on('change', function () {
          $widget.find('.form-type-radio').removeClass('selected');
          $(this).closest('.form-type-radio').addClass('selected');
        });

        // Make the entire wrapper clickable
        $widget.find('.form-type-radio').on('click', function (e) {
          // Only trigger if clicking on the wrapper, not the radio itself
          if (!$(e.target).is('input[type="radio"]')) {
            const $radio = $(this).find('.form-radio');
            $radio.prop('checked', true).trigger('change');
          }
        });

        // Add keyboard support
        $widget.find('.form-type-radio').attr('tabindex', '0');
        
        $widget.find('.form-type-radio').on('keydown', function (e) {
          // Space or Enter to select
          if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            $(this).find('.form-radio').prop('checked', true).trigger('change');
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);