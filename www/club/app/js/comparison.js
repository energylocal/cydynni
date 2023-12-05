
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

function household_comparison_graph_series(days, group) {
  const tariffPeriods = new Map();
  for (const day of days) {
    for (const [tariffPeriod,value] of Object.entries(day.demand)) {
      if (tariffPeriod != 'total') {
        if (!tariffPeriods.has(tariffPeriod)) {
          tariffPeriods.set(tariffPeriod, []);
        }
        tariffPeriods.get(tariffPeriod).push(value);
      }
    }
  }

  const series = [];
  const colors = [];
  for (const [tariffPeriod,data] of tariffPeriods) {
    series.push({
      name: tariffPeriod+" ("+group+")",
      group: group,
      data: data
    });
    colors.push(tariffColorMap[tariffPeriod]);
    console.log(colors);
  }
  return [series, colors]
}

function household_comparison_bargraph_draw() {

  const compareNum = (a, b) => (a - b);

  // alert(JSON.stringify(household_comparison_data));
  const days = Object.values(household_comparison_data).sort(compareNum);
  const previousDays = Object.values(previous_household_comparison_data).sort(compareNum);

  if (days.length == 0 || previousDays.length == 0) {
    return;
  }

  console.log(JSON.stringify(days));
  console.log(days);

  const [currentSeries, currentColors] = household_comparison_graph_series(days, "current");
  console.log("c:");
  console.log(currentSeries);
  const [prevSeries, prevColors] = household_comparison_graph_series(previousDays, "previous");
  console.log("p:");
  console.log(prevSeries);
  const series = currentSeries.concat(prevSeries);
  const colors = currentColors.concat(prevColors);
  console.log("s:");
  console.log(series);
  console.log(colors);
 

  /*
  const tariffPeriods = new Map();
  for (const day of days) {
    for (const [tariffPeriod,value] of Object.entries(day.demand)) {
      if (tariffPeriod != 'total') {
        if (!tariffPeriods.has(tariffPeriod)) {
          tariffPeriods.set(tariffPeriod, []);
        }
        console.log(tariffPeriods);
        console.log(tariffPeriod);
        console.log(value);
        tariffPeriods.get(tariffPeriod).push(value);
      }
    }
  }
  console.log(tariffPeriods);
  const series = [];
  const colors = [];
  for (const [tariffPeriod,data] of tariffPeriods) {
    series.push({
      name: tariffPeriod,
      group: 'current',
      data: data
    });
    colors.push(tariffColorMap[tariffPeriod]);
    console.log(colors);
  }*/

 /* const overnight = days.map((day) => day.demand.overnight);
  const daytime = days.map((day) => day.demand.daytime);
  const evening = days.map((day) => day.demand.evening);
  console.log(previousDays);
  const previousOvernight = previousDays.map((day) => day.demand.overnight);
  const previousDaytime = previousDays.map((day) => day.demand.daytime);
  const previousEvening = previousDays.map((day) => day.demand.evening);
*/

  const somePrevious = previousDays.slice(0, days.length);
  const labels = days.map((day, i) => {
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
    series: /*[
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
    ]*/
    series,
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
    colors: colors,
    //colors: ['#029C5C', '#BF9A41', '#BC4B2D', '#014C2D', '#FFB401', '#E6602B'],
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

if (session.read) {
//  household_comparison_bargraph_load();
//  household_comparison_bargraph_draw();
}
