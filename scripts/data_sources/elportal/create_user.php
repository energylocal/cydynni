<?php


function create_user($club_id,$email,$mpan)
{
    global $mysqli,$user;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (!$username = create_username($email)) return false;
    
    print "CREATING USER: $username, $email, $mpan\n";

    // gen new password
    $password = hash('sha256',md5(uniqid(rand(), true)));
    $password = substr($password, 0, 10);

    // hash and salt
    $hash = hash('sha256', $password);
    $salt = md5(uniqid(mt_rand(), true));
    $password = hash('sha256', $salt . $hash);

    $result = $user->register($username, $password, $email);
    if ($result["success"]) {
        $userid = $result["userid"];
        $mysqli->query("INSERT INTO cydynni (clubs_id,userid,mpan,token,premisestoken,welcomedate,reportdate) VALUES ('$club_id','$userid','$mpan','','',0,0)");
        $result = remoteaccess_userlink_existing($mysqli,$userid);
    }
}

function create_username($email) {
    $username = $email;
    $username = str_replace(".com","",$username);
    $username = str_replace(".co.uk","",$username);
    $username = str_replace(".org","",$username);
    $username = str_replace(".ac.uk","",$username);
    $username = str_replace(".net","",$username);
    $username = str_replace(".cymru","",$username);
    $username = str_replace("@sky","",$username);
    $username = str_replace("@hotmail","",$username);
    $username = str_replace("@gmail","",$username);
    $username = str_replace("@btinternet","",$username);
    $username = str_replace("@yahoo","",$username);
    $username = str_replace("@bangor","",$username);
    $username = str_replace("@talktalk","",$username);
    $username = str_replace("@ogwen","",$username);
    $username = str_replace("@aol","",$username);
    $username = str_replace("@outlook","",$username);
    $username = preg_replace('/[^\p{N}\p{L}]/u','',$username);

    if (!ctype_alnum($username)) {
        return false;
    } else if ($username=="info" || $username=="admin") {
        return false;
    } else if (strlen($username)<4) {
        return false;
    }
    
    return $username;
}

