(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.countyOfficeMap = {
    attach: function (context, settings) {
      
      const counties = drupalSettings.countyOfficeMap?.counties || {};
      const $svg = $('.map-container svg').first();
      
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
              <div class="county-meta">
                ${county.region ? `<span class="region-label">Region ${county.region}</span>` : ''}
                ${county.region && county.model ? `<span class="meta-separator">|</span>` : ''}
                ${county.model ? `<span class="model-label">Model ${county.model}</span>` : ''}
              </div>
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
              const profileUrl = `https://www.extension.iastate.edu/${svgId}/staff/${director.netid}`;
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
        const baseUrl = `https://www.extension.iastate.edu/${svgId}`;
        
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
        
        // Clear all map selections and highlights
        $svg.find('g[id]').removeClass('county-selected region-highlight');
        $svg.find('g[id] polygon, g[id] path').removeClass('county-selected');
        $svg.removeClass('region-active');
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
            
            // Read region and model from SVG data attributes if available
            const regionFromSVG = $(this).attr('data-region');
            const modelFromSVG = $(this).attr('data-model');
            
            // Create enhanced county data with SVG attributes
            const enhancedCountyData = { ...countyData };
            if (regionFromSVG) {
              enhancedCountyData.region = regionFromSVG;
            }
            if (modelFromSVG) {
              enhancedCountyData.model = modelFromSVG;
            }
            
            // Remove all previous highlights
            $svg.find('g[id]').removeClass('county-selected region-highlight');
            $svg.find('g[id] polygon, g[id] path').removeClass('county-selected');
            $svg.removeClass('region-active');

            loadCountyInfo(enhancedCountyData, svgId);

            // Highlight selected county in red
            $(this).addClass('county-selected');
            $(this).find('polygon, path').addClass('county-selected');

            // Highlight all counties in the same region with overlay
            if (enhancedCountyData.region) {
              $svg.addClass('region-active');
              $svg.find('g[id]').each(function() {
                const countyId = $(this).attr('id');
                const otherRegion = $(this).attr('data-region') || counties[countyId]?.region;

                if (otherRegion === enhancedCountyData.region) {
                  $(this).addClass('region-highlight');
                }
              });
            }

            // Move region-highlight counties to the end, then selected on top
            $svg.find('g[id].region-highlight').each(function() {
              $svg.append($(this));
            });
            const $selected = $svg.find('g[id].county-selected');
            if ($selected.length) {
              $svg.append($selected);
            }
            // Keep region number labels on top of all county layers
            const $regions = $svg.find('g#regions');
            if ($regions.length) {
              $svg.append($regions);
            }
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
            // Read region and model from SVG data attributes
            const $mapCounty = $svg.find('g#' + svgId);
            const regionFromSVG = $mapCounty.attr('data-region');
            const modelFromSVG = $mapCounty.attr('data-model');
            
            // Create enhanced county data with SVG attributes
            const enhancedCountyData = { ...countyData };
            if (regionFromSVG) {
              enhancedCountyData.region = regionFromSVG;
            }
            if (modelFromSVG) {
              enhancedCountyData.model = modelFromSVG;
            }
            
            // Remove all previous highlights
            $svg.find('g[id]').removeClass('county-selected region-highlight');
            $svg.find('g[id] polygon, g[id] path').removeClass('county-selected');
            $svg.removeClass('region-active');

            // Load panel with enhanced data
            loadCountyInfo(enhancedCountyData, svgId);

            // Highlight selected county
            $mapCounty.addClass('county-selected');
            $mapCounty.find('polygon, path').addClass('county-selected');

            // Highlight all counties in the same region
            if (enhancedCountyData.region) {
              $svg.addClass('region-active');
              $svg.find('g[id]').each(function() {
                const countyId = $(this).attr('id');
                const otherRegion = $(this).attr('data-region') || counties[countyId]?.region;

                if (otherRegion === enhancedCountyData.region) {
                  $(this).addClass('region-highlight');
                }
              });
            }

            // Move all region-highlight counties to the end, then selected on top
            $svg.find('g[id].region-highlight').each(function() {
              $svg.append($(this));
            });
            const $selectedCounty = $svg.find('g[id].county-selected');
            if ($selectedCounty.length) {
              $svg.append($selectedCounty);
            }
            // Keep region number labels on top of all county layers
            const $regionsGroup = $svg.find('g#regions');
            if ($regionsGroup.length) {
              $svg.append($regionsGroup);
            }
          }
        });
      });
 
    }
  };
 
})(jQuery, Drupal, drupalSettings, once);