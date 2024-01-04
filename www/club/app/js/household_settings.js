function updateTargetMin() {
    targetMin = $("#dailyTargetMin").val();
    setUserAttribute("targetMin", parseFloat(targetMin));
    // TODO redraw any graphs
}
function updateTargetMax() {
    targetMax = $("#dailyTargetMax").val();
    setUserAttribute("targetMax", parseFloat(targetMax));
    // TODO redraw any graphs
}
function updateTariff() {
    const tariff = $("#tariff").val();
    // setUserAttribute("tariff", parseFloat(tariff));
    $.ajax({
        type: "PUT",           
        url: "/club/set_fixed_user_tariff.json",                         
        data: JSON.stringify({
            name: "tariff",
            value: parseFloat(tariff),
        }),
        dataType: 'json',
        contentType: 'application/json'
    });
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
