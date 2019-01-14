<?php 
    global $path; 
    $version = 1;
?>

<section id="intro" class="hero-unit text-center">
    <p class="lead">Find Cydynni devices on your local network</p>
    <h2 id="ip" class="jumbotron-heading">
        <a href="<?php echo str_replace('https', 'http', $path) ?>find/ok" class="btn btn-success btn-large" target="_blank">SCAN</a>
    </h2>
    
    <p id="loader">This tool scans your local network from your web browser to discover CydYnni devices.</p>
</section>

<!--
<section id="more-details" class="text-center">
    <div class="container text-muted">
        <h4>More details:</h4>
        <p>Read more on this process on our <a href="https://github.com/emoncms/find" target="_blank">GitHub pages</a></p>
    </div>
</section>-->

<script>
    PATH = "<?php echo $path ?>";
</script>

<style>
    #intro{
        top: 2.5rem;
        left: 0;
        border-radius: 0;
        width: 100vw;
        position: absolute;
        height: 7rem;
        padding: 3rem 0;
    }
    #more-details{
        margin-top: 18rem;
    }
    .text-muted{
        color: #999;
    }
    .text-muted h4{
        color: #666;
    }
</style>
