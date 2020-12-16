
var http = require('http');
var util = require('util');
var OWL = require('../owl');
var request = require('request');

var owl = new OWL();
owl.monitor(3001);

// Event Messages --------------------------------------------------------

owl.on('connect', function( event ) {
	console.log( "connect" );
});

owl.on('electricity', function( event ) {

    owldata = JSON.parse(event);

    nodeid = owldata.id

    data = {}

    data.P1 = 1*owldata.channels[0][0].current
    data.P2 = 1*owldata.channels[1][0].current
    data.P3 = 1*owldata.channels[2][0].current

    data.E1 = 1*owldata.channels[0][1].day
    data.E2 = 1*owldata.channels[1][1].day
    data.E3 = 1*owldata.channels[2][1].day

    apikey_map = {
     
    }

    // console.log('node='+nodeid+'&fulljson='+JSON.stringify(data)+'&apikey='+apikey_map[nodeid])

    if (apikey_map[nodeid]!=undefined) {
      request('https://dashboard.energylocal.org.uk/input/post?node='+nodeid+'&fulljson='+JSON.stringify(data)+'&apikey='+apikey_map[nodeid], { json: true }, (err, res, body) => {
        if (err) { return console.log(err); }
        // console.log(res);
        // console.log(body);
      });
    }
});

owl.on('heating', function( event ) {
	console.log( "heating = " + util.inspect( event, {"depth": null}) );
});

owl.on('weather', function( event ) {
	console.log( "weather = " + util.inspect( event, {"depth": null}) );
});

owl.on('solar', function( event ) {
	console.log( "solar = " + util.inspect(event, {"depth": null}) );
});

owl.on('disconnect', function( event ) {
	console.log( "disconnect" );
});
