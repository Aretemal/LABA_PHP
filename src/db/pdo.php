<?php
function getPDO() {
    $dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
    $username = 'root';
    $password = '';

    return new PDO($dsn, $username, $password);
}
?>