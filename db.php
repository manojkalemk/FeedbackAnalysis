<?php
$servername = 'localhost';
$username = 'scmc_dbuser';
$password = 'u$erd6u$3rsCMz@';
$dbname = 'scmc_lms';
$prefix = 'scmc_';

$conn = mysqli_connect($servername, $username, $password, "$dbname");

if (!$conn) {
    die('Could not Connect');

}
?>