// Lines the help with making this an asset in hook.libraries.yml file
(function (Drupal, once) {
  // Build an InstantSearch search box inside the given container
  function mountSearchBar(container, options) {
    // Set up the search Adapter
    const typesenseInstantsearchAdapterBar =
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

    const searchClientBar = typesenseInstantsearchAdapterBar.searchClient;

    const searchBar = instantsearch({
      searchClient: searchClientBar,
      indexName: "porkgateway",
      routing: options.routing,
    });

    searchBar.addWidgets([
      instantsearch.widgets.searchBox({
        container: container,
        autofocus: options.autofocus,
        showReset: false,
        searchAsYouType: false,
        placeholder:
          "Search Peer-reviewed Resources",
        queryHook(query, search) {
          const newurl = "/results?porkgateway[query]=" + query;
          window.location.href = newurl;
          //search(query);
        },
      }),
    ]);

    searchBar.start();
  }

  // The theme builds the mobile menu (.isu-mobile-header-extras) from cloned
  // markup when the menu is opened, and removes it on desktop. A cloned search
  // box has no InstantSearch handlers, so mount a fresh one each time it's built.
  function watchMobileMenu() {
    const collapse = document.getElementById("isu-menu-navbar_collapse");
    if (!collapse) {
      return;
    }

    const addMobileSearchBar = () => {
      const extras = collapse.querySelector(".isu-mobile-header-extras");
      if (!extras || extras.querySelector(".porkgateway-search-bar--mobile")) {
        return;
      }
      const wrapper = document.createElement("div");
      wrapper.className = "porkgateway-mobile-search";
      const container = document.createElement("div");
      container.className = "porkgateway-search-bar--mobile";
      wrapper.appendChild(container);
      extras.prepend(wrapper);
      mountSearchBar(container, { routing: false, autofocus: false });
    };

    new MutationObserver(addMobileSearchBar).observe(collapse, {
      childList: true,
      subtree: true,
    });
    addMobileSearchBar();
  }

  Drupal.behaviors.porkgatewaySearchbar = {
    attach(context) {
      once("porkgateway-search", "#porkgateway-search-bar", context).forEach(
        (el) => {
          mountSearchBar(el, { routing: true, autofocus: true });

          // Only the header search bar gets a copy in the mobile menu
          if (el.closest(".isu-region_header-second")) {
            watchMobileMenu();
          }
        },
      );
    },
  };
})(Drupal, once);
