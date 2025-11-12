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
      "title,description,county",
    sort_by: "sort_order:asc",
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
  indexName: "events",
  routing: true,
});

const MOBILE_WIDTH = 375;

const countyDropdown = createDropdown(
  instantsearch.widgets.refinementList,
  {
    closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
    buttonText: "County",
  }
);

const deliveryMethodDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Format",
});

const deliveryLanguageDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Language",
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

const programUnitDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Program Area",
});

const programDropdown = createDropdown(instantsearch.widgets.refinementList, {
  closeOnChange: () => window.innerWidth >= MOBILE_WIDTH,
  buttonText: "Program ID",
});

searchResults.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#search-results-bar",
    autofocus: true,
    showReset: false,
    searchAsYouType: false,
    placeholder: "Search Upcoming Events",
    //queryHook(query, search) {
    //  document.getElementById("isueo-searchall").innerHTML =
    //    '<a href="https://www.extension.iastate.edu/search-results?as_q=' +
    //    query +
    //    '">Search all of Extension</a>';
    //  search(query);
    //},
  }),

  instantsearch.widgets.configure({
    hitsPerPage: 25,
  }),

  instantsearch.widgets.infiniteHits({
    container: "#hits",
    templates: {
      item(item) {
        var imagelink = "";
        /*
        if (item.field_plp_program_smugmug) {
          imagelink =
            '<img src ="https://photos.smugmug.com/photos/' +
            item.field_plp_program_smugmug +
            "/10000/XL/" +
            item.field_plp_program_smugmug +
            '-XL.jpg" alt="" />';
        }
        */
        const days = ['Sunday,', 'Monday,', 'Tuesday,', 'Wednesday,', 'Thursday,', 'Friday,', 'Saturday,'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const timeFormatter = new Intl.DateTimeFormat('en-US', {hour: 'numeric', minute: '2-digit', hour12: true });
        var date = new Date(item.Next_Start_Date__c * 1000);
        var sessions = String(item.sessions).split(',');
        var sessionString = '';
        if (sessions.length > 1) {
          sessionString = '(Session ' + (sessions.indexOf(String(item.Next_Start_Date__c)) + 1) + ' of ' + sessions.length + ')';
        }
        var location = item.Event_Location__c;
        if (item.Program_State__c !== undefined) {
          location += ', ' + item.Program_State__c;
        }
        location += '<br>'

        return `
          <div class="ts-events-item card">
            <div class="ts-event-details">
              <div class="card-body">
                <h2 class="hit-name card-title"><a href="ts-event-details/${item.id}/${item.title.replaceAll('/', '-')}"> ${item._highlightResult.title.value} ${sessionString}</a></h2>
                  <div class="ts-events-date">
                    <span class="icon">
                      <svg id="SVG_Time_Icon" xmlns="http://www.w3.org/2000/svg" width="42.262" height="42.304" viewBox="0 0 42.262 42.304"><path id="Oval_27_-_Outline" data-name="Oval 27 - Outline" d="M21.131,2A19.156,19.156,0,0,0,7.6,34.695,19.141,19.141,0,0,0,34.658,7.609,19,19,0,0,0,21.131,2m0-2A21.152,21.152,0,1,1,0,21.152,21.141,21.141,0,0,1,21.131,0Z" transform="translate(0)"></path><path id="Path_3472" data-name="Path 3472" d="M5756.131,1591.968a1,1,0,0,1-1-1v-13.16a1,1,0,1,1,2,0V1589.5l8.839-3.465a1,1,0,1,1,.729,1.862l-10.2,4A1,1,0,0,1,5756.131,1591.968Z" transform="translate(-5735 -1567.816)"></path></svg>
                    </span>
                    <span class="visible-for-screen-readers">Time</span>
                    ${days[date.getDay()]} ${months[date.getMonth()]} ${String(date.getDate()).padStart(2,'0')} at ${timeFormatter.format(date)}
                  </div>
                  <div class="ts-event-location">
                    <span class="icon">
                      <svg xmlns="http://www.w3.org/2000/svg" width="42.262" height="42.304" viewBox="0 0 42.262 42.304"><g id="SVG_Location_Icon" transform="translate(7.5 8.5)"><path id="Oval_27_-_Outline" data-name="Oval 27 - Outline" d="M21.131,2A19.156,19.156,0,0,0,7.6,34.695,19.141,19.141,0,0,0,34.658,7.609,19,19,0,0,0,21.131,2m0-2A21.152,21.152,0,1,1,0,21.152,21.141,21.141,0,0,1,21.131,0Z" transform="translate(-7.5 -8.5)"></path><path id="Path_3472" data-name="Path 3472" d="M13.5.7a9.811,9.811,0,0,1,9.8,9.8,12.133,12.133,0,0,1-1.495,5.507,24.387,24.387,0,0,1-3.2,4.639,35.36,35.36,0,0,1-4.616,4.486.8.8,0,0,1-.982,0,35.36,35.36,0,0,1-4.616-4.486,24.387,24.387,0,0,1-3.2-4.639A12.133,12.133,0,0,1,3.7,10.5,9.811,9.811,0,0,1,13.5.7Zm0,22.764c1.838-1.557,8.2-7.354,8.2-12.964a8.2,8.2,0,1,0-16.4,0C5.3,16.117,11.661,21.908,13.5,23.464Z"></path><path id="Path_3473" data-name="Path 3473" d="M16.5,9.7a3.8,3.8,0,1,1-3.8,3.8A3.8,3.8,0,0,1,16.5,9.7Zm0,6a2.2,2.2,0,1,0-2.2-2.2A2.2,2.2,0,0,0,16.5,15.7Z" transform="translate(-3 -3)"></path></g></svg>
                    </span>
                    <span class="visible-for-screen-readers">Location</span>
                    ${location}
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

  countyDropdown({
    container: "#county",
    attribute: "county",
    sortBy: ['name:asc'],
    limit: 3000,
  }),

  programUnitDropdown({
    container: "#program-unit",
    attribute: "PrimaryProgramUnit__c",
    sortBy: ['name:asc'],
  }),

  deliveryMethodDropdown({
    container: "#delivery-method",
    attribute: "delivery_method",
  }),

  deliveryLanguageDropdown({
    container: "#delivery-language",
    attribute: "Delivery_Language__c",
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

searchResults.on("render", function () {
  const refinementsList = document.querySelector(
    "#current-refinements .ais-CurrentRefinements-list"
  );

  if (refinementsList) {
    const refinementItems = refinementsList.querySelectorAll(
      ".ais-CurrentRefinements-category"
    );

    refinementItems.forEach((item) => {
      const categoryLabel = item.querySelector(
        ".ais-CurrentRefinements-categoryLabel"
      );
      const deleteButton = item.querySelector(".ais-CurrentRefinements-delete");

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
