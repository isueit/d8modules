(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.countyOfficeMap = {
    attach: function (context, settings) {
      
      const counties = drupalSettings.countyOfficeMap?.counties || {};
      const regions = drupalSettings.countyOfficeMap?.regions || {};
      
      // Make SVG counties clickable
      $('#iowa-map path, #iowa-map g[id], svg[id*="map"] path, svg[id*="map"] g[id]', context)
        .once('county-click')
        .each(function() {
          const $county = $(this);
          
          // Get county name from SVG id or data attribute
          // This works with various SVG structures
          const countyId = $county.attr('id') || 
                          $county.attr('data-county') || 
                          $county.parent().attr('id');
          
          if (!countyId) return;
          
          // Clean up the county name
          const countyName = cleanCountyName(countyId);
          
          // Find matching county data
          const countyData = Object.values(counties).find(c => 
            c.name.toLowerCase() === countyName.toLowerCase()
          );
          
          if (countyData) {
            $county.css('cursor', 'pointer');
            $county.attr('data-county-name', countyData.name);
            
            // Hover effect
            $county.hover(
              function() { 
                $(this).addClass('county-hover');
              },
              function() { 
                $(this).removeClass('county-hover');
              }
            );
            
            // Click handler
            $county.on('click', function() {
              loadCountyInfo(countyData);
              
              // Highlight selected county
              $('svg path, svg g[id]').removeClass('county-selected');
              $(this).addClass('county-selected');
            });
          }
        });
      
      // Quick access buttons
      $('.quick-county', context).once('quick-county').on('click', function() {
        const countyName = $(this).data('county');
        const countyData = Object.values(counties).find(c => 
          c.name === countyName
        );
        if (countyData) {
          loadCountyInfo(countyData);
          
          // Find and highlight the county on map
          const $mapCounty = $('svg [data-county-name="' + countyName + '"]');
          $('svg path, svg g[id]').removeClass('county-selected');
          $mapCounty.addClass('county-selected');
        }
      });
      
      // Search functionality with autocomplete
      let searchTimeout;
      $('#county-search', context).once('county-search').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
          $('#search-results').empty().hide();
          return;
        }
        
        searchTimeout = setTimeout(function() {
          showSearchResults(query, counties, regions);
        }, 300);
      });
      
      // Close search results when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-wrapper').length) {
          $('#search-results').hide();
        }
      });
      
      /**
       * Clean up county name from SVG id.
       */
      function cleanCountyName(id) {
        return id
          .replace(/county/gi, '')
          .replace(/[-_]/g, ' ')
          .trim()
          .split(' ')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
          .join(' ');
      }
      
      /**
       * Show search results dropdown.
       */
      function showSearchResults(query, counties, regions) {
        const results = [];
        
        // Search counties
        Object.values(counties).forEach(county => {
          if (county.name.toLowerCase().includes(query)) {
            results.push({
              type: 'county',
              title: county.name + ' County',
              subtitle: county.region,
              data: county
            });
          }
        });
        
        // Search regions
        Object.values(regions).forEach(region => {
          if (region.name.toLowerCase().includes(query)) {
            results.push({
              type: 'region',
              title: region.name,
              subtitle: 'Director: ' + region.director_name,
              data: region
            });
          }
        });
        
        // Search regional directors
        Object.values(regions).forEach(region => {
          if (region.director_name.toLowerCase().includes(query)) {
            results.push({
              type: 'director',
              title: region.director_name,
              subtitle: region.name,
              data: region
            });
          }
        });
        
        if (results.length === 0) {
          $('#search-results').html('<div class="no-results">No results found</div>').show();
          return;
        }
        
        let html = '<div class="results-list">';
        results.slice(0, 8).forEach(result => {
          html += `
            <div class="result-item" data-type="${result.type}" data-id="${result.data.tid}">
              <div class="result-title">${result.title}</div>
              <div class="result-subtitle">${result.subtitle}</div>
            </div>
          `;
        });
        html += '</div>';
        
        $('#search-results').html(html).show();
        
        // Handle result clicks
        $('.result-item').on('click', function() {
          const type = $(this).data('type');
          const id = $(this).data('id');
          
          if (type === 'county') {
            const county = Object.values(counties).find(c => c.tid == id);
            if (county) {
              loadCountyInfo(county);
              $('#county-search').val('');
              $('#search-results').hide();
            }
          } else if (type === 'region' || type === 'director') {
            // Load first county in that region
            const firstCounty = Object.values(counties).find(c => c.region_id == id);
            if (firstCounty) {
              loadCountyInfo(firstCounty);
              $('#county-search').val('');
              $('#search-results').hide();
            }
          }
        });
      }
      
      /**
       * Load county information into the side panel.
       */
      function loadCountyInfo(county) {
        let html = `
          <div class="panel-header">
            <div class="panel-title-area">
              <h2>${county.name} County</h2>
              ${county.region ? `<p class="region-label">Region ${county.region}</p>` : ''}
            </div>
            <button class="close-panel" aria-label="Close">×</button>
          </div>
          
          <div class="county-office-section">
            <h3>
              <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              County Office
            </h3>
            <div class="office-info">
              ${county.address ? `<p class="address">${county.address}</p>` : ''}
              ${county.phone ? `<p class="phone"><a href="tel:${county.phone}">${county.phone}</a></p>` : ''}
            </div>
            ${county.website ? `<a href="${county.website}" class="btn btn-primary" target="_blank" rel="noopener">Visit County Website →</a>` : ''}
          </div>
          
          <div class="staff-section">
            <h3>
              <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              County Staff
            </h3>
            <div id="staff-list-${county.tid}" class="loading">Loading staff...</div>
          </div>
          
          ${county.region_id ? `
            <div class="regional-director-section">
              <h3>
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="8.5" cy="7" r="4"></circle>
                  <line x1="20" y1="8" x2="20" y2="14"></line>
                  <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Regional Director
              </h3>
              <div id="regional-director-${county.region_id}" class="loading">Loading director info...</div>
            </div>
          ` : ''}
        `;
        
        $('#panel-content').html(html);
        
        // Close button handler
        $('.close-panel').on('click', function() {
          $('#panel-content').html(`
            <div class="panel-empty-state">
              <svg class="icon-location" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <h3>No County Selected</h3>
              <p>Search for a county above or click on the map to view office and staff information.</p>
            </div>
          `);
          $('svg path, svg g[id]').removeClass('county-selected');
        });
        
        // Load staff via AJAX (Views block)
        loadCountyStaff(county.tid);
        
        // Load regional director info
        if (county.region_id) {
          loadRegionalDirector(county.region_id);
        }
      }
      
      /**
       * Load county staff using Views.
       */
      function loadCountyStaff(countyTid) {
        // This assumes you've created a View with machine name 'county_staff'
        // and a block display 'block_1' with a contextual filter for county term ID
        
        $.ajax({
          url: Drupal.url('county-office-map/staff/' + countyTid),
          success: function(response) {
            $('#staff-list-' + countyTid).html(response);
          },
          error: function() {
            $('#staff-list-' + countyTid).html('<p>Unable to load staff information.</p>');
          }
        });
      }
      
      /**
       * Load regional director information.
       */
      function loadRegionalDirector(regionTid) {
        const region = regions[regionTid];
        
        if (!region) {
          $('#regional-director-' + regionTid).html('<p>No director information available.</p>');
          return;
        }
        
        let html = `
          <div class="director-info">
            <div class="director-avatar">
              ${region.director_name.split(' ').map(n => n[0]).join('')}
            </div>
            <div class="director-details">
              <p class="director-name">${region.director_name}</p>
              <p class="director-region">${region.name}</p>
              ${region.director_email ? `<p class="director-email"><a href="mailto:${region.director_email}">${region.director_email}</a></p>` : ''}
              ${region.director_phone ? `<p class="director-phone"><a href="tel:${region.director_phone}">${region.director_phone}</a></p>` : ''}
            </div>
          </div>
          <a href="#" class="btn btn-secondary">View Full Profile →</a>
        `;
        
        $('#regional-director-' + regionTid).html(html);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);