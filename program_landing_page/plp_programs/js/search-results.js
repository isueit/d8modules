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
      "title,body,field_plp_program_search_terms,children_title,children_body,summary,category_name,topic_names",
  },
});

var objUrlParams = new URLSearchParams(window.location.search);
if (objUrlParams.has("plp_programs[query]")) {
  document.getElementById("isueo-searchall").innerHTML =
    '<a href="https://www.extension.iastate.edu/search-results?as_q=' +
    objUrlParams.get("plp_programs[query]") +
    '">Search all of Extension</a>';
}

const searchClientResults = typesenseInstantsearchAdapterResults.searchClient;
const { infiniteHits } = instantsearch.widgets;

const searchResults = instantsearch({
  searchClient: searchClientResults,
  indexName: "plp_programs",
  routing: true,
});

const MOBILE_WIDTH = 375;

const programAreaDropdown = createDropdown(
  instantsearch.widgets.refinementList,
  {
    closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
    buttonText: "Program Area",
  }
);

const audienceDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Audience",
});

const categoriesDropdown = createDropdown(
  instantsearch.widgets.refinementList,
  {
    closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
    buttonText: "Category",
  }
);

const topicsDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Topics",
});

searchResults.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#search-results-bar",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search programs to help you grow, learn, and thrive",
    queryHook(query, search) {
      document.getElementById("isueo-searchall").innerHTML='<a href="https://www.extension.iastate.edu/search-results?as_q=' + query + '">Search all of Extension</a>';
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
        var imagelink = "";
        if (item.field_plp_program_smugmug) {
          imagelink =
            '<img src ="https://photos.smugmug.com/photos/' +
            item.field_plp_program_smugmug +
            "/10000/XL/" +
            item.field_plp_program_smugmug +
            '-XL.jpg" alt="" />';
        }
        return `
          <div class="card mb-3">
            <div class="row no-gutters">
              <div class="col-md-4">
                ${imagelink}
              </div>
            <div class="col-md-8">
              <div class="card-body">
                <h2 class="hit-name card-title"><a href="${item.url}"> ${item._highlightResult.title.value}</a></h2>
                <div class="hit-summary">${item._highlightResult.summary.value}</div>
              </div>
            </div>
            </div>
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



  programAreaDropdown({
    container: "#program_area",
    attribute: "program_area",
  }),
  audienceDropdown({
    container: "#audience",
    attribute: "audiences",
  }),
  categoriesDropdown({
    container: "#categories",
    attribute: "category_name",
  }),
  topicsDropdown({
    container: "#topics",
    attribute: "topic_names",
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
