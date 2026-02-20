(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const downloadButton = document.getElementById('download-filtered-calendar');
    
    if (!downloadButton) {
      return;
    }

    downloadButton.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Get the current InstantSearch state from the URL
      const urlParams = new URLSearchParams(window.location.search);
      const downloadParams = new URLSearchParams();
      
      // Map InstantSearch URL parameters to our filter parameters
      // County
      const county = urlParams.get('events[refinementList][county][0]');
      if (county) {
        downloadParams.append('county', county);
      }
      
      // Program Unit
      const programUnit = urlParams.get('events[refinementList][PrimaryProgramUnit__c][0]');
      if (programUnit) {
        downloadParams.append('program_unit', programUnit);
      }
      
      // Delivery Method
      const deliveryMethod = urlParams.get('events[refinementList][delivery_method][0]');
      if (deliveryMethod) {
        downloadParams.append('delivery_method', deliveryMethod);
      }
      
      // Delivery Language
      const deliveryLanguage = urlParams.get('events[refinementList][Delivery_Language__c][0]');
      if (deliveryLanguage) {
        downloadParams.append('delivery_language', deliveryLanguage);
      }
      
      // Program ID
      const plpProgram = urlParams.get('events[refinementList][plp_program][0]');
      if (plpProgram) {
        downloadParams.append('plp_program', plpProgram);
      }
      
      // Search query
      const query = urlParams.get('events[query]');
      if (query) {
        downloadParams.append('query', query);
      }
      
      // Build the download URL
      let url = '/calendar/feed-filtered.ics';
      const paramString = downloadParams.toString();
      if (paramString) {
        url += '?' + paramString;
      } else {
        // If no filters, just download the full calendar
        url = '/calendar/feed.ics';
      }
      
      // Trigger download
      window.location.href = url;
    });
  });
})();