<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Cydynni{

    private $mysqli;
    private $redis;

    public function __construct($mysqli,$redis) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
    }
    
    //CLUB FUNCTIONS
    public function getClubs($club_id = "") {
        $clubs = array();
        if (!empty($club_id)){
            $sql = "SELECT name,generator,root_token,api_prefix,languages,generation_feed,consumption_feed,color,id,slug FROM cydynni_clubs WHERE id = ?";
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param('i', $club_id);
            $stmt->execute();
            $stmt->bind_result(
                $club["name"],
                $club["generator"],
                $club["root_token"],
                $club["api_prefix"],
                $club["languages"],
                $club["generation_feed"],
                $club["consumption_feed"],
                $club["color"],
                $club["id"],
                $club["slug"]
            );
            while ($stmt->fetch()) $clubs[] = $club;
            $stmt->close();
        } else {
            $sql = "SELECT * FROM cydynni_clubs ORDER BY name ASC";
            $result = $this->mysqli->query($sql);
            $clubs = array();
            while ($row = $result->fetch_assoc()) {
                $clubs[] = $row;
            }
        }
        //return single row or array of multiple rows.
        if(!empty($clubs)){
            return $clubs;
        }else{
            return false;
        }
    }

    public function getClubBySlug($slug = "") {
        $sql = "SELECT name,generator,root_token,api_prefix,languages,generation_feed,consumption_feed,color,id,slug FROM cydynni_clubs WHERE slug = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $stmt->bind_result(
            $club["name"],
            $club["generator"],
            $club["root_token"],
            $club["api_prefix"],
            $club["languages"],
            $club["generation_feed"],
            $club["consumption_feed"],
            $club["color"],
            $club["id"],
            $club["slug"]
        );
        while ($stmt->fetch()) $clubs[] = $club; 
        $stmt->close();
        return count($clubs)==1 ? $clubs[0] : $clubs;
    }

    public function saveClub($data, $club_id=false) {
        //get the values and their types
        $fields = array();
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
        if (empty($club_id)) {
            //INSERT
            $stmt = $this->mysqli->prepare("INSERT INTO cydynni_clubs (".implode(",",$names).") VALUES (".implode(",", array_fill(0, count($fields),'?')) .")");
            $bind_params = array_merge(array(implode($types)), $values);
            $bound = call_user_func_array(array($stmt,'bind_param'), $this->arrayAsRefs($bind_params));
        } else {
            //UPDATE
            $stmt = $this->mysqli->prepare("UPDATE cydynni_clubs SET ".implode("=? ,",$names)."=? WHERE id = ?");
            $bind_params = array_merge(array(implode($types) . 'i'), array_merge($values, array($club_id)));
            $bound = call_user_func_array(array($stmt,'bind_param'), $this->arrayAsRefs($bind_params));
        }
        
        $success = $stmt->execute();
        $error = !$success ? $stmt->error : false;
        $stmt->close();

        return array('success'=>$success, 'params'=>$data, 'data'=>array(array('club_id'=>$club_id),array('fields'=>$fields)), 'bound'=>$bound, 'error'=>$error);
    }

    public function deleteClub($club_id) {
        $stmt = $this->mysqli->prepare("DELETE FROM cydynni_clubs WHERE id = ?");
        $stmt->bind_param('i', $club_id);
        if($stmt->execute()){
            $stmt->close();
            return array('success'=>true);
        }
        $stmt->close();
    }

//USER FUNCTIONS

/**
 * get array of users. if userid given only single user returned
 *
 * @param int $userid
 * @return array
 */
    public function getUsers($userid = "") {
        if (!empty($userid)) {
            $stmt = $this->mysqli->prepare('SELECT userid,mpan,token,premisestoken,welcomedate,reportdate,clubs_id FROM cydynni WHERE userid = ?');
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->bind_result(
                $user['userid'],
                $user['mpan'],
                $user['token'],
                $user['premisestoken'],
                $user['welcomedate'],
                $user['reportdate'],
                $user['clubs_id']
            );
            $stmt->fetch();
            $stmt->close();
            return array($user);
        }else{
            //show all users if no userid passed
            $sql = "SELECT userid,mpan,token,premisestoken,welcomedate,reportdate,clubs_id FROM cydynni";
            $result = $this->mysqli->query($sql);
            $users = array();
            while($user = $result->fetch_assoc()) {
                $users[] = $user;
            }
        }
        return $users;
    }
    /**
     * return array of users by given club_id
     *
     * @param int $club_id
     * @return array
     */
    public function getUsersByClub($club_id = "") {
        $users = array();
        if (!empty($club_id)) {
            $stmt = $this->mysqli->prepare('SELECT userid,mpan,token,premisestoken,welcomedate,reportdate,clubs_id FROM cydynni WHERE clubs_id = ?');
            $stmt->bind_param("i", $club_id);
            $stmt->execute();
            $stmt->bind_result(
                $user['userid'],
                $user['mpan'],
                $user['token'],
                $user['premisestoken'],
                $user['welcomedate'],
                $user['reportdate'],
                $user['clubs_id']
            );
            while ($stmt->fetch()) $users[] = $user; 
            $stmt->close();

        }
        return $users;
    }
    /**
     * insert if no user_id passed
     */
    public function saveUser($data, $user_id=false) {
        //get the values and their types (not added to $fields list if empty)
        $fields = array();
        $this->addToBindArray($fields, $data, 'mpan');
        $this->addToBindArray($fields, $data, 'token');
        $this->addToBindArray($fields, $data, 'premisestoken');
        $this->addToBindArray($fields, $data, 'welcomedate');
        $this->addToBindArray($fields, $data, 'reportdate');
        $this->addToBindArray($fields, $data, 'clubs_id', 'i');//int type

        //extract the parts for each name, value and type (for each table)
        foreach($fields as $key=>$v){
            $names[] = $key;
            $values[] = $v['value'];
            $types[] = $v['type'];
        }

        //create the update or insert statements
        //bind the values to the prepered statements
        if(empty($user_id)){
            //INSERT
            $stmt = $this->mysqli->prepare("INSERT INTO cydynni (".implode(",",$names).") VALUES (".implode(",", array_fill(0, count($fields),'?')) .")");
            $bind_params = array_merge(array(implode($types)), $values);
            $bound = call_user_func_array(array($stmt,'bind_param'), $this->arrayAsRefs($bind_params));
        }else{
            //UPDATE
            $stmt = $this->mysqli->prepare("UPDATE cydynni SET ".implode("=? ,",$names)."=? WHERE userid = ?");
            $bind_params = array_merge(array(implode($types) . 'i'), array_merge($values, array($user_id)));
            $bound = call_user_func_array(array($stmt,'bind_param'), $this->arrayAsRefs($bind_params));
        }
        
        $success = $stmt->execute();
        $insert_id = $this->mysqli->insert_id; 
        $affected_rows = $this->mysqli->affected_rows;
        $error = !$success ? $stmt->error : false;
        $stmt->close();
        
        return array(
            'success'=>$success, 
            'params'=>$data, 
            'fields'=>$fields,
            'bound'=>$bound, 
            'user_id'=> empty($user_id) ? $insert_id : $user_id,
            'affected_rows'=>$affected_rows, 
            'error'=>$error
        );
    }
    /**
     * insert if no user_id passed
     */
    public function deleteUser($userid) {
        $stmt = $this->mysqli->prepare("DELETE FROM cydynni WHERE userid = ?");
        $stmt->bind_param('i', $userid);
        if($stmt->execute()){
            $stmt->close();
            $user->delete($userid);
            return array('success'=>true);
        }
        $stmt->close();
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
