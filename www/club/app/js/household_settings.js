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
    const tariff_type = $("input[type=radio][name=tariff_type]:checked").val();
    const standing_charge = $("#standing_charge").val();
    const tariff = $("#tariff").val();
    const economy7_tariff = $("#economy7_tariff").val();
    // setUserAttribute("tariff", parseFloat(tariff));
    $.ajax({
        type: "PUT",
        url: "/club/set_fixed_user_tariff.json",
        data: JSON.stringify({
            "tariff_type": tariff_type,
            "standing_charge": parseFloat(standing_charge),
            "tariff": parseFloat(tariff),
            "economy7_tariff": parseFloat(economy7_tariff)
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
