<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $bd = 'registros_clientes';

    $mysqli = new mysqli ($host, $user, $pass, $bd);
    if ($mysqli->connect_errno) {
       die ("Erro na conexão: " . $mysqli->connect_errno);
}
?>
