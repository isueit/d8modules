(function (Drupal, once) {
  Drupal.behaviors.porkgatewaySearchbar = {
    attach(context) {
      once('porkgateway-search', '#porkgateway-search-bar', context).forEach((el) => {
 const typesenseInstantsearchAdapterBar = new TypesenseInstantSearchAdapter({
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
  routing: true,
});

searchBar.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#porkgateway-search-bar",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search programs to help you grow, learn, and thrive",
    queryHook(query, search) {
      newurl = '/results?porkgateway[query]=' + query;
      window.location.href = newurl;
      //search(query);
    },
  }),
]);

searchBar.start();

      });
    },
  };
})(Drupal, once);
