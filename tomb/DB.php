<?php
error_reporting(E_ALL);
//credit erevolutions (india)
// For OPEN SOURCE
class DB{
  private $__table='';
  public $connect;
  public $query = '';
  public $where = '';
  public $andDeli = ' AND ';
  public $commaDeli = ', ';
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

  public function count(){
    $output = 0;
    $response = $this->connect->query("SELECT * from ".$this->__table." WHERE ".$this->where);
    $output = mysqli_num_rows($response);
    return $output;
  }

  public function select($values){
    $table = $this->__table;
    $this->query = "SELECT ".$values." from ".$table;
    return $this;
  }

  public function insert($values=array()){
    $table = $this->__table;
    $value = $this->parseArray($values,$this->commaDeli);
    $query = $this->connect->query("INSERT INTO ".$table." SET ".$value);
    return $query;
  }

  public function update($values=array()){
    $value = $this->parseArray($values,$this->commaDeli);
    $result = $this->connect->query("UPDATE ".$this->__table." SET ".$value." WHERE ".$this->where);
    return $result;
  }

  public function where($where=array()){
    $this->where .= $this->parseArray($where,$this->andDeli);
    return $this;
  }

  public function parseArray($array=array(),$delimeter=''){
    function value_maker(&$item, $key, $prefix){
        $item = "$prefix$item$prefix";
    }
    array_walk($array, 'value_maker', '\'');
    $result = '';
    $count = 0;
    $result = urldecode(http_build_query($array,'',$delimeter));
    return $result;
  }
}
?>
