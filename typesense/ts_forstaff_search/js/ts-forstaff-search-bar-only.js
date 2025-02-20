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
      "title,text_content,rendered_content,summary"
  },
});

const searchClientBar = typesenseInstantsearchAdapterBar.searchClient;

const searchBar = instantsearch({
  searchClient: searchClientBar,
  indexName: "ForStaff",
  routing: true,
});

searchBar.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#ts-forstaff-search-bar-only",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search ForStaff Info",
    queryHook(query, search) {
      newurl = 'search-results-0?ForStaff[query]=' + query;
      window.location.href = newurl;
      //search(query);
    },
  }),
]);

searchBar.start();
