<?php
session_start();
// error_reporting(E_ALL);
//credit erevolutions (india)
// For OPEN SOURCE
class DB{
  private $__table='';
  private $connect;
  private $query = '';
  private $where = '';
  private $andDeli = ' AND ';
  private $commaDeli = ', ';
  private $selects = ' * ';
  public function __construct($table){
    $this->__table = $table;
    require_once("server.php");
    $this->connect = $this->connection($SERVER,$USER,$PASS,$DBNAME);
  }

  public function __destruct(){
    mysqli_close();
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

  public function generateQuery(){
    if($this->where == '')
      $this->where = '1';
    $query = "SELECT ".$this->selects." FROM ".$this->__table.' WHERE '.$this->where;
    $this->_log($query);
    return $query;
  }

  public function get(){
    $output = array();
    $query = $this->generateQuery();
    $response = $this->connect->query($query);
    while($result = mysqli_fetch_object($response)){
      $output[] = $result;
    }
    return (object) $output;
  }

  public function first(){
    $output = array();
    $query = $this->generateQuery();
    $response = $this->connect->query($query.' LIMIT 0,1');
    return (object) mysqli_fetch_object($response);
  }

  public function count(){
    $output = 0;
    $response = $this->connect->query("SELECT * from ".$this->__table." WHERE ".$this->where);
    $output = mysqli_num_rows($response);
    return $output;
  }

  public function select($values){
    $this->selects = $values;
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
    $query = "UPDATE ".$this->__table." SET ".$value." WHERE ".$this->where;
    $this->_log($query);
    $result = $this->connect->query($query);
    return $result;
  }

  public function where($key='',$sec='',$third=''){
    if(is_array($key)){
      $this->where .= $this->parseArray($key,$this->andDeli);
    }else{
    $exp = '=';
    if($third=='')
      $value = $sec;
    else {
      $exp = $sec;
      $value = $third;
    }
    if($this->where!='')
      $this->where .= ' '.$this->andDeli;
    $this->where .=$key.$exp.$value.' ';
    }
    $this->_log($this->where);
    return $this;
  }

  public function raw($qry=''){
    $this->query .= $qry;
    return $this;
  }

  public function limit($from,$count){
    $this->query .= ' LIMIT '.$from.','.$count;
    return $this;
  }

  public function parseArray($array=array(),$delimeter=''){
    $result = ' ';
    $sr = 0;
    $limit = count($array);
    foreach($array as $k=>$a){
      $sr++;
      if($sr==$limit)
      $result .= $k.'="'.$a.'" ';
      else
      $result .= $k.'="'.$a.'" '. $delimeter;
    }
    $this->_log($result);
    return $result;
  }

  public function _log($result){
    file_put_contents('logs/.log_'.date("j.n.Y").'.txt', $result."\n", FILE_APPEND);
  }
}
?>
