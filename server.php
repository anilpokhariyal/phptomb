<?php
/**
 * Database credentials. You can set these here or via environment variables
 * (DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME) which override these defaults.
 */

$SERVER = getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? getenv('DB_HOST') : 'localhost';
$DB_PORT = getenv('DB_PORT') !== false && getenv('DB_PORT') !== ''
    ? (int) getenv('DB_PORT')
    : 3306;
$USER = getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? getenv('DB_USER') : 'root';
$PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$DBNAME = getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? getenv('DB_NAME') : 'dbname';
