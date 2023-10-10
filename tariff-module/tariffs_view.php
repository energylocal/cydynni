<?php
defined('EMONCMS_EXEC') or die('Restricted access');

global $path;
$v = 1;
?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.5.1/axios.min.js"></script>

<div id="app">

	<h3><?php echo $club_name; ?>: Tariffs</h3>

	<ul class="nav nav-tabs">
		<li><a href="<?php echo $path; ?>account/list?clubid=<?php echo $clubid; ?>">Accounts</a></li>
		<li class="active"><a href="<?php echo $path; ?>tariff/list?clubid=<?php echo $clubid; ?>">Tariffs</a></li>
	</ul>

	<div class="input-prepend input-append">
		<span class="add-on">New tariff</span>
		<input type="text" value="" v-model="new_tariff_name">
		<button class="btn" style="float: right;" @click="add_tariff"><i class="icon-plus"></i> Add</button>
	</div>

	<table class="table table-striped">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Created</th>
			<th>First assigned</th>
			<th>Last used</th>
			<th>Active users</th>
			<th>Actions</th>
		</tr>
		<tr v-for="(tariff,index) in tariffs">
			<td>{{tariff.id}}</td>
			<td>{{ tariff.name }}</td>
			<td>{{tariff.created}}</th>
			<td>{{tariff.first_assigned}}</th>
			<td>{{tariff.last_assigned}}</td>
			<td>{{tariff.active_users}}</td>
			<td>
				<button class="btn" @click="delete_tariff(tariff.id)"><i class="icon-trash"></i></button>
				<button class="btn" @click="edit_tariff(index)"><i class="icon-pencil"></i></button>
			</td>
		</tr>
	</table>

	<div v-if="selected_tariff!==false">

		<div class="input-prepend">
			<span class="add-on">Tariff name</span>
			<input type="text" v-model="tariffs[selected_tariff].name">
		</div>
		
		<table class="table table-striped">
			<tr>
				<th>Index</th>
				<th>Name</th>
				<th>Day</th>
				<th>Start</th>
				<!--<th>End</th>-->
				<th>Generator</th>
				<th>Import</th>
				<th>Colour</th>
				<th>Actions</th>
			</tr>
			<tr v-for="(period,index) in tariff_periods">
				<td>{{index}}</td>
				<td>
					<span v-if="edit_period_index===index">
						<input type="text" v-model="tariff_periods[edit_period_index].name" style="width:80px">
					</span>
					<span v-else>{{ period.name }}</span>
				</td>
				<td>
					<span v-if="edit_period_index===index">
						<select v-model="tariff_periods[edit_period_index].weekend" style="width:120px">
							<option value=0>All</option>
							<option value=1>Weekend</option>
						</select>
					</span>
					<span v-else>
						<span v-if="!period.weekend">All</span>
						<span v-if="period.weekend">Weekend</span>
					</span>
				</td>
				<td>
					<span v-if="edit_period_index===index">
						<input type="text" v-model="tariff_periods[edit_period_index].start" style="width:80px">
					</span>
					<span v-else>{{ period.start | toTime }}</span>
				</td>
				<!--<td>
					<span v-else>{{ period.end }}</span>
				</td>-->
				<td>
					<span v-if="edit_period_index===index">
						<div class="input-append">
							<input type="text" v-model="tariff_periods[edit_period_index].generator" style="width:50px">
							<span class="add-on">p/kWh</span>
						</div>
					</span>
					<span v-else>{{ period.generator }} p/kWh</span>
				</td>
				<td>
					<span v-if="edit_period_index===index">
						<div class="input-append">
							<input type="text" v-model="tariff_periods[edit_period_index].import" style="width:50px">
							<span class="add-on">p/kWh</span>
						</div>
					</span>
					<span v-else>{{ period.import }} p/kWh</span>
				</td>
				<td>
					<input type="color" v-model="period.color" :disabled="edit_period_index===false" style="width:80px" />
				</td>
				<td>
					<button class="btn" @click="delete_period(index)" v-if="edit_period_index===false"><i class="icon-trash"></i></button>
					<button class="btn" @click="edit_period(index)" v-if="edit_period_index===false"><i class="icon-pencil"></i></button>
					<button class="btn" @click="save_period(index)" v-if="edit_period_index===index"><i class="icon-ok"></i></button>
				</td>
			</tr>
		</table>
		<p><i>Note: Tariff prices are all in pence per kWh ex VAT</i></p>

		<!-- add new period -->
		<button class="btn" @click="add_period"><i class="icon-plus"></i> Add period</button>
	</div>
