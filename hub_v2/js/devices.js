var devices = {};
var inputs = {};
var nodes = {};
var nodes_display = {};
var selected_inputs = {};
var selected_device = false;
var device_templates = {};
var updater;

function device_load()
{
    $.ajax({ url: emoncmspath+"device/template/listshort.json", dataType: 'json', async: true, success: function(data) { 
        device_templates = data; 
        update();
    }});

    //updaterStart(update, 5000);
}

function updaterStart(func, interval){
	  clearInterval(updater);
	  updater = null;
	  if (interval > 0) updater = setInterval(func, interval);
}

// ---------------------------------------------------------------------------------------------
// Fetch device and input lists
// ---------------------------------------------------------------------------------------------

function update(){

    // Join and include device data
    $.ajax({ url: emoncmspath+"device/list.json", dataType: 'json', async: true, success: function(data) {
        
        // Associative array of devices by nodeid
        devices = {};
        for (var z in data) devices[data[z].nodeid] = data[z];
        
        var requestTime = (new Date()).getTime();
        $.ajax({ url: emoncmspath+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
            table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
	          
	          // Associative array of inputs by id
            inputs = {};
	          for (var z in data) inputs[data[z].id] = data[z];
	          
	          // Assign inputs to devices
	          for (var z in inputs) {
	              // Device does not exist which means this is likely a new system or that the device was deleted
	              // There needs to be a corresponding device for every node and so the system needs to recreate the device here
	              if (devices[inputs[z].nodeid]==undefined) {
	                  devices[inputs[z].nodeid] = {description:""};
	                  // Device creation
	                  $.ajax({ url: emoncmspath+"device/create.json?nodeid="+inputs[z].nodeid, dataType: 'json', async: true, success: function(data) {
	                      if (!data) alert("There was an error creating device: "+inputs[z].nodeid); 
	                  }});
	              }
	              if (nodes_display[inputs[z].nodeid]==undefined) nodes_display[inputs[z].nodeid] = false;
	              if (devices[inputs[z].nodeid].inputs==undefined) devices[inputs[z].nodeid].inputs = [];
	              devices[inputs[z].nodeid].inputs.push(inputs[z]);
	          }
	          
	          draw_devices();
        }});
    }});
}

// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices()
{
    // Draw node/input list
    var out = "";
    for (var node in devices) {
        
        // Control node
        var control_node = false;
        if (device_templates[devices[node].type]!=undefined && device_templates[devices[node].type].control) control_node = true;
    
        var visible = "hide"; if (nodes_display[node]) visible = "";
        
        out += "<div class='node'>";
        out += "  <div class='node-info' node='"+node+"'>";
        out += "    <div class='device-name'>"+node+":</div>";
        out += "    <div class='device-description'>"+devices[node].description+"</div>";
        // out += "    <div class='device-configure'>CONFIG</div>";
        // out += "    <div class='device-key'><i class='icon-lock icon-white'></i></div>"; 
        out += "    <div class='device-delete "+visible+"'><i class='icon-trash icon-white'></i></div>";
        out += "  </div>";
        
        if (!control_node) {
            out += "<div class='node-inputs "+visible+"' node='"+node+"'>";
            
            for (var i in devices[node].inputs) {
                var input = devices[node].inputs[i];
                
                var selected = "";
                if (selected_inputs[input.id]!=undefined && selected_inputs[input.id]==true) 
                    selected = "checked";
                
                out += "<div class='node-input' id="+input.id+">";
                out += "<div class='select'><div class='ipad'><input class='input-select' type='checkbox' id='"+input.id+"' "+selected+" /></div></div>";
                out += "<div class='name'><div class='ipad'>"+input.name+"</div></div>";
                
                // if (processlist_ui != undefined)  out += "<div class='processlist'><div class='ipad'>"+processlist_ui.drawpreview(input.processList)+"</div></div>";
                
                out += "<div class='node-input-right'>";
                out += "<div class='time'>"+list_format_updated(input.time)+"</div>";
                out += "<div class='value'>"+list_format_value(input.value)+"</div>";
                out += "<div class='configure' id='"+input.id+"'><i class='icon-wrench'></i></div>";
                out += "</div>";
                out += "</div>";
            }
            
            out += "</div>";
        } else {
            out += "<div class='node-scheduler hide' node='"+node+"'></div>";
        }
        out += "</div>";
       

    }
    $("#table").html(out);

    $('#input-loader').hide();
    if (out=="") {
        $("#noinputs").show();
        $("#apihelphead").hide();
    } else {
        $("#noinputs").hide();
        $("#apihelphead").show();
    }

    for (var node in devices) {
        if (device_templates[devices[node].type]!=undefined && device_templates[devices[node].type].control) {
            $(".node-info[node='"+node+"'] .device-schedule").show();
        }
    }
    
    autowidth(".node-inputs .name",10);
    autowidth(".node-inputs .value",10);
    resize();
}
// ---------------------------------------------------------------------------------------------

