<?php defined('EMONCMS_EXEC') or die('Restricted access'); ?>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<h3>EnergyLocal Clubs</h3>

<div id="app">

    <div class="alert alert-warning" v-if="clubs.length == 0">
        <p><b>Hello!</b></p><p>Looks like no clubs have been created yet. Create your first club to get started.</p><button class="btn">Create a new club</button>.
    </div>

    <table class="table table-striped">
        <tr v-for="(club,index) in clubs">
            <td><a :href="'<?php echo $path; ?>account/list?clubid='+club.id">{{ club.name }}</a></td>
            <td>{{ club.created }}</td>
            <td><button class="btn btn-mini btn-danger" @click="remove(index)">Delete</button></td>
        </tr>
    </table>

    <h3>New Club</h3>
    <div id="club_editor">
        <div class="input-prepend input-append">
            <span class="add-on">Club name</span>
            <input type="text" value="" v-model="edit.club_name">
            <button class="btn" @click="create">Create</button>
        </div>
    </div>
    <div class="alert alert-error hide" id="error" style="width:300px"></div>
</div>

<script>
    // Fetch clubs from API
    var clubs = [];
    reload_list();

    var app = new Vue({
        el: '#app',
        data: {
            clubs: clubs,
            edit: {
                club_name: ''
            }
        },
        methods: {
            create: function() {
                $("#error").hide();
                $.get('<?php echo $path; ?>club/create.json', {name: this.edit.club_name}, function(data) {
                    if (data.success) {
                        reload_list();
                    } else {
                        $("#error").html("<b>Error:</b> "+data.message).show();
                    }
                });
            },
            remove: function(index) {
                var id = this.clubs[index].id;
                var club_name = this.clubs[index].name;
                
                // confirm
                if (!confirm("Are you sure you want to delete club: "+club_name)) return;

                $("#error").hide();
                $.get('<?php echo $path; ?>club/delete.json', {id: id}, function(data) {
                    if (data.success) {
                        reload_list();
                    } else {
                        $("#error").html("<b>Error:</b> "+data.message).show();
                    }
                });
            }
        }
    });

    function reload_list() {
        $.getJSON('<?php echo $path; ?>club/list.json', function(data) {
            app.clubs = data;
        });
    }
</script>
