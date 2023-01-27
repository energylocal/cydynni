<?php global $path, $session, $club_settings; ?>

<style>
.label {cursor:pointer; }
</style>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<h3>Admin club</h3>

<div id="app">

<div class="input-prepend">
    <span class="add-on">Select Club:</span>
    <select v-model="club" @change="load">
      <option v-for="(key,name) in settings" :value="name">{{ name }}</option>
    </select>
</div>

<p><b>Club ID: </b><span>{{ settings[club].club_id }}</span></p>

<p>Share: <input type="checkbox" v-model="settings[club].share" /></p>

<p><div class="input-prepend"><span class="add-on">Club name:</span><input type="text" v-model="settings[club].name" /></div></p>

<p><div class="input-prepend input-append">
  <span class="add-on">Generator:</span>
  <select v-model="settings[club].generator" style="width:115px">
    <option value="solar">Solar</option>
    <option value="hydro">Hydro</option>
    <option value="wind">Wind</option>
  </select>
  
  <input type="color" style="width:40px; height:30px" v-model="settings[club].generator_color" />
  <span class="add-on">Export:</span>
  <input type="color" style="width:40px; height:30px" v-model="settings[club].export_color" />
</div></p>

<p><div class="input-prepend input-append">
  <span class="add-on" style="width:180px; text-align:left">Generation feed</span>
  <select v-model="settings[club].generation_feed">
    <optgroup v-for="(tag,key) in feedbytag" v-bind:label="key">
      <option v-for="f in feedbytag[key]" v-bind:value="f.id">{{ f.name }}</option>
    </optgroup>
  </select>
</div></p>

<p><div class="input-prepend input-append">
  <span class="add-on" style="width:180px; text-align:left">Consumption feed</span>
  <select v-model="settings[club].consumption_feed">
    <optgroup v-for="(tag,key) in feedbytag" v-bind:label="key">
      <option v-for="f in feedbytag[key]" v-bind:value="f.id">{{ f.name }}</option>
    </optgroup>
  </select>
</div></p>

<p><div class="input-prepend input-append">
  <span class="add-on" style="width:180px; text-align:left">Generation forecast feed</span>
  <select v-model="settings[club].generation_forecast_feed">
    <optgroup v-for="(tag,key) in feedbytag" v-bind:label="key">
      <option v-for="f in feedbytag[key]" v-bind:value="f.id">{{ f.name }}</option>
    </optgroup>
  </select>
</div></p>

<p><div class="input-prepend input-append">
  <span class="add-on" style="width:180px; text-align:left">Consumption forecast feed</span>
  <select v-model="settings[club].consumption_forecast_feed">
    <optgroup v-for="(tag,key) in feedbytag" v-bind:label="key">
      <option v-for="f in feedbytag[key]" v-bind:value="f.id">{{ f.name }}</option>
    </optgroup>
  </select>
</div></p>

<p><div class="input-prepend">
  <span class="add-on">Unit price comparison</span>
  <input type="text" v-model="settings[club].unitprice_comparison" style="width:80px" /></div>
</p>

<p><div class="input-prepend input-append">
  <span class="add-on">Generator limit</span>
  <input type="text" v-model="settings[club].gen_limit" style="width:80px" />
  <span class="add-on">scale</span>
  <input type="text" v-model="settings[club].gen_scale" style="width:80px" />
</div></p>

<div class="input-prepend">
  <span class="add-on">Select tariff period</span>
  <select v-model="selected_tariff" >
    <option v-for="(t,index) in settings[club].tariff_history" v-bind:value="index">{{ t.start }} - {{ t.end }}</option>
  </select>
</div>

<div :set="tariff = settings[club].tariff_history[selected_tariff]" style="border:1px solid #ccc; padding:10px; width:525px;">

  <div class="input-prepend input-append">
    <span class="add-on">Start</span><input type="text" v-model="tariff.start" style="width:80px" />
    <span class="add-on">End</span><input type="text" v-model="tariff.end" style="width:80px" />
    <span class="add-on">Standing charge</span><input type="text" v-model="tariff.standing_charge" style="width:80px" />
  </div>
  
  <table>
    <tr>
      <th>Name</th>
      <th>Start</th>
      <th>End</th>
      <th>Generator</th>
      <th>Import</th>
    </tr>
    <tr v-for="(t,i) in tariff.tariffs">
      <td><input type="text" style="width:80px; text-align:center" v-model="t.name" /></td>
      <td><input type="text" style="width:80px; text-align:center" v-model="t.start" /></td>
      <td><input type="text" style="width:80px; text-align:center" v-model="t.end" /></td>
      <td><input type="text" style="width:80px; text-align:center" v-model="t.generator" /></td>
      <td><input type="text" style="width:80px; text-align:center" v-model="t.import" /></td>
      <td><input type="color" style="width:40px; height:30px" v-model="t.color" /></td>
    </tr>
  </table>
  <button class="btn" @click="removeLine(selected_tariff)">Remove line</button> 
  <button class="btn" @click="addLine(selected_tariff)">Add line</button>
</div>
<br>
<button class="btn" @click="addNewTariffPeriod()">Add new tariff period</button>

</div>


<script>

var club = localStorage.getItem('selected_club_name');
if (club==null) club = "bethesda";

var feeds = {}

var feeds = feed.list();
var feedbytag = {}
for (var z in feeds) {
    var tag = feeds[z].tag;
    if (feedbytag[tag]==undefined) feedbytag[tag] = []
    feedbytag[tag].push(feeds[z]);
}

var users = {}
var original = {}
var data_status = {}

var settings = <?php echo json_encode($club_settings); ?>;

for (var club_name in settings) {
    for (var z in settings[club_name].tariff_history) {
        settings[club_name].tariff_history[z].start = time_to_datestr(settings[club_name].tariff_history[z].start);
        settings[club_name].tariff_history[z].end = time_to_datestr(settings[club_name].tariff_history[z].end)
    }
}

var app = new Vue({
    el: '#app',
    data: {
        club: club,
        settings: settings,
        feedbytag: feedbytag,
        selected_tariff: settings[club].tariff_history.length-1
    },
    methods: {
      load: function() {
          app.selected_tariff = settings[app.club].tariff_history.length-1;
          
          localStorage.setItem('selected_club_name',app.club);
      },
      addLine: function(i) {
        var t = settings[club].tariff_history[i].tariffs.length-1;        
        settings[club].tariff_history[i].tariffs.push(settings[club].tariff_history[i].tariffs[t])
      },
      removeLine: function(i) {
        var t = settings[club].tariff_history[i].tariffs.length-1;        
        settings[club].tariff_history[i].tariffs.pop()
      },
      addNewTariffPeriod: function () {
        settings[club].tariff_history.push({
          "start":"01-01-2010",
          "end":"01-03-2019",
          "tariffs":[
            {"name":"morning","start":"06:00","end":"11:00","generator":7,"import":12,"color":"#ffdc00"}
          ]}
        )
        app.selected_tariff[club] = settings[club].tariff_history.length-1
      }
    },
    filters: {
    }
});


function time_to_datestr(unix) {
  const zeroPad = (num, places) => String(num).padStart(places, '0')
  var d = new Date();
  d.setTime(unix*1000);
  return zeroPad(d.getDate(),2)+"-"+zeroPad((d.getMonth()+1),2)+"-"+d.getFullYear();
}
</script>


