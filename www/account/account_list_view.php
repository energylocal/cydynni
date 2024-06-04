<?php global $path; ?>

<style>
.label {cursor:pointer; }
</style>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<h3>Account list</h3>

<div id="app">

    <button class="btn" style="float:right" @click="add_user"><i class="icon-plus"></i> Add new user</button>

    <div class="input-prepend">
        <span class="add-on">Select Club:</span>
        <select v-model="selected_club" @change="load">
          <option v-for="(key,id) in clubs" :value="id">{{ key }}</option>
        </select>
    </div>

    <table class="table table-striped">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>MPAN</th>
        <th>CAD Serial</th>
        <th>Octopus</th>
        <th style="width:100%">Meter Serial</th>
        <th>TMA</th>
        <th title="Connected response export">CRE</th>
        <th title="Octopus data">OCT</th>
        <th title="Connected response meter data (MQTT route)">PWR</th>
        <th tital="Estimated">EST</th>
        <th>Emails</th>
      </tr>
      <tr v-for="(user,index) in users" style="font-size:14px; cursor:pointer">
        <td><a :href="'/club/admin-switchuser?userid='+user.userid">{{ user.userid }}</a></td>
        <td @click="edit(index)">{{ user.username }}</td>
        <td @click="edit(index)">{{ user.email }}</td>
        <td @click="edit(index)">{{ user.mpan }}</td>
        <td @click="edit(index)">{{ user.cad_serial }}</td>
        <td @click="edit(index)"><span style="font-size:12px">{{ user.octopus_apikey }}</span></td>
        <td @click="edit(index)">{{ user.meter_serial }}</td>

        <td v-for="source in data_status[index]">
          <span v-if="source.days">
            <span class="label label-success" v-if="source.updated<7" :title="source.days | toFixed(0) + ' days of data'" @click="graph(source.feedid)" >
            {{ source.updated | toFixed(0) }}d ago</span>
            <span class="label label-warning" v-else-if="source.updated<31" :title="source.days | toFixed(0) + ' days of data'" @click="graph(source.feedid)" >
            {{ source.updated | toFixed(0) }}d ago</span>
            <span class="label label-important" v-else :title="source.days | toFixed(0) + ' days of data'" @click="graph(source.feedid)">
            {{ source.updated | toFixed(0) }}d ago</span>
          </span>
          <span v-else class="label">no data</span>
        </td>
        <td v-if="!data_status[index]"><span class="label">loading</span></td>
        <td v-if="!data_status[index]"><span class="label">loading</span></td>
        <td v-if="!data_status[index]"><span class="label">loading</span></td>
        <td v-if="!data_status[index]"><span class="label">loading</span></td>
        <td v-if="!data_status[index]"><span class="label">loading</span></td>
        <td @click="edit(index)">
          <span :title="'Welcome email sent:\n'+user.welcomedate"><i class="icon-ok-circle" v-if="user.welcomedate!=0"></i></span>
          <span :title="'Report email sent:\n'+user.reportdate"><i class="icon-book" v-if="user.reportdate!=0"></i></span>        
        </td>
      </tr>
    </table>

    <div id="editUserModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button type="button" class="close" @click="edit_close">×</button>
            <h3 id="editUserModalLabel"><?php echo _('Edit User'); ?></h3>
        </div>
        <div class="modal-body" v-if="selected_user!==false">
        
        
        <div class="row-fluid">
          <div class="span7">
            <label>Username</label>
            <input type="text" v-model="users[selected_user].username" style="width:260px" />
            
            <label v-if="new_user!==false">Password</label>
            <input v-if="new_user!==false" type="password" autocomplete="new-password" v-model="new_user_password" style="width:260px" />
            
            <label>Email</label>
            <input type="text" v-model="users[selected_user].email" style="width:260px" />

            <label>MPAN</label>
            <input type="text" v-model="users[selected_user].mpan" style="width:260px" />
            
            <label>CAD Serial</label>
            <input type="text" v-model="users[selected_user].cad_serial" style="width:260px" />

            <label>Octopus API Key</label>
            <input type="text" v-model="users[selected_user].octopus_apikey" style="width:260px" />

            <label>Meter Serial</label>
            <input type="text" v-model="users[selected_user].meter_serial" style="width:260px" />
            <div v-if="users[selected_user].userid<0">
                <label>User tariff</label>
                <select v-model="selectedTariff" @change="resetTariffTimestamp" style="width:274px">
                    <option v-for="tariff in tariffs" :value="tariff.id">{{tariff.name}} - {{tariff.active_users}}/{{tariff.total_club_users_count}} users | Last assigned on {{tariff.last_assigned}}</option>
                </select>
                <label>User tariff start timestamp</label>
                <select v-model="selectedTariffTimestamp" style="width:274px">
                <option v-for="timestamp in selectedTariffDistinctStarts" :value="timestamp">{{timestamp}}</option>
                </select>
            </div>
          </div>
          <div class="span5">
            <div v-if="users[selected_user].userid>0">
              <p>Welcome email <div class="input-append"><span class="add-on">{{ users[selected_user].welcomedate }}</span>
              <button class="btn" @click="send_welcome">Send</button></div></p>

              <p>Report email <div class="input-append"><span class="add-on">{{ users[selected_user].reportdate }}</span>
              <button class="btn" @click="send_report">Send</button></div></p>
            </div>
          </div>
        </div>
        
        </div>
        <div class="modal-footer">
            <button class="btn" @click="edit_close"><?php echo _('Cancel'); ?></button>
            <button class="btn btn-primary" @click="save"><?php echo _('Save'); ?></button>
        </div>
    </div>

