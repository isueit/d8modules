import { createDropdown } from "./Dropdown.js";

// Lines the help with making this an asset in hook.libraries.yml file
(function (Drupal, once) {
  Drupal.behaviors.porkgatewaySearchresults = {
    attach(context) {
      once("porkgateway-search-results", "#search-results-bar", context).forEach(
        (el) => {
          /* global algoliasearch instantsearch */

          // This is the start of the  searchresults code
          // Set up the search Adapter
          const typesenseInstantsearchAdapterResults =
            new TypesenseInstantSearchAdapter({
              server: {
                apiKey: "bilLvsiWoO1EqcM21L8XrzofmVBYfyB9", // Be sure to use an API key that only allows searches, in production
                nodes: [
                  {
                    host: "typesense.extension.iastate.edu",
                    port: "443",
                    protocol: "https",
                  },
                ],
              },
              // The following parameters are directly passed to Typesense's search API endpoint.
              //  So you can pass any parameters supported by the search endpoint below.
              //  queryBy is required.
              //  filterBy is managed and overridden by InstantSearch.js. To set it, you want to use one of the filter widgets like refinementList or use the `configure` widget.
              additionalSearchParameters: {
                queryBy:
                  "title,factsheet_number,url_path,audiences,resource_type,categories,body,summary,references,updated_date,authors,editors,reviews",
              },
            });

          const searchClientResults =
            typesenseInstantsearchAdapterResults.searchClient;
          const { infiniteHits } = instantsearch.widgets;

          const searchResults = instantsearch({
            searchClient: searchClientResults,
            indexName: "porkgateway",
            routing: true,
          });

          const MOBILE_WIDTH = 375;

          const audiencesDropdown = createDropdown(
            instantsearch.widgets.refinementList,
            {
              closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
              buttonText: "Audiences",
            },
          );


          const categoriesDropdown = createDropdown(
            instantsearch.widgets.refinementList,
            {
              closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
              buttonText: "Category",
            },
          );


          const resourceTypeDropdown = createDropdown(
            instantsearch.widgets.refinementList,
            {
              closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
              buttonText: "Resource Type",
            },
          );

          searchResults.addWidgets([
            instantsearch.widgets.searchBox({
              container: "#search-results-bar",
              autofocus: true,
              showReset: false,
              searchAsYouType: false,
              placeholder:
                "Search Peer-reviewed Resources",
              queryHook(query, search) {
                search(query);
              },
            }),

            instantsearch.widgets.configure({
              hitsPerPage: 120,
            }),
            instantsearch.widgets.infiniteHits({
              container: "#hits",
              templates: {
                item(item) {
                  const hl = item._highlightResult;
                  // Categories (highlighted when they match), separated by commas
                  const categoryNames = Array.isArray(hl.categories)
                    ? hl.categories.map((category) => category.value)
                    : item.categories || [];
                  const categories = categoryNames.length
                    ? `<div class="pg-hit__category">${categoryNames.join(", ")}</div>`
                    : "";
                  // Bottom bar styled like the Resource Details block header, showing the resource type
                  const barLabel = "Resource Type";
                  const barValue = hl.resource_type ? hl.resource_type.value : item.resource_type || "";
                  const bar = `<div class="pg-hit__bar">
                <span class="pg-hit__bar-icon" aria-hidden="true"></span>
                <div class="pg-hit__bar-text">
                  <span class="pg-hit__bar-label">${barLabel}</span>
                  <span class="pg-hit__bar-value">${barValue}</span>
                </div>
                <a class="pg-hit__view" href="${item.url_path}" tabindex="-1" aria-hidden="true">View <span class="pg-hit__view-arrow">&rarr;</span></a>
              </div>`;
                  return `
            <article class="pg-hit">
              <div class="pg-hit__body">
                ${categories}
                <h2 class="pg-hit__title"><a href="${item.url_path}">${hl.title.value}</a></h2>
                <div class="pg-hit__summary">${hl.summary.value}</div>
              </div>
              ${bar}
            </article>
          `;
                },
              },
              cssClasses: {
                loadMore: ["btn-outline-danger", "btn"],
              },
            }),

            audiencesDropdown({
              container: "#audiences",
              attribute: "audiences",
            }),
            categoriesDropdown({
              container: "#categories",
              attribute: "categories",
            }),
            resourceTypeDropdown({
              container: "#resource_type",
              attribute: "resource_type",
            }),

            instantsearch.widgets.stats({
              container: "#stats",
              templates: {
                text(data) {
                  return `
        <div class="search-stats-number">
        ${data.nbHits} result(s) found
        </div>
        `;
                },
              },
            }),
            instantsearch.widgets.currentRefinements({
              container: "#current-refinements",
              transformItems(items) {
                return items.map((item) => ({
                  ...item,
                  refinements: item.refinements.map((refinement) => ({
                    ...refinement,
                    label: refinement.label, // Keep the label intact for rendering later
                  })),
                }));
              },
            }),
          ]);
          // Point "Search all of Extension" at the current query
          const searchAllLink = document.querySelector("#isueo-searchall a");
          searchResults.on("render", function () {
            if (searchAllLink) {
              const query = searchResults.helper.state.query;
              searchAllLink.href = query
                ? "https://www.extension.iastate.edu/search-results?as_q=" + encodeURIComponent(query)
                : "https://www.extension.iastate.edu/search-results?search_broadness=wide";
            }

            const refinementsList = document.querySelector(
              "#current-refinements .ais-CurrentRefinements-list",
            );

            if (refinementsList) {
              const refinementItems = refinementsList.querySelectorAll(
                ".ais-CurrentRefinements-category",
              );

              refinementItems.forEach((item) => {
                const categoryLabel = item.querySelector(
                  ".ais-CurrentRefinements-categoryLabel",
                );
                const deleteButton = item.querySelector(
                  ".ais-CurrentRefinements-delete",
                );

                // Move the category label inside the delete button
                if (categoryLabel && deleteButton) {
                  deleteButton.innerHTML = categoryLabel.innerHTML; // Adding label inside the button
                  deleteButton.classList.add("btn", "btn-outline-primary");
                  categoryLabel.style.display = "none"; // Hide the categoryLabel span
                }
              });
            }
          });

          searchResults.start();
          // end of the searchresults code

        },
      );
    },
  };
})(Drupal, once);
