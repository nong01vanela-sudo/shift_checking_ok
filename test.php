<?php

$host = 'ใส่ Database Host';
$db   = 'ใส่ Database Name';
$user = 'ใส่ Database User';
$pass = 'ใส่ Password';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );

    echo "Connected OK";
} catch (PDOException $e) {
    echo $e->getMessage();
}
