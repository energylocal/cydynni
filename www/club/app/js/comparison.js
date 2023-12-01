
let household_comparison_data = {};
let previous_household_comparison_data = {};

function household_comparison_bargraph_load() {
  console.log("Loading household comparrison bargraph data...")
  const url = path+"data/daily?start="+(view.start/1000)+"&end="+(view.end/1000)+"&apikey="+session['apikey_read'];
  $.ajax({
    url: url,
    dataType: 'json',
    async: true,
    success: function(result) {
      if (result.success === false) {
        console.log("Failed to load household comparison data from "+url+": "+result.message);
        alert(result.message);
        return;
      }
      household_comparison_data = result;
      household_comparison_bargraph_draw();
    }
  });


  // alert(JSON.stringify(view));
  const start = (view.start/1000) - (60*60*24*7);
  const end = (view.end/1000)- (60*60*24*7);
  const previousURL = path+"data/daily?start="+start+"&end="+end+"&apikey="+session['apikey_read']+"&previousluke";
  $.ajax({
    url: previousURL,
    dataType: 'json',
    async: true,
    success: function(result) {
      if (result.success === false) {
        console.log("Failed to load household comparison previous data from "+previousURL+": "+result.message);
        alert(result.message);
        return;
      }
      previous_household_comparison_data = result;
      household_comparison_bargraph_draw();
    }
  });
}

function unixTimeToDay(unixTimestamp) {
  const date = new Date(unixTimestamp * 1000); // Convert to milliseconds
  const options = { month: 'short', day: 'numeric', daySuffix: 'numeric' };
  return date.toLocaleDateString('en-GB', options);
}

function household_comparison_bargraph_draw() {

  const overnight = household_comparison_data.map((day) => day[1][0]);
  const daytime = household_comparison_data.map((day) => day[1][1]);
  const evening = household_comparison_data.map((day) => day[1][2]);
  const previousOvernight = previous_household_comparison_data.map((day) => day[1][0]);
  const previousDaytime = previous_household_comparison_data.map((day) => day[1][1]);
  const previousEvening = previous_household_comparison_data.map((day) => day[1][2]);


  previous_household_comparison_data = previous_household_comparison_data.slice(0, household_comparison_data.length);
  const labels = household_comparison_data.map((day, i) => {
    let previous = "";
    let current = "";
    if (previous_household_comparison_data.length >= i && previous_household_comparison_data.length > 0) {  
      previous = unixTimeToDay(previous_household_comparison_data[i][0]);
    }
    if (day.length > 0) {
      current = unixTimeToDay(day[0])
    }
    if (previous.length == 0) {
      return current
    }
    return previous + ' / ' + current;
  });
  
  // alert(JSON.stringify(overnight));

  var options = {
    annotations: {
      yaxis: [
        {
          y: targetMin,
          y2: targetMax,
          borderColor: '#000',
          fillColor: '#209ED3',
          label: {
            text: 'Daily Target'
          }
        }
      ]
    },
    series: [
      {
        name: 'Overnight (previous week)',
        group: 'previous',
        data: previousOvernight,
      },
      {
        name: 'Daytime (previous week)',
        group: 'previous',
        data: previousDaytime,
      },
      {
        name: 'Evening (previous week)',
        group: 'previous',
        data: previousEvening,
      },
      {
        name: 'Overnight',
        group: 'current',
        data: overnight,
      },
      {
        name: 'Daytime',
        group: 'current',
        data: daytime,
      },
      {
        name: 'Evening',
        group: 'current',
        data: evening,
      },
    ],
    chart: {
      type: 'bar',
      height: 400,
      stacked: true,
      toolbar: {
        show: true,
        tools:{
          download: false // <== line to add
        }
      },
    },
    stroke: {
      width: 0,
      // colors: ['#fff']
    },
    dataLabels: {
      enabled: false,
      // formatter: (val) => {
      //   return val / 1000 + 'K'
      // }
    },
    plotOptions: {
      bar: {
        horizontal: false,
        highlightDataSeries: false,
      }
    },
    xaxis: {
      categories: labels
    },
    fill: {
      opacity: 1
    },
    colors: ['#029C5C', '#BF9A41', '#BC4B2D', '#014C2D', '#FFB401', '#E6602B'],
    yaxis: {
      labels: {
        formatter: (val) => {
          return val + ' kWh'
        }
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      onItemClick: {
        toggleDataSeries: false // This disables the hiding of series when legend item is clicked
      },
    },
  };

  var chart = new ApexCharts(document.querySelector("#household_comparison_bargraph_placeholder"), options);
  chart.render();
}

household_comparison_bargraph_load();
household_comparison_bargraph_draw();