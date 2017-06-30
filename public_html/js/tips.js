$("#previous-tip").click(function(){
    tipid--;
    if (tipid<1) tipid = 7;
    $(".tip").hide();
    $(".tip[tipid="+tipid+"]").show();
});

$("#next-tip").click(function(){
    tipid++;
    if (tipid>7) tipid = 1;
    $(".tip").hide();
    $(".tip[tipid="+tipid+"]").show();
});
