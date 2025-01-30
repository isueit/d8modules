/**
 * @file
 * javascript for the ISU Theme.
 */
  document.addEventListener('DOMContentLoaded', function() {
    var links = document.querySelectorAll('.flip-card_back a');
    links.forEach(function(link) {
      link.addEventListener('click', function(event) {
        event.stopPropagation();
      });
    });
  });