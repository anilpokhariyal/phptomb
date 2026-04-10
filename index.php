<?php
/**
 * Sample usage — ensure `server.php` points at a real database with a `users` table,
 * or change the table name below.
 */
// Optional: define('TOMB_LOG_ENABLED', true);

require __DIR__ . '/tomb/DB.php';

$result = DB::table('users')->select('*')->get();
echo '<pre>';
print_r($result);
echo '</pre>';
