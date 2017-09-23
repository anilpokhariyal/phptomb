<?php
// error_reporting(0);
// require_once("server.php");
//credit erevolutions (india)
class DB{
  public $table;
  public $connect;
  public $result = '';
  public function __construct(){
    require_once("server.php");
    $this->connect = $this->connection($SERVER,$USER,$PASS,$DBNAME);
  }

  public function connection($SERVER='',$USER='',$PASS='',$DBNAME=''){
    $connect = mysqli_connect($SERVER,$USER,$PASS,$DBNAME);
    if(!$connect){
      echo "Error: Unable to connect to MySQL." . PHP_EOL;
      echo "Error Specification: " . mysqli_connect_errno() . PHP_EOL;
      exit;
    }else{
      return $connect;
    }
  }

  public function table($table=''){
    $this->table = $table;
    return $this;
  }

  public function get(){
    $output = array();
    while($result = mysqli_fetch_object($this->result)){
      $output[] = $result;
    }
    return (object) $output;
  }

  public function select($values){
    $table = $this->table;
    $this->result = $this->connect->query("SELECT ".$values." from ".$table);
    return $this;
  }

  public function insert($values=array()){
    $table = $this->table;
    $query = $connect->query("INSERT INTO ".$table." set ".$values);
    return $query;
  }

}
?>