function autowidth(element,padding) {
    var mw = 0;
    $(element).each(function(){
        var w = $(this).width();
        if (w>mw) mw = w;
    });
    
    $(element).width(mw+padding);
    return mw;
}

// Show/hide node on click
$("#table").on("click",".node-info",function() {
    var node = $(this).attr('node');
    
    var visible = false;
    if (nodes_display[node]) visible = true;
    
    for (var n in nodes_display) {
        nodes_display[n] = false;
    }
    
    if (!visible) nodes_display[node] = true;
    
    //if (nodes_display[node]) {
    //    nodes_display[node] = false;
    //} else {
    //    nodes_display[node] = true;
    //}

    draw_devices();
    
    if (device_templates[devices[node].type]!=undefined && device_templates[devices[node].type].control) {
        if (nodes_display[node]) draw_scheduler(node);
    }
});

$("#table").on("click",".input-select",function(e) {
    input_selection();
});

$("#input-selection").change(function(){
    var selection = $(this).val();
    
    if (selection=="all") {
        for (var id in inputs) selected_inputs[id] = true;
        $(".input-select").prop('checked', true); 
        
    } else if (selection=="none") {
        selected_inputs = {};
        $(".input-select").prop('checked', false); 
    }
    input_selection();
});
  
function input_selection() 
{
    selected_inputs = {};
    var num_selected = 0;
    $(".input-select").each(function(){
        var id = $(this).attr("id");
        selected_inputs[id] = $(this)[0].checked;
        if (selected_inputs[id]==true) num_selected += 1;
    });

    if (num_selected>0) {
        $(".input-delete").show();
    } else {
        $(".input-delete").hide();
    }

    if (num_selected==1) {
        // $(".feed-edit").show();	  
    } else {
        // $(".feed-edit").hide();
    }
}

$("#table").on("click",".device-key",function(e) {
    e.stopPropagation();
    var node = $(this).parent().attr("node");
    $(".node-info[node='"+node+"'] .device-key").html(devices[node].devicekey);    
});

$("#table").on("click",".device-schedule",function(e) {
    e.stopPropagation();
    var node = $(this).parent().attr("node");
    draw_scheduler(node);
});

$("#table").on("click",".device-delete",function(e) {
    e.stopPropagation();
    var node = $(this).parent().attr("node");
    var deviceid = devices[node].id;

    $("#device-delete-modal-name").html(node);
    $("#DeviceDeleteModal").show();
    $("#DeviceDeleteModal").attr("node",node);
    $("#DeviceDeleteModal").attr("deviceid",deviceid);
});

