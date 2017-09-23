<?php
require 'tomb/DB.php';
$result = DB::table('users')->select('*')->get();
echo "<pre>";print_r($result);die;
?>