</div>


<script>

var selected_club = <?php echo $clubid; ?>;
if (!selected_club) selected_club = localStorage.getItem('selected_club');
if (selected_club==null) selected_club = 1;

var users = {}
var original = {}
var data_status = {}
var tariffs = {}

var clubs = <?php echo json_encode($clubs); ?>;

var app = new Vue({
    el: '#app',
    data: {
        clubs: clubs,
        selected_club: selected_club,
        users:users,
        data_status:data_status,
        selected_user: false,
        new_user: false,
        new_user_password: "",
        tariffs: tariffs,
        selectedTariff: null,
        selectedTariffTimestamp: null,
    },
    computed: {
        selectedTariffDistinctStarts() {
            if (this.selectedTariff && this.tariffs[this.selectedTariff]) {
                return this.tariffs[this.selectedTariff].distinct_tariff_starts;
            }
            return [];
        }
    },
    methods: {
        graph: function(feedid) {
            window.location = "/graph/"+feedid
        },
        add_user: function() {
            let latestTariff = null;
            for (let key in this.tariffs) {
                if (this.tariffs.hasOwnProperty(key)) {
                    let currentTariff = this.tariffs[key];
                    if (!latestTariff || currentTariff.last_assigned_unix > latestTariff.last_assigned_unix) {
                        latestTariff = currentTariff;
                    }
                }
            }
            this.latestTariff = latestTariff
            this.selectedTariff = this.latestTariff['id']
            this.selectedTariffTimestamp = this.latestTariff['last_assigned_unix']

            this.users.push({
                userid:-1,
                username:"",
                email:"",
                clubs_id:app.selected_club,         
                mpan:"",
                cad_serial:"",
                octopus_apikey:"",       
                meter_serial:"",
                welcomedate:0,
                reportdate:0,
                admin:0
            });
            this.selected_user = this.users.length-1;
            this.new_user = this.selected_user;
            $("#editUserModalLabel").html("Add user");
            $("#editUserModal").modal("show");
        },
        edit: function(index){
            this.selected_user = index
            $("#editUserModalLabel").html("Edit user");
            $("#editUserModal").modal("show");            
        },
        edit_close: function() {
            $("#editUserModal").modal("hide");
            if (this.new_user!==false) {
                this.selected_user = false;
                this.new_user = false;
                this.users.pop()
            }         
        },
        save: function() {
            console.log("new user: "+this.new_user);
            // if this doesn't concern a new user - edit existing user
            if (this.new_user===false) {
                var changed = {};
                // Find changed properties
                for (var z in this.users[this.selected_user]) {
                    if (this.users[this.selected_user][z]!=original[this.selected_user][z]) {
                        changed[z] = this.users[this.selected_user][z];
                    }
                }
                update_user(this.users[this.selected_user].userid,changed);
            // if this does concern a new user - create new user
            } else {
                add_user(this.users[this.selected_user], this.new_user_password, function (new_userid) {
                    add_user_tariff(new_userid, this.selectedTariff)
                }.bind(this));
            }
        },
        send_welcome: function() {
            $.ajax({
                url: path+"club/admin-registeremail",
                data: "userid="+this.users[this.selected_user].userid,
                dataType: 'text',
                success: function(result) {
                    alert(result)
                }
            });        
        },
        send_report: function() {
            $.ajax({
                url: path+"club/admin-sendreport",
                data: "userid="+this.users[this.selected_user].userid,
                dataType: 'text',
                success: function(result) {
                    alert(result)
                }
            });   
        },
        resetTariffTimestamp: function() {
            this.selectedTariffTimestamp = this.tariffs[this.selectedTariff]['last_assigned_unix'] || null;
        }
    },
    filters: {
        toFixed: function(val,dp) {
            return val.toFixed(dp)
        }
    }
});

