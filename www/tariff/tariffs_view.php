<?php
  defined('EMONCMS_EXEC') or die('Restricted access');

  global $path;
  $v = 1;
?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.5.1/axios.min.js"></script>

<div id="app">

	<h3>Tariffs</h3>

  <p>
  <div class="input-prepend">
    <span class="add-on">Select Club:</span>
    <select v-model="selected_club" @change="list_tariffs">
      <option v-for="(key,id) in clubs" :value="id">{{ key }}</option>
    </select>
  </div>
  </p>


	<div class="input-prepend input-append">
		<span class="add-on">New tariff</span>
		<input type="text" value="" v-model="new_tariff_name">
		<button class="btn" style="float: right;" @click="add_tariff"><i class="icon-plus"></i> Add</button>
	</div>

<div id="assign-user-tariffs-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="assignUserModalLabel" aria-hidden="true">
  <div class="modal-header">
    <h3 id="assignUserModalLabel">Assign user tariffs</h3>
  </div>
  <div class="modal-body">
    <p>Assign tariff "{{ tariffToAssign.name }}" to all users in the club?</p>
    Start time: <input type="text" id="assignTariffDatePicker" v-model="assignTariffStart">
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true" @click="clearTariffToAssign()">Cancel</button>
    <button class="btn btn-primary" @click="assignAllUserTariffs()">Assign to all</button>
  </div>
</div>

	<table class="table table-striped">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Standing Charge</th>
			<th>Created</th>
			<th>First assigned</th>
			<th>Last used</th>
			<th>Active users</th>
			<th>Actions</th>
		</tr>
		<tr v-for="(tariff,index) in tariffs" :key="tariff.id">
			<td>{{tariff.id}}</td>
			<td>{{ tariff.name }}</td>
			<td>{{ tariff.standing_charge }}</td>
			<td>{{tariff.created}}</th>
			<td>{{tariff.first_assigned}}</th>
			<td>{{tariff.last_assigned}}</td>
			<td :class="getClass(tariff)">({{tariff.active_users}}/{{tariff.total_club_users_count}})</td>
			<td>
				<button class="btn" @click="delete_tariff(tariff.id)"><i class="icon-trash"></i></button>
				<button class="btn" @click="edit_tariff(index)"><i class="icon-pencil"></i></button>
				<button class="btn" @click="clone_tariff(tariff.id)"><i class="icon-share"></i></button>
				<button class="btn" @click="showAssignTariffModal(tariff)"><i class="icon-user"></i></button>
			</td>
		</tr>
	</table>

	<div v-if="selected_tariff!==false">

		<div class="input-prepend">
			<span class="add-on">Tariff name</span>
			<input type="text" v-model="edit_tariff_name_field">
			<span class="add-on">Standing charge</span>
			<input type="text" v-model="edit_standing_charge_field">
      <button class="btn" @click="update_tariff()"><i class="icon-ok"></i></button>
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
  var selected_club = <?php echo $clubid; ?>;
  if (!selected_club) selected_club = localStorage.getItem('selected_club');
  if (selected_club==null) selected_club = 1;
  
  var clubs = <?php echo json_encode($clubs); ?>;

	app = new Vue({
		el: '#app',
		data: {
			groups: false,
			selected_group: 0,
			tariffs: [],
			new_tariff_name: "",
			selected_tariff: false,
			tariff_periods: [],
			edit_period_index: false,
			selected_club: selected_club,
      clubs: clubs,
      edit_tariff_name_field: "",
      edit_standing_charge_field: 0,
      tariffToAssign: false,
      assignTariffStart: 0
		},
		methods: {
			add_tariff: function() {
				// url encode post body
				// club/tariff/create
				$.post(path + "tariff/create", {
						club: app.selected_club,
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
			  localStorage.setItem('selected_club',app.selected_club);
			  
				$.get(path + "tariff/list.json?clubid=" + app.selected_club)
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
			getClass: function(tariff) {
				return {
					'text-green': tariff.active_users == tariff.total_club_users_count,
					'text-red': tariff.active_users < tariff.total_club_users_count,
					'text-orange': tariff.active_users > tariff.total_club_users_count
				}
			},
			edit_tariff: function(index) {
				app.selected_tariff = index;
        this.edit_tariff_name_field = this.tariffs[index].name;
        this.edit_standing_charge_field = this.tariffs[index].standing_charge;

				// get tariff periods
				$.get(path + "tariff/periods", {
						id: app.tariffs[index].id
					})
					.done(function(data) {
						app.tariff_periods = data;
					});
			},
      clone_tariff: function(id) {
        $.post(path + "tariff/clone", {
          tariff_id: id
        })
          .done(function(data) {
            if (data.success) {
              app.list_tariffs();
            } else {
              alert("Error: " + data.message);
            }
          });
      },
      update_tariff: function() {
				$.post(path + "tariff/update", {
            tariff_id: app.tariffs[app.selected_tariff].id,
						name: app.edit_tariff_name_field,
						standing_charge: app.edit_standing_charge_field
					})
					.done(function(data) {
						if (data.success) {
							app.list_tariffs();
						} else {
							alert("Error: " + data.message);
						}
					});
      },
      assignAllUserTariffs: function() {
        $.post(path + "tariff/assign_all_users", {
            tariff_id: app.tariffToAssign.id,
            start: app.assignTariffStart
					})
					.done(function(data) {
						if (data.success) {
							app.list_tariffs();
						} else {
							alert("Error: " + data.message);
						}
            app.clearTariffToAssign();
					});
      },
      showAssignTariffModal: function(tariff) {
        this.tariffToAssign = tariff;
        $('#assign-user-tariffs-modal').modal({})
      },
      clearTariffToAssign: function() {
        $('#assign-user-tariffs-modal').modal('hide')
        this.tariffToAssign = false;
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

<style>
.text-green {
  color: green;
}
.text-red {
  color: red;
}
.text-orange {
  color: orange;
}
</style>
