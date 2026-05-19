<?php
$config = [
    'username' => 'GG_SCHEMA',
    'password' => 'admin',
    'connection_string' => 'localhost:1521/XEPDB1',
];

$local = __DIR__ . '/db.local.php';
if (is_readable($local)) {
    $config = array_merge($config, require $local);
}

$username = $config['username'];
$password = $config['password'];
$connectionString = $config['connection_string'];

$conn = oci_connect($username, $password, $connectionString);

if (!$conn) {
    $e = oci_error();
    die('Database connection failed:<br>' . htmlentities($e['message'] ?? 'unknown'));
}
