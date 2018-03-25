<?php
// General singleton class.
namespace MO;
use PDO;
use PDOException;

/**
____  _   _ ____    ____  ____    _   _ _____ _     ____  _____ ____
|  _ \| | | |  _ \  |  _ \| __ )  | | | | ____| |   |  _ \| ____|  _ \
| |_) | |_| | |_) | | | | |  _ \  | |_| |  _| | |   | |_) |  _| | |_) |
|  __/|  _  |  __/  | |_| | |_) | |  _  | |___| |___|  __/| |___|  _ <
|_|   |_| |_|_|     |____/|____/  |_| |_|_____|_____|_|   |_____|_| \_\
 */
class Model{
    //Database host
    //Fill all with your db details then extend the base model by child models.
    public $host = "";
    //Database username
    public $username = "";
    //Database password
    public $password = "";
    //Database name
    public $db = '';
    // Hold the class instance.
    private static $instance;
    //Table Name to be set inside model.
    public static $tbl_name;

    /**
     * DB constructor.
     */
    protected function __construct()
    {
        try {
            self::$instance = new PDO("mysql:host=$this->host;dbname=$this->db", $this->username, $this->password);
            // set the PDO error mode to exception
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch
        (PDOException $e) {
            return 'fail';
        }
    }
    /**
     * Create new Instance of DB Class
     * @return PDO
     */
    public static function getInstance()
    {
        if (self::$instance == null)
        {
            new DB();
        }

        return self::$instance;
    }
    /**
     * @self $tbl_name
     * @param $params
     * @return mixed
     */

    private static function select($params){
        $string = "SELECT ".$params['cols']." FROM ".static::$tbl_name;
        if(isset($params['where'])){
            $string .=" WHERE {$params['where']['column']} {$params['where']['operator']}".":"."{$params['where']['column']}";
        }
        if(isset($params['order'])){
            $string .=" order by {$params['order']['key']} {$params['order']['type']}";
        }
        if(isset($params['limit'])){
            $string .=" LIMIT {$params['limit']}";
        }
        $instance = self::$instance;
        $stmt = $instance->prepare($string);
        if(isset($params['where'])&&!empty($params['where'])) {
            $key = ":".$params['where']['column'];
            $value = $params['where']['value'];
            $stmt->bindValue($key,$value);
        }
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_OBJ);
        return $stmt;
    }

