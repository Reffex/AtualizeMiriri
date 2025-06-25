<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $bd = 'emprestimos';

    $mysqli = new mysqli ($host, $user, $pass, $bd);
    if ($mysqli->connect_errno) {
       die ("Erro na conexão: " . $mysqli->connect_errno);
}
?>
