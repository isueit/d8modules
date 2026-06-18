(function ($, Drupal) {
  Drupal.behaviors.dateDropdownLimitYears = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var currentYear = new Date().getFullYear();

        // Select all year dropdowns dynamically.
        $('select[name$="[value][year]"]', context).each(function () {
          var yearDropdown = $(this);

          if (yearDropdown.length > 0) {
            yearDropdown.find('option').each(function () {
              var optionValue = parseInt($(this).val(), 10);

              // Remove options for years before the current year.
              if (!isNaN(optionValue) && optionValue < currentYear) {
                $(this).remove();
              }
            });
          }
        });
      });
    }
  };
})(jQuery, Drupal);

