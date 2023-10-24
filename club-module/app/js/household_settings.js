function updateTargetMin() {
    targetMin = $("#dailyTargetMin").val();
    setUserAttribute("targetMin", parseFloat(targetMin));
}
function updateTargetMax() {
    targetMax = $("#dailyTargetMax").val();
    setUserAttribute("targetMax", parseFloat(targetMax));
    // Todo redraw any graphs
}
function updateTariff() {
    const tariff = $("#tariff").val();
    setUserAttribute("tariff", parseFloat(tariff));
}

function setUserAttribute(name, value) {
    $.ajax({
        type: "PUT",           
        url: "/user/setattribute.json",                         
        data: JSON.stringify({
            name: name,
            value: value,
        }),
        dataType: 'json',
        contentType: 'application/json',
//        success: function(result) {
  //      }
        // TODO handle failure
    });
}