$(".device-delete-modal-delete").click(function(){

    var node = $("#DeviceDeleteModal").attr("node");
    var deviceid = $("#DeviceDeleteModal").attr("deviceid");
    console.log("DELETE: "+node+" deviceid:"+deviceid);
    
    // Delete schedule
    $.ajax({ url: emoncmspath+"demandshaper/delete.json", data: "device="+node, dataType: 'json', async: true, success: function(result) {
        console.log("demandshaper/delete:");
        console.log(result);

        if (devices[node].inputs != undefined) {
            var inputIds = [];
            for (var i in devices[node].inputs) {
                inputIds.push(parseInt(devices[node].inputs[i].id));
            }
            
            $.ajax({ url: emoncmspath+"input/delete.json", data: "inputids="+JSON.stringify(inputIds), async: false, success: function(result){
                 console.log("input/delete:");
                 console.log(result);
            }});
        }
        
        if (deviceid) {
            // Delete device
            $.ajax({ url: emoncmspath+"device/delete.json", data: "id="+deviceid, dataType: 'json', async: false, success: function(result) {
                console.log("device/delete:");
                console.log(result);
            }});
        }
        
        update();
        $("#DeviceDeleteModal").hide();
        
    }});
});
$(".device-delete-modal-cancel").click(function(){$("#DeviceDeleteModal").hide()});

$("#table").on("click",".device-configure",function(e) {
    e.stopPropagation();

    // Get device of clicked node
    var device = devices[$(this).parent().attr("node")];
	device_dialog.loadConfig(device_templates, device);
});

$(".input-delete").click(function(){
	  $('#inputDeleteModal').modal('show');
	  var out = "";
	  var ids = [];
	  for (var inputid in selected_inputs) {
		    if (selected_inputs[inputid]==true) {
			      var i = inputs[inputid];
			      if (i.processList == "" && i.description == "" && (parseInt(i.time) + (60*15)) < ((new Date).getTime() / 1000)){
				        // delete now if has no values and updated +15m
				        // ids.push(parseInt(inputid)); 
				        out += i.nodeid+":"+i.name+"<br>";
			      } else {
				        out += i.nodeid+":"+i.name+"<br>";		
			      }
		    }
	  }
	  
	  input.delete_multiple(ids);
	  update();
	  $("#inputs-to-delete").html(out);
});
  
$("#inputDelete-confirm").off('click').on('click', function(){
    var ids = [];
	  for (var inputid in selected_inputs) {
		    if (selected_inputs[inputid]==true) ids.push(parseInt(inputid));
	  }
	  input.delete_multiple(ids);
	  update();
	  $('#inputDeleteModal').modal('hide');
});

/* 
// Process list UI js
processlist_ui.init(0); // Set input context

$("#table").on('click', '.configure', function() {
    var i = inputs[$(this).attr('id')];
    console.log(i);
    var contextid = i.id; // Current Input ID
    // Input name
    var newfeedname = "";
    var contextname = "";
    if (i.description != "") { 
	      newfeedname = i.description;
	      contextname = "Node " + i.nodeid + " : " + newfeedname;
    }
    else { 
	      newfeedname = "node:" + i.nodeid+":" + i.name;
	      contextname = "Node " + i.nodeid + " : " + i.name;
    }
    var newfeedtag = "Node " + i.nodeid;
    var processlist = processlist_ui.decode(i.processList); // Input process list
    processlist_ui.load(contextid,processlist,contextname,newfeedname,newfeedtag); // load configs
});

$("#save-processlist").click(function (){
    var result = input.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
    if (result.success) { processlist_ui.saved(table); } else { alert('ERROR: Could not save processlist. '+result.message); }
});

*/

// -------------------------------------------------------------------------------------------------------
// Device authentication transfer
// -------------------------------------------------------------------------------------------------------

