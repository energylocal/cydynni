<?php
die;

require "../settings.php";

$mysqli = @new mysqli(
    $mysql['server'],
    $mysql['username'],
    $mysql['password'],
    $mysql['database']
);

require("../user_model.php");
$user = new User($mysqli);

// print $user->change_password_nocheck(1,"");

die;
/*
  print "-------------------------------------------\n";
  print "Password tool\n";
  print "-------------------------------------------\n";
  print "userid\temail\n";
  foreach ($user->userlist() as $u) {
      print $u->id."\t".$u->email."\n";
  }
  print "\n";
  $userid = (int) readline("Please enter userid: ");
  $new = trim(readline("Please enter new password: "));
  print $user->change_password_nocheck($userid, $new)."\n";
*/
