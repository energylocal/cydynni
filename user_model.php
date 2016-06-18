<?php

class User
{
    private $mysqli;
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    //---------------------------------------------------------------------------------------
    // Status
    //---------------------------------------------------------------------------------------
    public function status()
    {
        if (!isset($_SESSION['userid'])) return false;
        if ($_SESSION['userid']<1) return false;
        
        $session = $_SESSION;
        if (!isset($session['admin'])) $session['admin'] = 0;
        return $session;
    }
    
    //---------------------------------------------------------------------------------------
    // Status
    //---------------------------------------------------------------------------------------
    public function userlist()
    {
        $result = $this->mysqli->query("SELECT id,email,apikey,feedid,admin FROM users");
        $users = array();
        while($row = $result->fetch_object()) $users[] = $row;
        return $users;
    }
    
    //---------------------------------------------------------------------------------------
    // User login
    //---------------------------------------------------------------------------------------
    public function register($email,$password,$apikey,$feedid)
    {
        if ($email==null) return "Email address missing";
        if ($password==null) return "Password missing";
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Invalid email";
        if (strlen($password) < 4 || strlen($password) > 250) return "Password length error";

        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows>0) return "User already exists";

        $hash = hash('sha256', $password);
        $salt = md5(uniqid(mt_rand(), true));
        $password = hash('sha256', $salt . $hash);

        $stmt = $this->mysqli->prepare("INSERT INTO users (email, password, salt, admin,apikey,feedid) VALUES (?,?,?,0,?,?)");
        $stmt->bind_param("sssss", $email, $password, $salt,$apikey,$feedid);
        if (!$stmt->execute()) {
            return "Error creating user";
        }

        // Make the first user an admin
        $userid = $this->mysqli->insert_id;
        if ($userid == 1) $this->mysqli->query("UPDATE users SET admin = 1 WHERE id = '1'");
        return $userid;
    }
    
    //---------------------------------------------------------------------------------------
    // User login
    //---------------------------------------------------------------------------------------
    public function login($email,$password)
    {        
        if ($email==null) return "Email address missing";
        if ($password==null) return "Password missing";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Invalid email";

        $stmt = $this->mysqli->prepare("SELECT id,password,salt,admin,apikey,feedid FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows!=1) return "User not found";
        
        $stmt->bind_result($id, $dbhash, $salt, $admin,$apikey,$feedid);
        $u = $stmt->fetch();
        
        $hash = hash('sha256', $salt . hash('sha256', $password));
        if ($hash!=$dbhash) return "Invalid password";
        
        session_regenerate_id();
        $_SESSION['userid'] = $id;
        $_SESSION['email'] = $email;
        $_SESSION['admin'] = $admin;
        $_SESSION['apikey'] = $apikey;
        $_SESSION['feedid'] = $feedid;
        return $_SESSION;
    }

    //---------------------------------------------------------------------------------------
    // Logout
    //---------------------------------------------------------------------------------------
    public function logout() 
    {
        session_unset();
        session_destroy();
    }
}