function auth_check(){
    $.ajax({ 
        url: emoncmspath+"device/auth/check.json", 
        dataType: 'json', 
        async: true,
        timeout: 3000
    })
    .done(function(data){
        if (data.message!=undefined && data.message==="No authentication request registered") {
            //all good
            $("#auth-check").hide();
        } else if(data.hasOwnProperty('success') && data.success===false){
            //show session timeout message
            $("#auth-check").show();
            $("#auth-check").html(t('Session Timed out')+'. <a href="/cydynni?return=/cydynni/?devices" class="btn">' + t('Please login') +'</a>');
        } else {
            //show ip authorise message
            $("#auth-check").show();
            html = "";
            html+=t('Device on ip address: ');
            html+='<span id="auth-check-ip"></span> ';
            html+=t('would like to connect');
            html+='<button class="btn btn-small auth-check-btn auth-check-allow">';
            html+=t('Allow');
            html+='</button>';
            $("#auth-check").html(html);
            $("#auth-check-ip").text(data.ip);
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown){
        //issue with connecting with hub
        message = {"success":false,"message":t("Error Connecting to Hub")};
        switch(textStatus){
            case 'error':
            case 'timeout':
            default:
                $("#auth-check").show();
                html = "";
                html+= t('Hub not available. Please ensure the Hub is powered on.');
                html+= ' <a href="/cydynni?return=/cydynni/?devices" class="btn">';
                html+= t('Re - Login');
                html+= '</a>';
                $("#auth-check").html(html);
        }
    })
    .always(function(){
        //try agian after 5000ms (after success or fail)
        window.setTimeout(function(){
            auth_check();
        }, 5000)
    });
}

$(".auth-check-allow").click(function(){
    var ip = $("#auth-check-ip").html();
    $.ajax({ url: emoncmspath+"device/auth/allow.json?ip="+ip, dataType: 'json', async: true, success: function(data) {
        $("#auth-check").hide();
    }});
});

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------
var show_processlist = true;
var show_select = true;
var show_time = true;
var show_value = true;

$(window).resize(function(){ resize(); });

function resize() 
{
    show_processlist = true;
    show_select = true;
    show_time = true;
    show_value = true;

    $(".node-input").each(function(){
         var node_input_width = $(this).width();
         if (node_input_width>0) {
             var w = node_input_width-10;
             
             var tw = 0;
             tw += $(this).find(".name").width();
             tw += $(this).find(".configure").width();

             tw += $(this).find(".select").width();
             if (tw>w) show_select = false;
             
             tw += $(this).find(".value").width();
             if (tw>w) show_value = false;
             
             tw += $(this).find(".time").width();
             if (tw>w) show_time = false;   
                
             tw += $(this).find(".processlist").width();
             if (tw>w) show_processlist = false;
         }
    });
    
    if (show_select) $(".select").show(); else $(".select").hide();
    if (show_time) $(".time").show(); else $(".time").hide();
    if (show_value) $(".value").show(); else $(".value").hide();
    if (show_processlist) $(".processlist").show(); else $(".processlist").hide();
    
}

// Calculate and color updated time
function list_format_updated(time) {
  time = time * 1000;
  var servertime = (new Date()).getTime() - table.timeServerLocalOffset;
  var update = (new Date(time)).getTime();

  var secs = (servertime-update)/1000;
  var mins = secs/60;
  var hour = secs/3600;
  var day = hour/24;

  var updated = secs.toFixed(0) + "s";
  if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
  else if (secs< 0) updated = secs.toFixed(0) + "s"; // update time ahead of server date is signal of slow network
  else if (secs.toFixed(0) == 0) updated = "now";
  else if (day>7) updated = "inactive";
  else if (day>2) updated = day.toFixed(1)+" days";
  else if (hour>2) updated = hour.toFixed(0)+" hrs";
  else if (secs>180) updated = mins.toFixed(0)+" mins";

  secs = Math.abs(secs);
  var color = "rgb(255,0,0)";
  if (secs<25) color = "rgb(50,200,50)"
  else if (secs<60) color = "rgb(240,180,20)"; 
  else if (secs<(3600*2)) color = "rgb(255,125,20)"

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value) {
  if (value == null) return 'NULL';
  value = parseFloat(value);
  if (value>=1000) value = parseFloat((value).toFixed(0));
  else if (value>=100) value = parseFloat((value).toFixed(1));
  else if (value>=10) value = parseFloat((value).toFixed(2));
  else if (value<=-1000) value = parseFloat((value).toFixed(0));
  else if (value<=-100) value = parseFloat((value).toFixed(1));
  else if (value<10) value = parseFloat((value).toFixed(2));
  return value;
}

