<?php
error_reporting(0);
//credit erevolutions (india)
// For OPEN SOURCE
class DB{
  private $__table='';
  public $connect;
  public $query = '';
  public $where = 1;
  public function __construct($table){
    $this->__table = $table;
    require_once("server.php");
    $this->connect = $this->connection($SERVER,$USER,$PASS,$DBNAME);
  }

  public function connection($SERVER = '', $USER = '', $PASS = '', $DBNAME = '') {
    $connect = mysqli_connect($SERVER,$USER,$PASS,$DBNAME);
    if(!$connect){
      echo "Error: Unable to connect to MySQL." . PHP_EOL;
      echo "Error Specification: " . mysqli_connect_errno() . PHP_EOL;
      exit;
    }else{
      return $connect;
    }
  }

  public static function table($table){
     return new DB($table);
  }

  public function get(){
    $output = array();
    $response = $this->connect->query($this->query);
    while($result = mysqli_fetch_object($response)){
      $output[] = $result;
    }
    return (object) $output;
  }

  public function select($values){
    $table = $this->__table;
    $this->query = "SELECT ".$values." from ".$table;
    return $this;
  }

  public function insert($values=array()){
    $table = $this->__table;
    $value = $this->parseArray($values);
    $query = $this->connect->query("INSERT INTO ".$table." SET ".$value);
    return $query;
  }

  public function update($values=array()){
    $value = $this->parseArray($values);
    $result = $this->connect->query("UPDATE ".$this->__table." SET ".$value." WHERE ".$this->where);
    return $result;
  }

  public function where($where=array()){
    $this->where = $this->parseArray($where);
    return $this;
  }

  public function parseArray($array=array()){
    $result = '';
    $count = 0;
    foreach($array as $k=>$arr){
      if($count==0)
        $result .= $k."='".$arr."'";
      else
        $result .= ",".$k."='".$arr."'";
        $count++;
    }
    return $result;
  }
}
?>
