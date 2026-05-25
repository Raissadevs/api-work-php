<?php
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'banco_noite';
$port = '3307';

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}
?>