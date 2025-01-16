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
      "title,text_content,rendered_content,summary",
  },
});

var objUrlParams = new URLSearchParams(window.location.search);
if (objUrlParams.has('ForStaff[query]')) {
  document.getElementById("isueo-searchall").innerHTML='<a href="https://www.extension.iastate.edu/search-results?as_q=' + objUrlParams.get('ForStaff[query]') + '">Search all of Extension</a>';
}


const searchClientResults = typesenseInstantsearchAdapterResults.searchClient;
const { infiniteHits } = instantsearch.widgets;

const searchResults = instantsearch({
  searchClient: searchClientResults,
  //indexName: "plp_programs",
  indexName: "ForStaff",
  routing: true,
});

searchBoxID = "#search-results-bar";
if (document.getElementById('ts-forstaff-search-bar')) {
  searchBoxID = "#ts-forstaff-search-bar";
}

searchResults.addWidgets([
  instantsearch.widgets.searchBox({
    container: searchBoxID,
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search Programs",
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
        imagelink = "";
//        if (item.field_plp_program_smugmug) {
//          imagelink =
//            '<img src ="https://photos.smugmug.com/photos/' +
//            item.field_plp_program_smugmug +
//            "/10000/XL/" +
//            item.field_plp_program_smugmug +
//            '-XL.jpg" alt="" />';
//        }

summary = item.summary;
if (item._highlightResult.summary) {
  summary = item._highlightResult.summary.value;
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
                <div class="hit-summary">${summary}</div>
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
]);

searchResults.addWidgets([
  instantsearch.widgets.refinementList({
    container: "#content_types",
    attribute: "content_type",
    templates: {
      item(item) {
        const { url, label, count, isRefined } = item;
      return `
        <a href="${url}">
          <span class="btn btn-outline-primary">${label} (${count})</span>
        </a>
      `;
      },
    },
  }),
]);

searchResults.addWidgets([
  instantsearch.widgets.refinementList({
    container: "#sites",
    attribute: "site_name",
    templates: {
      item(item) {
        const { url, label, count, isRefined } = item;
      return `
        <a href="${url}">
          <span class="btn btn-outline-primary">${label} (${count})</span>
        </a>
      `;
      },
    },
  }),
]);

/*
searchResults.addWidgets([
  instantsearch.widgets.refinementList({
    container: "#audiences",
    attribute: "audiences",
    templates: {
      item(item) {
        const { url, label, count, isRefined } = item;
      return `
        <a href="${url}">
          <span class="btn btn-outline-primary">${label} (${count})</span>
        </a>
      `;
      },
    },
  }),
]);
*/

/*
searchResults.addWidgets([
  instantsearch.widgets.refinementList({
    container: "#program_area",
    attribute: "program_area",
    templates: {
      item(item) {
        const { url, label, count, isRefined } = item;
      return `
        <a href="${url}">
          <span class="btn btn-outline-primary">${label} (${count})</span>
        </a>
      `;
      },
    },
  }),
]);
*/

searchResults.start();
