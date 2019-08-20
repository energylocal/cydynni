<?php

class CydynniEmails
{
    private $mysqli;
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    //---------------------------------------------------------------------------------------
    // Forgotten password
    //--------------------------------------------------------------------------------------- 
    public function registeremail($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM users WHERE id = '$userid'");
        if (!$row = $result->fetch_array()) return "user not found";
        
        $username = $row['username'];
        $email = $row['email'];
        
        // Generate new random password
        $newpass = hash('sha256',md5(uniqid(rand(), true)));
        $newpass = substr($newpass, 0, 10);

        // Hash and salt
        $hash = hash('sha256', $newpass);
        $salt = md5(uniqid(rand(), true));
        $dbhash = hash('sha256', $salt . $hash);

        // Save password and salt
        $this->mysqli->query("UPDATE users SET password = '$dbhash', salt = '$salt' WHERE id = '$userid'");

        $subject = "Welcome to CydYnni, account details";   
                         
        $message = view("Modules/cydynni/emailbound.php",array(
            "title"=>"Croeso i CydYnni, Welcome to CydYnni",
            "message"=>"Gallwch fewngofnodi nawr ar <a href='https://dashboard.energylocal.org.uk'>dashboard.energylocal.org.uk</a> gyda enw: $username a chyfrinair: $newpass.<br><i>Rydym yn argymell eich bod yn newid y cyfrinair a roddir uchod i gadw eich cyfrif yn ddiogel. I newid y cyfrinair: Mewngofnodwch ar dashboard.energylocal.org.uk yna cliciwch ar icon Fy Nghyfrif</i><br><br>You can now login at <a href='https://dashboard.energylocal.org.uk'>dashboard.energylocal.org.uk</a> with username: $username and password: $newpass.<br><i>It is recommended to change the password given above to keep your account secure. To change the password: Login at dashboard.energylocal.org.uk then click on the My Account icon."
        ));

        $emailer = new Email();
        $emailer->to(array($email));
        $emailer->subject($subject);
        $emailer->body($message);
        $result = $emailer->send();
        
        if ($result['success']) {
            $welcomedate = date("d-m-Y");
            $this->mysqli->query("UPDATE cydynni SET welcomedate = '$welcomedate' WHERE `userid`='$userid'");
            return "Email sent";
        } else {
            return "Error sending email";
        }
    }
    
    
    //---------------------------------------------------------------------------------------
    // Report Email
    //--------------------------------------------------------------------------------------- 
    public function send_report_email($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM users WHERE id = '$userid'");
        if (!$row = $result->fetch_array()) return "user not found";
        
        $email = $row['email'];
        $username = $row['username'];
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp(time());
        $date->modify("last month");
        
        $month_en = $date->format("F");
        $month_cy = translate($month_en,"cy_GB");
        
        $subject = "Mae eich adroddiad Ynni Lleol Bethesda ar gyfer $month_cy yn barod. | Your Bethesda Energy Local report for $month_en is ready";  
        // $subject = "Adroddiad $month_cy, tudalen sgor newydd a cyfeiriad we | $month_en report, new score page and dashboard address!";  
                
        $c = "";
        $c .= "Mae eich adroddiad Ynni Lleol Bethesda ar gyfer $month_cy yn barod. Mewngofnodwch i weld eich adroddiad gan dilyn y ddolen isod:<br>";
        $c .= "<i>Your Bethesda Energy Local report for $month_en is now ready. Please login to view your report by following the link below:</i><br><br>";
        $c .= "<a href='https://dashboard.energylocal.org.uk/cydynni/report?apikey=".$row["apikey_read"]."&lang=cy'>Adroddiad Ynni Lleol Bethesda (Cymraeg)</a><br>";
        $c .= "<a href='https://dashboard.energylocal.org.uk/cydynni/report?apikey=".$row["apikey_read"]."&lang=en'>Bethesda Energy Local Report (English)</a><br><br>";

        // $c .= "Mae yna hefyd welliannau i dudalen 'Eich Sgôr' ar y dangosfwrdd. Archwiliwch eich sgôr, eich defnydd a'ch costau dros amser.<br>";
        // $c .= "<i>There are also improvements to the 'Your Score' page of the dashboard. Explore your score, consumption and cost breakdown over time.</i><br><br>";

        // $c .= "Mae cyfeiriad gwe y dangosfwrdd hefyd wedi newid i: <a href='https://dashboard.energylocal.org.uk'>https://dashboard.energylocal.org.uk</a><br>";
        // $c .= "<i>The dashboard web address has also changed to: <a href='https://dashboard.energylocal.org.uk'>https://dashboard.energylocal.org.uk</a></i><br><br>";
        
        $c .= "Diolch/Thankyou<br><br>Ynni Lleol Bethesda Energy Local<br><br>";
        
        //$c .= "<i style='font-size:12px'>Nodwch: Ar hyn o bryd mae cyfran y hydro sydd yn gysylltiedig â'ch cyfrif yn amcangyfrif.</i><br>";
        //$c .= "<i style='font-size:12px'>Please note that at the moment the share of hydro assigned to you is still an estimate.</i><br>";
        
        // $c .= "<i style='font-size:12px'>Questions? cwestiynau?, cysylltwch â: cydynni@energylocal.co.uk</i><br>";
        
        $message = view("Modules/cydynni/emailbound.php",array(
            "title"=>"Mae eich adroddiad Ynni Lleol Bethesda yn barod<br>Your Bethesda Energy Local report is ready",
            "message"=>$c
        ));


        $emailer = new Email();
        $emailer->to(array($email));
        $emailer->subject($subject);
        $emailer->body($message);
        $result = $emailer->send();
        
        if ($result['success']) {
            $reportdate = date("d-m-Y");
            $this->mysqli->query("UPDATE cydynni SET reportdate = '$reportdate' WHERE `userid`='$userid'");
            return "Email sent $month_en:$month_cy";
        } else {
            return "Error sending email";
        }
    }
}

