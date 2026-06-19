<?php

$host = 'sql205.infinityfree.com';
$db   = 'if0_42218812_pn_shift_check';
$user = 'if0_42218812';
$pass = 'shiftchecking'; 

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
