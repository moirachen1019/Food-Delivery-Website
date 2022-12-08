<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "hw_db";

$conn = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass, array( PDO::ATTR_PERSISTENT => true));
# set the PDO error mode to exception
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);