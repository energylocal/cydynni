<?php 
    global $path; 
    $version = 1;
?>

<section id="intro" class="hero-unit text-center">
    <p>Find CydYnni devices on your local network</p>
    <h2 id="ip" class="jumbotron-heading">Getting your ip</h2>
    <p id="loader" class="lead">Searching...</p>
</section>
  
<section id="list-section" class="text-center">
    <div class="container">

        <div id="not-found" class="d-none" style="margin-bottom:3rem">
            <h4>No devices found on your local network!</h4>
            <p>Ensure the device is online, then click reload:</p>
            <a href="ok" class="btn btn-large btn-success">Reload</a>
        </div>


        <div id="found" class="d-none">
            <h4>Found devices:</h4>
        </div>
        <ul id="list" class="nav nav-tabs nav-stacked text-left" style="display: inline-block"><ul>
        <template id="list-group-item">
            <li><a href="#" style="color: #666!important; text-align:left"><img><span></span></a></li>
        </template>

    </div>
</section>
<script>
    PATH = "<?php echo $path ?>";
</script>
<script src="<?php echo $path; ?>Modules/find/js/find.js?v=<?php echo $version; ?>"></script>

<style>
    
    #list li a{
        padding: 1em 2em;
    }
    #list li a img{
        height: 1.3em;
        padding-right: .3em;
    }
    .d-none{
        display: none!important;
    }
</style>
