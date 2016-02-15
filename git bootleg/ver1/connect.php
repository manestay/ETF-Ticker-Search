<?php
$user = 'root';
$pass = '';
$db = 'etfsdb';

$db = new mysqli('localhost', $user, $pass, $db) or die("Unable to connect to server");
echo "successful connection";

?> 