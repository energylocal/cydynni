<?php

class User
{
    private $mysqli;
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    public function status()
    {
        if (!isset($_SESSION['userid'])) return false;
        if ($_SESSION['userid']<1) return false;
        
        $session = $_SESSION;
        if (!isset($session['admin'])) $session['admin'] = 0;
        return $session;
    }

    private function getbyusername($username) {
        $stmt = $this->mysqli->prepare("SELECT id,username,email,password,salt,admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows!=1) return false;
        
        $stmt->bind_result($id,$username,$email,$dbhash,$salt,$admin);
        $u = $stmt->fetch();
        return array(
            "id"=>$id,
            "username"=>$username,
            "email"=>$email,
            "dbhash"=>$dbhash,
            "salt"=>$salt,
            "admin"=>$admin
        );
    }
       
    private function getbyemail($email) {
        $stmt = $this->mysqli->prepare("SELECT id,username,email,password,salt,admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows!=1) return false;
        
        $stmt->bind_result($id,$username,$email,$dbhash,$salt,$admin);
        $u = $stmt->fetch();
        return array(
            "id"=>$id,
            "username"=>$username,
            "email"=>$email,
            "dbhash"=>$dbhash,
            "salt"=>$salt,
            "admin"=>$admin
        );
    }
    
    public function getbyid($id) {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT email FROM users WHERE id='$id'");
        $row = $result->fetch_array();
        
        return array(
            "email"=>$row["email"]
        );
    }
    
    public function get_id($username)
    {
        if (!ctype_alnum($username)) return false;

        $result = $this->mysqli->query("SELECT id FROM users WHERE username = '$username';");
        $row = $result->fetch_array();
        return $row['id'];
    }
    
    public function apikey_session($apikey_in)
    {
        $apikey_in = $this->mysqli->real_escape_string($apikey_in);
        $session = array();

        $result = $this->mysqli->query("SELECT id, username, email FROM users WHERE apikey_write='$apikey_in'");
        if ($result->num_rows == 1)
        {
            $row = $result->fetch_array();
            if ($row['id'] != 0)
            {
                $session['userid'] = $row['id'];
                $session['read'] = 1;
                $session['write'] = 1;
                $session['admin'] = 0;
                $session['lang'] = "en"; // API access is always in english
                $session['email'] = $row['email'];
                $session['username'] = $row['username'];
            }
        }
        else
        {
            $result = $this->mysqli->query("SELECT id, username, email FROM users WHERE apikey_read='$apikey_in'");
            if ($result->num_rows == 1)
            {
                $row = $result->fetch_array();
                if ($row['id'] != 0)
                {
                    $session['userid'] = $row['id'];
                    $session['read'] = 1;
                    $session['write'] = 0;
                    $session['admin'] = 0;
                    $session['lang'] = "en";  // API access is always in english
                    $session['email'] = $row['email'];
                    $session['username'] = $row['username'];
                }
            }
        }

        //----------------------------------------------------
        return $session;
    }
    
    //---------------------------------------------------------------------------------------
    // User login
    //---------------------------------------------------------------------------------------    
    public function login($username,$password)
    {        
        if ($username==null) return array("success"=>false, "message"=>"Username missing");
        if ($password==null) return array("success"=>false, "message"=>"Password missing");
        
        if (!$u = $this->getbyusername($username)) return array("success"=>false, "message"=>"User not found");
        
        $hash = hash('sha256', $u['salt'] . hash('sha256', $password));
        if ($hash!=$u['dbhash']) return array("success"=>false, "message"=>"Invalid password");
        
        session_regenerate_id();
        $_SESSION['userid'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['read'] = 1;
        $_SESSION['write'] = 1;
        $_SESSION['admin'] = $u['admin'];
        
        $result = $_SESSION;
        $result["success"] = true;
        return $result;
    }
    
    public function register($username, $password, $email)
    {
        // Input validation, sanitisation and error reporting
        if (!$username || !$password || !$email) return array('success'=>false, 'message'=>_("Missing username, password or email parameter"));

        if (!ctype_alnum($username)) return array('success'=>false, 'message'=>_("Username must only contain a-z and 0-9 characters"));
        $username = $this->mysqli->real_escape_string($username);
        // $password = $this->mysqli->real_escape_string($password);

        if ($this->get_id($username) != 0) return array('success'=>false, 'message'=>_("Username already exists"));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return array('success'=>false, 'message'=>_("Email address format error"));

        if (strlen($username) < 3 || strlen($username) > 30) return array('success'=>false, 'message'=>_("Username length error"));
        if (strlen($password) < 4 || strlen($password) > 250) return array('success'=>false, 'message'=>_("Password length error"));

        // If we got here the username, password and email should all be valid

        $hash = hash('sha256', $password);
        $salt = md5(uniqid(mt_rand(), true));
        $password = hash('sha256', $salt . $hash);

        $apikey_write = md5(uniqid(mt_rand(), true));
        $apikey_read = md5(uniqid(mt_rand(), true));

        $stmt = $this->mysqli->prepare("INSERT INTO users ( username, password, email, salt ,apikey_read, apikey_write, admin ) VALUES (?,?,?,?,?,?,0)");
        $stmt->bind_param("ssssss", $username, $password, $email, $salt, $apikey_read, $apikey_write);
        if (!$stmt->execute()) {
            return array('success'=>false, 'message'=>_("Error creating user"));
        }

        // Make the first user an admin
        $userid = $this->mysqli->insert_id;
        if ($userid == 1) $this->mysqli->query("UPDATE users SET admin = 1 WHERE id = '1'");

        return array('success'=>true, 'userid'=>$userid, 'apikey_read'=>$apikey_read, 'apikey_write'=>$apikey_write);
    }

    //---------------------------------------------------------------------------------------
    // Change password
    //--------------------------------------------------------------------------------------- 
    public function change_password_nocheck($userid, $new)
    {
        $userid = (int) $userid;

        if (strlen($new) < 4 || strlen($new) > 250) return "New password length error";

        // 2) Save new password
        $hash = hash('sha256', $new);
        $salt = md5(uniqid(rand(), true));
        $newdbhash = hash('sha256', $salt . $hash);
        $this->mysqli->query("UPDATE users SET password = '$newdbhash', salt = '$salt' WHERE id = '$userid'");
        return "Password changed";
    }
    
    public function change_email($userid, $email) 
    {
        $userid = (int) $userid;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Invalid email";
        $this->mysqli->query("UPDATE users SET email = '$email' WHERE id = '$userid'");
        return "Email updated";
    }

    //---------------------------------------------------------------------------------------
    // Change password
    //--------------------------------------------------------------------------------------- 
    public function change_password($userid, $old, $new)
    {
        $userid = intval($userid);

        if (strlen($old) < 4 || strlen($old) > 250) return "Old password length error";
        if (strlen($new) < 4 || strlen($new) > 250) return "New password length error";

        // 1) check that old password is correct
        $result = $this->mysqli->query("SELECT password, salt FROM users WHERE id = '$userid'");
        $row = $result->fetch_object();
        $hash = hash('sha256', $row->salt . hash('sha256', $old));

        if ($hash == $row->password)
        {
            // 2) Save new password
            $hash = hash('sha256', $new);
            $salt = md5(uniqid(rand(), true));
            $newdbhash = hash('sha256', $salt . $hash);
            $this->mysqli->query("UPDATE users SET password = '$newdbhash', salt = '$salt' WHERE id = '$userid'");
            return "Password changed";
        }
        else
        {
            return "Old password incorect";
        }
    }

    public function get_number_of_users()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) FROM users");
        $row = $result->fetch_row();
        return $row[0];
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