    /**
     * @param $params
     * @return mixed
     */
    public static function fetch($params)
    {
        self::getInstance();
        try{
            if(!isset($params['cols']))
            {
                $params['cols'] = '*';
            }
            $stmt = self::select($params);
            $final = $stmt->fetch();
            return $final;
        }
        catch (PDOException $e) {
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public static function fetchAll($params)
    {
        self::getInstance();
        try {
            if(!isset($params['cols']))
            {
                $params['cols'] = '*';
            }
            $stmt = self::select($params);
            $final = $stmt->fetchAll();
            return $final;
        }
        catch (PDOException $e) {
            echo 'Failed to fetch data.';
        }

    }

    /**
     * @param $params
     * @return mixed
     */
    public static function getAll($params)
    {
        self::getInstance();
        if(!isset($params['cols']))
        {
            $params['cols'] = '*';
        }
        $all = self::fetchAll($params);
        return $all;
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getByID($id)
    {
        self::getInstance();
        $params = [
            'where' =>[
                'column' => 'id',
                'operator' => '=',
                'value' => $id
            ]
        ];
        $all = self::fetch($params);
        return $all;
    }

    /**
     * @param $params
     * @return mixed
     */
    public static function first($params){
        self::getInstance();
        if(!isset($params['cols']))
        {
            $params['cols'] = '*';
        }
        $stats = self::fetchAll($params);
        return $stats[0];
    }
    /**
     * @param array $array
     * @return string
     */
    private static function get_bind($array = array()){
        $keys = array_keys($array);
        $values = null;
        foreach (array_slice($keys, 0, count($array) - 1) as $key => $value){
            $values .=':'.$value.', ';
        }
        $all =  $values.':'.end($keys);
        return $all;
    }
    /**
     * @param array $array
     * @return string
     */
    private static function get_bind_exe($array = array()){
        $keys = array_keys($array);
        $values = null;
        foreach (array_slice($keys, 0, count($array) - 1) as $key => $value){
            $values []=':'.$value;
        }
        $values[] =  ':'.end($keys);
        return $values;
    }
    /**
     * @param array $array
     * @return string
     */
    private static function get_values($array = array()){
        $keys = array_values($array);
        return $keys;
    }
    /**
     * @param array $array
     * @return string
     */
    private static function get_keys($array = array()){
        $keys = array_keys($array);
        $values = null;
        foreach (array_slice($keys, 0, count($array) - 1) as $key => $value){
            $values .=$value.', ';
        }
        $all =  $values.end($keys);
        return $all;
    }


    /**
     * @param array $params
     * @return bool
     */
    public static function Create($params=array()){
        self::getInstance();
        $keys = self::get_keys($params);
        $bind = self::get_bind($params);
        $string = "INSERT INTO ".static::$tbl_name." (". $keys . ") VALUES (". $bind .")";
        $values = self::get_values($params);
        $bind_2 = self::get_bind_exe($params);
        $bindp = array_combine($bind_2,$values);
        try
        {$stmt = self::$instance->prepare($string);
            self::bindParam($stmt,$bindp);
            $stmt->execute();
        }
        catch
        (PDOException $e) {
            echo 'Failed to insert Data.';
            echo $e->getMessage();
        }
    }

    /**
     * @param $condition
     */
    public static function Delete($condition)
    {
        self::getInstance();
        $sql = "DELETE FROM ".static::$tbl_name." WHERE {$condition['column']} {$condition['operator']} {$condition['value']}";
        $key = ":".$condition['column'];
        $value = $condition['value'];
        try{$sql = self::$instance->prepare($sql);$sql->bindParam($key,$value);$sql->execute();}
        catch
        (PDOException $e) {
            echo 'Failed to Delete Data.';
        }
    }
    /**
     * @param $array
     * @return string
     */
    public static function update_array($array)
    {
        $statement = NULL;
        foreach(array_slice($array,0,count($array)-1,true) as $key => $value)
        {
            $statement .=  $key . '=' .":".$key.",";
        }
        end($array);
        $all = $statement . key($array).' = :'.key($array);
        return $all;
    }

    /**
     * @param $array
     * @param $where
     * @return bool
     */
    public static function Update($array,$where)
    {
        self::getInstance();
        $sql = "UPDATE ".static::$tbl_name." SET ". self::update_array($array).' '."WHERE {$where['column']} {$where['operator']} ".':'."{$where['column']}";
        $sql = self::$instance->prepare($sql);
        $key = ":".$where['column'];
        $value = $where['value'];
        $sql->bindParam($key,$value);
        $values = self::get_values($array);
        $bind_2 = self::get_bind_exe($array);
        $bindp = array_combine($bind_2,$values);
        self::bindParam($sql,$bindp);

        try {
            if ($sql->execute()) {
                return true;
            } else {
                return false;
            }
        }
        catch
        (PDOException $e) {
            echo 'Failed to Update Data.';
        }
    }

    /**
     * @param $stmt
     * @param $bind
     */
    private static function bindParam($stmt,$bind){
        foreach($bind as $key => $value)
        {
            $stmt->bindValue($key,$value);
        }
    }

    /**
     * @param $statment
     * @return bool|mixed
     */
    public static function Query($statment)
    {
        self::getInstance();
        $string = $statment;
        $stmt = self::$instance->prepare($string);
        $stmt->execute();
        if(strtolower(substr($statment,0,6)) == 'select') {
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $result = $stmt->fetch();
            return $result;
        }
        else{
            if($stmt)
            {
                return true;
            }
            else{
                return false;
            }
        }
    }
    protected function __clone() {}
    protected function __wakeup() {}
}