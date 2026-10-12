(function (Drupal, once) {
  Drupal.behaviors.porkgatewaySearchbar = {
    attach(context) {
      once('porkgateway-search', '#search-results-bar', context).forEach((el) => {
/* global algoliasearch instantsearch */

import { createDropdown } from "./Dropdown.js";

const typesenseInstantsearchAdapterResults = new TypesenseInstantSearchAdapter({
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

const searchClientResults = typesenseInstantsearchAdapterResults.searchClient;
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
  }
);

const authorsDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Authors",
});

const categoriesDropdown = createDropdown(
  instantsearch.widgets.refinementList,
  {
    closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
    buttonText: "Category",
  }
);

const editorsDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Editors",
});

const resourceTypeDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Resource Type",
});

searchResults.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#search-results-bar",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search programs to help you grow, learn, and thrive",
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
        var date = new Date(item.publish_date * 1000);
        var formattedDate = new Intl.DateTimeFormat('en-US', {
          dateStyle: 'long'
        }).format(date);
        return `
          <div class="card mb-3 porkgateway-card">
                <h2 class="hit-name card-title"><a href="${item.url_path}"> ${item._highlightResult.title.value}</a></h2>
                <div class="hit-date">Publish date: <span class="formatted-date">${formattedDate}</span></div>
                <div class="hit-summary">${item._highlightResult.summary.value}</div>
          </div>
        `;
      },
    },
    cssClasses:{
      loadMore: [
        "btn-outline-danger",
        "btn",
      ],
    },
  }),



  authorsDropdown({
    container: "#authors",
    attribute: "authors",
  }),
  audiencesDropdown({
    container: "#audiences",
    attribute: "audiences",
  }),
  categoriesDropdown({
    container: "#categories",
    attribute: "categories",
  }),
  editorsDropdown({
    container: "#editors",
    attribute: "editors",
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
      return items.map(item => ({
        ...item,
        refinements: item.refinements.map(refinement => ({
          ...refinement,
          label: refinement.label, // Keep the label intact for rendering later
        }))
      }));
    },
  }),
  ]);
  searchResults.on('render', function () {
    const refinementsList = document.querySelector('#current-refinements .ais-CurrentRefinements-list');

    if (refinementsList) {
      const refinementItems = refinementsList.querySelectorAll('.ais-CurrentRefinements-category');

      refinementItems.forEach(item => {
        const categoryLabel = item.querySelector('.ais-CurrentRefinements-categoryLabel');
        const deleteButton = item.querySelector('.ais-CurrentRefinements-delete');

        // Move the category label inside the delete button
        if (categoryLabel && deleteButton) {
          deleteButton.innerHTML = categoryLabel.innerHTML; // Adding label inside the button
          deleteButton.classList.add('btn', 'btn-outline-primary');
          categoryLabel.style.display = 'none'; // Hide the categoryLabel span
        }
      });
    }
  });

searchResults.start();

      });
    },
  };
})(Drupal, once);
