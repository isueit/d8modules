(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.countyOfficeMap = {
    attach: function (context, settings) {
      
      const counties = drupalSettings.countyOfficeMap?.counties || {};
      
      // Store the original empty state HTML on first load
      const panelContent = once('store-empty-state', '#panel-content', context);
      if (panelContent.length > 0) {
        const $panel = $(panelContent[0]);
        const emptyStateHtml = $panel.html();
        // Store on the element itself
        $panel.attr('data-empty-state', 'stored');
        // Store in a variable accessible to the behavior
        if (!window.countyMapEmptyState) {
          window.countyMapEmptyState = emptyStateHtml;
        }
      }


      // ============================================
      // HELPER FUNCTIONS - Define these first
      // ============================================
      
      /**
       * Load county information into the side panel.
       */
      function loadCountyInfo(county, svgId) {

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
              <i class="fa-regular fa-building"></i>
              County Office
            </h3>
            <div class="office-info">
        `;
        
        // Add address and phone together
        if (county.address || county.phone) {
          html += `<p class="address">`;
          
          if (county.address) {
            html += `${county.address.replace(/\n/g, '<br>')}`;
          }
          
          if (county.phone) {
            html += `<br>${county.phone}`;
          }
          
          html += `</p>`;
        }
        
        html += `</div>`;
        
        // Add website link
        if (county.website) {
          html += `<a href="${county.website}" class="county-website-link">Visit County Website</a>`;
        }
        
        html += `</div>`;
        
        // Add Regional Director section
          html += `
            <div class="regional-director-section">
              <h3>
                <i class="fa-solid fa-user-group"></i>
                Regional Director
              </h3>
              <div class="director-info">`;

          if (county.regional_director) {
          const director = county.regional_director;
          
          if (director.image_url) {
            html += `
              <div class="director-photo">
                <img src="${director.image_url}" alt="${director.name}" class="staff_profile_smugmug">
              </div>`;
          }
          
          html += `<div class="director-details">`;
          
          if (director.name) {
            // Build staff profile URL if we have netid
            if (director.netid) {
              const countySlug = svgId; 
              const profileUrl = `https://www.extension.iastate.edu/${countySlug}/staff/${director.netid}`;
              html += `<p class="h3 director-name"><a href="${profileUrl}">${director.name}</a></p>`;
            } else {
              html += `<p class="h3 director-name">${director.name}</p>`;
            }
          }
          
          if (director.email) {
            html += `<p class="director-email"><i class="fa-regular fa-envelope"></i> <a href="mailto:${director.email}">${director.email}</a></p>`;
          }
          
          if (director.phone) {
            html += `<p class="director-phone"><i class="fa-solid fa-phone"></i> ${director.phone}</p>`;
          }
          
          html += `</div>`;
        } else {
          // No regional director assigned
          html += `
            <div class="director-details">
              <p class="h3 director-name director-open-position">Open Position</p>
            </div>`;
        }
        
        html += `</div></div>`;
        
       // Add Quick Links section
        const countySlug = svgId; 
        const baseUrl = `https://www.extension.iastate.edu/${countySlug}`;
        
        html += `
          <div class="quick-links-section">
            <h3>Quick Links</h3>
            <ul class="quick-links-list">
              <li><a href="${baseUrl}/staff">Meet the ${county.name} County Staff</a></li>
              <li><a href="${baseUrl}/4h">4-H Information</a></li>
              <li><a href="${baseUrl}/events">Upcoming Events</a></li>
              <li><a href="${baseUrl}/impact-report">Impact Report</a></li>
              <li><a href="${baseUrl}/extension-council">Extension Council</a></li>
            </ul>
          </div>`;
        
        $('#panel-content').html(html);
        
        // Bind close button
        $('.close-panel').on('click', function() {
          resetPanel();
        });
      }
      
         /**
       * Reset panel to empty state.
       */
      function resetPanel() {
        // Restore the original empty state
        if (window.countyMapEmptyState) {
          $('#panel-content').html(window.countyMapEmptyState);
        }
        
        // Clear map selection
        $('svg g[id]').removeClass('county-selected');
        $('svg g[id] polygon, svg g[id] path').removeClass('county-selected');
      }
      
      // ============================================
      // EVENT BINDINGS - Use the functions defined above
      // ============================================
      
      // Make SVG counties clickable
      const countyGroups = once('county-click', 'svg g[id]', context);
      countyGroups.forEach(function(element) {
        const $countyGroup = $(element);
        const svgId = $countyGroup.attr('id');
        
        // Skip the "regions" group
        if (svgId === 'regions') {
          return;
        }
        
        // Check if we have data for this county
        const countyData = counties[svgId];
        
        if (countyData) {
          // Make the entire group clickable
          $countyGroup.css('cursor', 'pointer');
          $countyGroup.attr('data-county-svg-id', svgId);
          $countyGroup.attr('data-county-name', countyData.name);

          // Add ARIA attributes for accessibility
          $countyGroup.attr('role', 'button');
          $countyGroup.attr('tabindex', '0');
          $countyGroup.attr('aria-label', `Select ${countyData.name} County`);
          
          // Hover effect - target the polygon/path inside the group
          $countyGroup.find('polygon, path').hover(
            function() { 
              $(this).addClass('county-hover');
            },
            function() { 
              $(this).removeClass('county-hover');
            }
          );
          
          // Click handler on the entire group
          $countyGroup.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            loadCountyInfo(countyData, svgId);
            
            // Highlight selected county
            $('svg g[id]').removeClass('county-selected');
            $('svg g[id] polygon, svg g[id] path').removeClass('county-selected');
            $(this).addClass('county-selected');
            $(this).find('polygon, path').addClass('county-selected');
          });

          // Keyboard support for accessibility
          $countyGroup.on('keydown', function(e) {
            // Enter or Space key
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              $(this).trigger('click');
            }
          });
        }
      });
      
       // Quick county buttons - bind to buttons with data-county-id attribute
      // These are in the Twig template's empty state
      const quickButtons = once('quick-county', '.quick-county-btn[data-county-id]', context);
      quickButtons.forEach(function(button) {
        $(button).on('click', function(e) {
          e.preventDefault();
          const svgId = $(this).data('county-id');
          const countyData = counties[svgId];
          
          if (countyData) {
            loadCountyInfo(countyData, svgId);
            
            // Find and highlight the county on map
            const $mapCounty = $('svg g#' + svgId);
            $('svg g[id]').removeClass('county-selected');
            $('svg g[id] polygon, svg g[id] path').removeClass('county-selected');
            $mapCounty.addClass('county-selected');
            $mapCounty.find('polygon, path').addClass('county-selected');
          }
        });
      });
 
    }
  };

})(jQuery, Drupal, drupalSettings, once);