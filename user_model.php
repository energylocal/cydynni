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
    
    private function getbyemail($email) {
        $stmt = $this->mysqli->prepare("SELECT id,email,password,salt,admin,apikey,feedid FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows!=1) return false;
        
        $stmt->bind_result($id,$email,$dbhash,$salt,$admin,$apikey,$feedid);
        $u = $stmt->fetch();
        return array(
            "id"=>$id,
            "email"=>$email,
            "dbhash"=>$dbhash,
            "salt"=>$salt,
            "admin"=>$admin,
            "apikey"=>$apikey,
            "feedid"=>$feedid
        );
    }
    
    //---------------------------------------------------------------------------------------
    // User login
    //---------------------------------------------------------------------------------------
    public function register($email,$password,$apikey,$feedid)
    {
        if ($email==null) return "Email address missing";
        if ($password==null) return "Password missing";
        if (!ctype_alnum($apikey)) return "Apikey must be alpha-numeric";
        if (!is_numeric($feedid)) return "Feed id must be numeric";
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Invalid email";
        if (strlen($password) < 4 || strlen($password) > 250) return "Password length error";
        $feedid = (int) $feedid;

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
        
        if (!$u = $this->getbyemail($email)) return "User not found";
        
        $hash = hash('sha256', $u['salt'] . hash('sha256', $password));
        if ($hash!=$u['dbhash']) return "Invalid password";
        
        session_regenerate_id();
        $_SESSION['userid'] = $u['id'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['admin'] = $u['admin'];
        $_SESSION['apikey'] = $u['apikey'];
        $_SESSION['feedid'] = $u['feedid'];
        return $_SESSION;
    }

    //---------------------------------------------------------------------------------------
    // Forgotten password
    //--------------------------------------------------------------------------------------- 
    public function passwordreset($email)
    {
        // return false;
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Email address format error";

        if (!$u = $this->getbyemail($email)) return "User not found";
        $userid = $u['id'];

        // Generate new random password
        $newpass = hash('sha256',md5(uniqid(rand(), true)));
        $newpass = substr($newpass, 0, 10);

        // Hash and salt
        $hash = hash('sha256', $newpass);
        $salt = md5(uniqid(rand(), true));
        $password = hash('sha256', $salt . $hash);

        // Save password and salt
        $this->mysqli->query("UPDATE users SET password = '$password', salt = '$salt' WHERE id = '$userid'");

        $subject = "CydYnni password reset";                    
        $message = "<p>A password reset was requested for your CydYnni account.</p><p>Your can now login with password: $newpass </p>";

        // ------------------------------------------------------------------
        // Email with swift
        // ------------------------------------------------------------------
        $have_swift = @include_once ("lib/swift/swift_required.php"); 

        if (!$have_swift){
            print "Could not find SwiftMailer - cannot proceed";
            exit;
        };

        global $smtp_email_settings;
        
        // ---------------------------------------------------------
        // Removed sequre connect $smtp_email_settings['port'],'ssl'
        // Not supported by 123reg
        // ---------------------------------------------------------
        $transport = Swift_SmtpTransport::newInstance($smtp_email_settings['host'],25)
          ->setUsername($smtp_email_settings['username'])
          ->setPassword($smtp_email_settings['password']);

        $mailer = Swift_Mailer::newInstance($transport);
        $message = Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom($smtp_email_settings['from'])
          ->setTo(array($email))
          ->setBody($message, 'text/html');
        $result = $mailer->send($message);
        // ------------------------------------------------------------------
        return "Email sent";
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
