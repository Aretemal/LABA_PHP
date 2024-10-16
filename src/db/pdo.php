<?php
function getPDO() {
    $dsn = 'mysql:host=localhost;dbname=php_laba2;charset=utf8';
    $username = 'root';
    $password = '';

    return new PDO($dsn, $username, $password);
}
?>