load();
function load() {

    localStorage.setItem('selected_club',app.selected_club);

    app.data_status = {};
    $.ajax({
        url: path+"account/list.json?clubid="+app.selected_club,
        dataType: 'json',
        async:true,
        success: function(result) {
            app.users = result;
            original = JSON.parse(JSON.stringify(result));
            data_status = {}
        }
    });

    $.ajax({
        url: path+"tariff/list.json?clubid="+app.selected_club,
        dataType: 'json',
        async:true,
        success: function(result) {
            tariffs_dict = result.reduce((dict, tariff) => {
                dict[tariff.id] = tariff;
                return dict;
            }, {});
            app.tariffs = tariffs_dict;
        }
    });
    
    setTimeout(function(){
    $.ajax({
        url: path+"club/admin-users-data-status?club_id="+app.selected_club,
        dataType: 'json',
        async:true,
        success: function(result) {
            app.data_status = result;
        }
    });
    },100);
}

function add_user(user,password,callback) {
    user.password = password;
    $.ajax({
        type: 'POST',
        url: path+"account/add",
        data: "user="+JSON.stringify(user),
        dataType: 'json',
        success: function(result) {
            if (result.success) {
                $("#editUserModal").modal("hide");
                app.users[app.selected_user].userid = result.userid;
                callback(result.userid);
            }
        }
    });
}

function add_user_tariff(userid, tariffid) {
    $.ajax({
        type: 'GET',
        url: path + "tariff/user/set",
        data: {
            userid: userid,
            tariffid: tariffid
        },
        dataType: 'json',
        success: function(result) {
            alert("User created, user tariff created");
            if (result.success) {
                $("#editUserModal").modal("hide");
                app.users[app.selected_user].userid = result.userid;
            }
        },
        error: function(xhr, status, error) {
            console.error("Ajax request failed:", error);
            console.log(xhr.responseText); // log the raw response for debugging
        }
    });
}


function update_user(userid,changed) {
    $.ajax({
        type: 'POST',
        url: path+"account/update?userid="+userid,
        data: "data="+JSON.stringify(changed),
        dataType: 'json',
        async:true,
        success: function(result) {
            alert(result.message)
            if (result.success) {
                $("#editUserModal").modal("hide");
                // apply changes back to original copy
                for (var z in changed) {
                    original[app.selected_user][z] = changed[z] 
                }
            }
        }
    });
}
</script>


