var view =
{
  'start':0,
  'end':0,
  'first_data':0,

  'zoomout':function ()
  {
    var time_window = this.end - this.start;
    var middle = this.start + time_window / 2;
    time_window = time_window * 2;
    this.start = Math.max(middle - (time_window/2), this.first_data);
    this.end = Math.min(middle + (time_window/2), this.now());
  },

  'zoomin':function ()
  {
    var time_window = this.end - this.start;
    var middle = this.start + time_window / 2;
    time_window = time_window * 0.5;
    this.start = middle - (time_window/2);
    this.end = middle + (time_window/2);
  },

  'panright':function ()
  {
    var time_window = this.end - this.start;
    var shiftsize = time_window * 0.2;
    var now = this.now();
    if (this.end + shiftsize > now) {
      shiftsize = now - this.end;
    }
    this.start += shiftsize;
    this.end += shiftsize;
  },

  'panleft':function ()
  {
    var time_window = this.end - this.start;
    var shiftsize = time_window * 0.2;
    if (this.start - shiftsize < this.first_data) {
      shiftsize = this.start - this.first_data;
    }
    this.start -= shiftsize;
    this.end -= shiftsize;
  },

  'timewindow':function(time)
  {
    this.start = ((new Date()).getTime())-(3600000*24*time);	//Get start time
    this.end = (new Date()).getTime();	//Get end time
  },
  
  'round_interval':function(interval)
  {
      var outinterval = 10;
      if (interval>10) outinterval = 10;
      if (interval>15) outinterval = 15;
      if (interval>20) outinterval = 20;
      if (interval>30) outinterval = 30;
      if (interval>60) outinterval = 60;
      if (interval>120) outinterval = 120;
      if (interval>180) outinterval = 180;
      if (interval>300) outinterval = 300;
      if (interval>600) outinterval = 600;
      if (interval>900) outinterval = 900;
      if (interval>1200) outinterval = 1200;
      if (interval>1800) outinterval = 1800;
      if (interval>3600*1) outinterval = 3600*1;
      if (interval>3600*2) outinterval = 3600*2;
      if (interval>3600*3) outinterval = 3600*3;
      if (interval>3600*4) outinterval = 3600*4;
      if (interval>3600*5) outinterval = 3600*5;
      if (interval>3600*6) outinterval = 3600*6;
      if (interval>3600*12) outinterval = 3600*12;
      if (interval>3600*24) outinterval = 3600*24;
      if (interval>3600*36) outinterval = 3600*36;
      if (interval>3600*48) outinterval = 3600*48;
      if (interval>3600*72) outinterval = 3600*72;
      
      return outinterval;
  },

  'now':function()
  {
    var date = new Date();
    return date.getTime();
  }
}

function stats(data)
{
    var sum = 0;
    var i=0;
    var minval = 0;
    var maxval = 0;
    for (z in data)
    {
        var val = data[z][1];
        if (val!=null) 
        {
            if (i==0) {
                maxval = val;
                minval = val;
            }
            if (val>maxval) maxval = val;
            if (val<minval) minval = val;
            sum += val;
            i++;
        }
    }
    var mean = sum / i;
    sum = 0, i=0;
    for (z in data)
    {
        sum += (data[z][1] - mean) * (data[z][1] - mean);
        i++;
    }
    var stdev = Math.sqrt(sum / i);
    
    return {
        "minval":minval,
        "maxval":maxval,
        "diff":maxval-minval,
        "mean":mean,
        "stdev":stdev
    }
}

// http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values/901144#901144
var urlParams;
(window.onpopstate = function () {
    var match,
        pl = /\+/g, // Regex for replacing addition symbol with a space
        search = /([^&=]+)=?([^&]*)/g,
        decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
        query = window.location.search.substring(1);

    urlParams = {};
    while (match = search.exec(query))
       urlParams[decode(match[1])] = decode(match[2]);
})();

function tooltip(x, y, contents, bgColour)
{
    var offset = 10; // use higher values for a little spacing between `x,y` and tooltip
    var elem = $('<div id="tooltip" >' + contents + '</div>').css({
        position: 'absolute',
        color: "#000",
        display: 'none',
        'font-weight':'bold',
        border: '1px solid #000',
        padding: '2px',
        'background-color': bgColour,
        opacity: '0.8',
        "text-align": 'left'
    }).appendTo("body").fadeIn(00);

    var elemY = y - elem.height() - offset;
    var elemX = x - elem.width()  - offset;
    if (elemY < 0) { elemY = 0; } 
    if (elemX < 0) { elemX = 0; } 
    elem.css({
        top: elemY,
        left: elemX
    });
}
