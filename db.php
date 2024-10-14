<?php
$host = 'localhost';
$user = 'u310652966_nicolab';
$password = 'Minchia_pulita9'; // Sostituisci con la tua password
$database = 'u310652966_dataagency';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>
