var sort = $("#sort");
var sortOrder = $("#sortOrder");

function updateSortOrder() {
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

    if(sortOrderValue === optionOneValue) {
        $optionOne.prop("selected", true);
    }
    else {
        $optionTwo.prop("selected", true);
    }
}

sort.change(updateSortOrder);
$(document).ready(updateSortOrder);


