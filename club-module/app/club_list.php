<?php global $path, $session, $club_settings; ?>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<h3>Club List</h3>

<div id="app">
  <table class="table table-hover">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Users</th>
      <th>Share</th>
    </tr>
    <tr v-for="(club, name) in club_settings">
      <td>{{ club.club_id }} </td>
      <td>{{ club.name }}</td>
      <td>10</td>
      <td><i v-if="club.share" class="icon icon-ok"/></td>
    </tr>
  </table>
</div>

<script>
var club_settings = <?php echo json_encode($club_settings); ?>;

var app = new Vue({
    el: '#app',
    data: {
        club_settings: club_settings
    }
});
</script>