</div>

<script>
	var clubid = <?php echo $clubid; ?>;

	app = new Vue({
		el: '#app',
		data: {
			groups: false,
			selected_group: 0,
			tariffs: [],
			new_tariff_name: "",
			selected_tariff: false,
			tariff_periods: [],
			edit_period_index: false
		},
		methods: {
			add_tariff: function() {
				// url encode post body
				// club/tariff/create
				$.post(path + "tariff/create", {
						club: clubid,
						name: app.new_tariff_name
					})
					.done(function(data) {
						if (data.success) {
							app.list_tariffs();
						} else {
							alert("Error: " + data.message);
						}
					});
			},
			list_tariffs: function() {
				$.get(path + "tariff/list.json?clubid=" + clubid)
					.done(function(data) {
						app.tariffs = data;
					});
			},
			delete_tariff: function(id) {
				if (confirm("Are you sure you want to delete this tariff?")) {
					$.get(path + "tariff/delete", {
							id: id
						})
						.done(function(data) {
							if (data.success) {
								app.list_tariffs();
							} else {
								alert("Error: " + data.message);
							}
						});
				}
			},
			edit_tariff: function(index) {
				app.selected_tariff = index;

				// get tariff periods
				$.get(path + "tariff/periods", {
						id: app.tariffs[index].id
					})
					.done(function(data) {
						app.tariff_periods = data;
					});
			},
			add_period: function() {
				// add new period to end of list


				// Default day
				var weekend_auto = [0, 0, 0, 0, 1, 1, 1, 1];
				var weekend = 0;
				if (weekend_auto[app.tariff_periods.length] != undefined) {
					weekend = weekend_auto[app.tariff_periods.length];
				}

				// Default names
				var names = ["Morning", "Midday", "Evening", "Overnight", "Morning", "Midday", "Evening", "Overnight"];
				var name = "Period " + (app.tariff_periods.length + 1);
				if (names[app.tariff_periods.length] != undefined) {
					name = names[app.tariff_periods.length];
				}

				// Default start times
				var starts = [6, 12, 18, 0, 6, 12, 18, 0];
				var start = 0;
				if (starts[app.tariff_periods.length] != undefined) {
					start = starts[app.tariff_periods.length];
				}

				// Default colours
				var colours = ["#ffdc00", "#ffb401", "#e6602b", "#014c2d", "#ffdc00", "#ffb401", "#e6602b", "#014c2d"];
				var colour = "#000000";
				if (colours[app.tariff_periods.length] != undefined) {
					colour = colours[app.tariff_periods.length];
				}

				var period = {
					tariffid: app.tariffs[app.selected_tariff].id,
					name: name,
					weekend: weekend,
					start: start,
					generator: 10,
					import: 20,
					color: colour
				};
				app.tariff_periods.push(period);

				$.post(path + "tariff/addperiod", period)
					.done(function(data) {
						if (data.success) {
							app.edit_tariff(app.selected_tariff);
						} else {
							alert("Error: " + data.message);
						}
					});
			},
			edit_period: function(index) {
				app.edit_period_index = index;
			},
			save_period: function(index) {
				app.edit_period_index = false;
				// save period
				$.post(path + "tariff/saveperiod", app.tariff_periods[index])
					.done(function(data) {
						if (data.success) {
							app.edit_tariff(app.selected_tariff);
						} else {
							alert("Error: " + data.message);
						}
					});
			},
			delete_period: function(index) {
				if (confirm("Are you sure you want to delete this period?")) {
					$.get(path + "tariff/deleteperiod", {
							tariffid: app.tariff_periods[index].tariffid,
							index: app.tariff_periods[index].index
						})
						.done(function(data) {
							if (data.success) {
								app.edit_tariff(app.selected_tariff);
							} else {
								alert("Error: " + data.message);
							}
						});
				}
			}
		},
		filters: {
			toFixed: function(value, dp) {
				return value.toFixed(dp);
			},
			toTime: function(hour) {
				// format to 12:00

				var h = Math.floor(hour);
				if (h < 10) h = "0" + h;
				var m = Math.floor((hour - h) * 60);
				if (m < 10) m = "0" + m;

				return h + ":" + m;

			}
		}
	});

	// get list of tariffs
	app.list_tariffs();
</script>