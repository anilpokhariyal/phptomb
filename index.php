<?php
require 'tomb/DB.php';
$obj = new DB();
$result = $obj->table('users')->select('*')->get();
echo "<pre>";print_r($result);die;
 ?>
