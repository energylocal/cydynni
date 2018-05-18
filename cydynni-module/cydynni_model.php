<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Cydynni {

    private $mysqli;
    private $redis;

    public function __construct($mysqli,$redis) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
    }

    public function getClubs($slug = "") {
        if(empty($slug)){
            $sql = "SELECT * FROM cydynni_clubs ORDER BY name ASC";
        }else{
            $sql = sprintf('SELECT * FROM cydynni_clubs WHERE slug = "%s"', $slug);
        }
        $result = $this->mysqli->query($sql);
        $clubs = array();
        while($row = $result->fetch_object()) {
            $clubs[] = $row;
        }
        return count($clubs)==1 ? $clubs[0] : $clubs;
    }
    public function saveClub($data, $club_id=false) {
        //get the values and their types
        $this->addToBindArray($fields, $data, 'name');
        $this->addToBindArray($fields, $data, 'slug');
        $this->addToBindArray($fields, $data, 'color');
        $this->addToBindArray($fields, $data, 'generator');
        $this->addToBindArray($fields, $data, 'root_token');
        $this->addToBindArray($fields, $data, 'languages[]');
        $this->addToBindArray($fields, $data, 'api_prefix', 'i');
        $this->addToBindArray($fields, $data, 'consumption_feed', 'i');
        $this->addToBindArray($fields, $data, 'generation_feed', 'i');
        //extract the parts for each name ,value and type
        foreach($fields as $key=>$v){
            $names[] = $key;
            $values[] = $v['value'];
            $types[] = $v['type'];
        }
        //create the update or insert statements
        //bind the values to the prepered statements
        if(empty($club_id)){
            //INSERT
            $statement = $this->mysqli->prepare("INSERT INTO cydynni_clubs (".implode(",",$names).") VALUES (".implode(",", array_fill(0, count($fields),'?')) .")");
            $bind_params = array_merge(array(implode($types)), $values);
            $bound = call_user_func_array(array($statement,'bind_param'), $this->arrayAsRefs($bind_params));
        }else{
            //UPDATE
            $statement = $this->mysqli->prepare("UPDATE cydynni_clubs SET ".implode("=? ,",$names)."=? WHERE id = ?");
            $bind_params = array_merge(array(implode($types) . 'i'), array_merge($values, array($club_id)));
            $bound = call_user_func_array(array($statement,'bind_param'), $this->arrayAsRefs($bind_params));
        }
        
        $success = $statement->execute();
        //$club_id = empty($club_id) ? $this->getClubById($this->mysqli->insert_id) : $club_id; 
        $error = !$success ? $statement->error : false;
        
        $statement->close();

        return array('success'=>$success, 'params'=>$data, 'data'=>array(array('club_id'=>$club_id),array('fields'=>$fields)), 'bound'=>$bound, 'error'=>$error);
    }

    public function getClubById($id = "") {
        $sql = "SELECT * FROM cydynni_clubs WHERE id = ".$id;
        $clubs = array();
        if($result = $this->mysqli->query($sql)){
            while($row = $result->fetch_object()) {
                $clubs[] = $row;
            }
        }
        if(!empty($clubs)){
            return count($clubs)>1 ? $clubs : $clubs[0];
        }else{
            return false;
        }
    }




    public function getUser($user_id = "") {
        $user_id = (int) $user_id;
        if(!empty($user_id)){
            // Include data from cydynni table here too
            $sql = "SELECT users.id,users.username,users.email,users.apikey_read,users.admin,cydynni.*
            FROM users
            LEFT JOIN cydynni ON users.id = cydynni.userid
            WHERE users.id = $user_id
            ORDER BY users.id ASC";

            $result = $this->mysqli->query($sql);
            $users = array();
            while($user = $result->fetch_object()) {
                $userid = $user->id;
                $user->hits = $this->redis->get("   userhits:$userid");
                $user->testdata = json_decode($this->redis->get("user:summary:lastday:$userid"));
                $user->club = $this->getClubById($user->clubs_id);
                $users[] = $user;
            }
            return $users[0];
        }
    }

    public function getUsers($club_id = "") {
        $club_id = (int) $club_id;
        
        //show all clubs if no club_id passed
        $where = $club_id>0 ? "WHERE cydynni.clubs_id = $club_id" : "";

        // Include data from cydynni table here too
        $sql = "SELECT users.id,users.username,users.email,users.apikey_read,users.admin,cydynni.*
         FROM users
         LEFT JOIN cydynni ON users.id = cydynni.userid
         $where
         ORDER BY users.id ASC";

        $result = $this->mysqli->query($sql);
        $users = array();
        while($user = $result->fetch_object()) {
            $userid = $user->id;
            $user->hits = $this->redis->get("userhits:$userid");
            $user->testdata = json_decode($this->redis->get("user:summary:lastday:$userid"));
            $user->club = $this->getClubById($user->clubs_id);
            $users[] = $user;
        }
        return $users;
    }

    public function saveUser($data, $user_id=false) {
        //get the values and their types
        $fields['megni'][] = array();
        $fields['users'][] = array();
        
        //get the values and their types (not added to $fields list if empty)
        $this->addToBindArray($fields['megni'][], $data, 'mpan');
        $this->addToBindArray($fields['megni'][], $data, 'token');
        $this->addToBindArray($fields['megni'][], $data, 'premisestoken');
        $this->addToBindArray($fields['megni'][], $data, 'welcomedate');
        $this->addToBindArray($fields['megni'][], $data, 'reportdate');
        $this->addToBindArray($fields['megni'][], $data, 'clubs_id', 'i');//int type

        // $this->addToBindArray($fields['users'][], $data, 'username');
        // $this->addToBindArray($fields['users'][], $data, 'email');
        // $this->addToBindArray($fields['users'][], $data, 'apikey_read');
        // $this->addToBindArray($fields['users'][], $data, 'admin', 'i');//int type
        
        //extract the parts for each name, value and type (for each table)
        foreach($fields as $table_name=>$field){
            foreach($field as $key=>$value){
                $values[$table_name][] = $key;
                $values[$table_name][] = $value['value'];
                $types[$table_name][]  = $value['type'];
            }
        }

        //create the update or insert statements
        //bind the values to the prepered statements
        if(empty($user_id)){
            //INSERT
            $statement['megni'] = $this->mysqli->prepare("INSERT INTO cydynni (".implode(",",$names['megni']).") VALUES (".implode(",", array_fill(0, count($fields['megni']),'?')) .")");
            $bind_params['megni'] = array_merge(array(implode($types['megni'])), $values['megni']);
            $bound['megni'] = call_user_func_array(array($statement,'bind_param'), $this->arrayAsRefs($bind_params));

            $statement['users'] = $this->mysqli->prepare("INSERT INTO users (".implode(",",$names['users']).") VALUES (".implode(",", array_fill(0, count($fields['users']),'?')) .")");
            $bind_params['users'] = array_merge(array(implode($types['users'])), $values['users']);
            $bound['users'] = call_user_func_array(array($statement,'bind_param'), $this->arrayAsRefs($bind_params));
        }else{
            //UPDATE
            $statement['megni'] = $this->mysqli->prepare("UPDATE cydynni SET ".implode("=? ,",$names['megni'])."=? WHERE userid = ?");
            $bind_params['megni'] = array_merge(array(implode($types['megni']) . 'i'), array_merge($values['megni'], array($user_id)));
            $bound['megni'] = call_user_func_array(array($statement['megni'],'bind_param'), $this->arrayAsRefs($bind_params['megni']));

            $statement['users'] = $this->mysqli->prepare("UPDATE `emoncms` SET ".implode("=? ,",$names['users'])."=? WHERE `id`=?");
            $bind_params['users'] = array_merge(array(implode($types['users']) . 'i'), array_merge($values['users'], array($user_id)));
            $bound['users'] = call_user_func_array(array($statement['users'],'bind_param'), $this->arrayAsRefs($bind_params['users']));
        }
        
        $success['megni'] = $statement['megni']->execute();
        $insert_id['megni'] = $this->mysqli->insert_id; 
        $affected_rows['megni'] = $this->mysqli->affected_rows;
        $error['megni'] = !$success['megni'] ? $statement['megni']->error : false;
        $statement['megni']->close();
        
        $success['users'] = $statement['users']->execute();
        $insert_id['users'] = $this->mysqli->insert_id; 
        $affected_rows['users'] = $this->mysqli->affected_rows;
        $error['users'] = !$success['users'] ? $statement['users']->error : false;
        $statement['users']->close();
    
        return array(
            'success'=>$success['megni'] && $uccess['users'], 
            'params'=>$data, 
            'fields'=>$fields,
            'bound'=>$bound['megni'] && $bound['users'], 
            'user_id'=> empty($user_id) ? $insert_id['megni'] : $user_id,
            'affected_rows'=>$affected_rows['megni'] + $affected_rows['users'], 
            'error'=>$error['megni'].$error['users']
        );
    }










    public function set($userid="") {

        $userid = (int) $userid;

        return 'set';
    }

    /**
     * return given array as array of referrences not values
     */
    private function arrayAsRefs($arr) {
        $refs = array(); 
        foreach($arr as $key => $value) 
            $refs[$key] = &$arr[$key]; 
        return $refs; 
    }
    /**
     * adds to given array to be used in a mysqli prepered statement
     * checks if key exists in values and adds the key,value and type to array
     * @param array $array the array to modify
     * @param array $values the array to get the values from
     * @param string $key the key to check if empty
     * @param string $type (optional) s default. s=string,i=integer,d=double,b=blob
     */
    private function addToBindArray(&$array, $values, $key, $type='s'){
        if(!empty($values[$key])){
            if(is_array($values[$key])) {
                $array[$key] = array('type'=>$type, 'value'=>json_encode($values[$key]));
            }else{
                $array[$key] = array('type'=>$type, 'value'=>$values[$key]);
            }
        }
    }
    
}
