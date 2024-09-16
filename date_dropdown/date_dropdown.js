(function ($, Drupal) {
    Drupal.behaviors.dateDropdownLimitYears = {
      attach: function (context, settings) {
        // Get the current year.
        var currentYear = new Date().getFullYear();

        // Target the year dropdown in the form.
        // Replace with the correct ID or class if necessary.
        var yearDropdown = $('select[name="field_application_deadline_date[0][value][year]"]', context);

        // If the year dropdown exists, modify the options.
        if (yearDropdown.length > 0) {
          yearDropdown.find('option').each(function () {
            var optionValue = parseInt($(this).val());

            // Remove options for years that are before the current year.
            if (optionValue && optionValue < currentYear) {
              $(this).remove();
            }
          });
        }
      }
    };
  })(jQuery, Drupal);
