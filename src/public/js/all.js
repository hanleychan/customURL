var sort = $("#sort");
var sortOrder = $("#sortOrder");
var pageSelectSmall = $("#pageSelectSmall");

/**
 * Updates selectable sort order values when the sort select input is changed 
 */
function updateSortOrder(sortValueChanged) {
    var optionOneValue;
    var optionTwoValue;
    var optionOneText;
    var optionTwoText;

    $optionOne = $('<option></option>');
    $optionTwo = $('<option></option>');
    sortOrder.html($optionOne);
    sortOrder.append($optionTwo);

    if(sort.val() === "hits") {
        optionOneValue = "desc";
        optionTwoValue = "asc";
        optionOneText = "Desc";
        optionTwoText = "Asc";
    }
    else if(sort.val() === "fullURL" || sort.val() === "customURL") {
        optionOneValue = "asc";
        optionTwoValue = "desc";
        optionOneText = "A - Z";
        optionTwoText = "Z - A";
    }
    else {  // sort.val() = "added"
        optionOneValue = "desc";
        optionTwoValue = "asc";
        optionOneText = "Newest";
        optionTwoText = "Oldest";
    }

    $optionOne.attr("value", optionOneValue);
    $optionTwo.attr("value", optionTwoValue);
    $optionOne.text(optionOneText);
    $optionTwo.text(optionTwoText);

    if(sortValueChanged) {
        $optionOne.prop("selected", true);
    }
    else {
        if(sortOrderValue === optionOneValue) {
            $optionOne.prop("selected", true);
        }
        else {
            $optionTwo.prop("selected", true);
        }
    }
}

/**
 * Update selectable sort order values when the sort select input value is changed 
 */
sort.change(function() {
    updateSortOrder(true)
});


/**
 * Update sort and sort order when page loads 
 */
$(document).ready(function() {
    updateSortOrder(false);
});


/**
 * Redirects user to a different page
 */
pageSelectSmall.change(function() {
    var pageURL = "?page=" + pageSelectSmall.val();

    if(searchValue) {
        pageURL += "&search=" + searchValue
    }

    if(!(sortValue === "added" && sortOrderValue === "desc")) {
        pageURL += "&sort=" + sortValue + "&sortOrder=" + sortOrderValue;
    }

    if(displayItemsValue != 10) {
        pageURL += "&displayItems=" + displayItemsValue;
    }

    window.location = pageURL;
});


