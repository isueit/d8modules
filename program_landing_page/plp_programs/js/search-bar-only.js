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
      "title,body,field_plp_program_search_terms,children_title,children_body,summary,category_name,topic_names",
  },
});

const searchClientBar = typesenseInstantsearchAdapterBar.searchClient;

const searchBar = instantsearch({
  searchClient: searchClientBar,
  indexName: "plp_programs",
  routing: true,
});

searchBar.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#search-bar-only",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search Programs",
    queryHook(query, search) {
      newurl = '/program/program-search?plp_programs[query]=' + query;
      window.location.href = newurl;
      //search(query);
    },
  }),
]);

searchBar.start();
