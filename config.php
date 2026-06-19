<?php
// config.php
date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'sql205.infinityfree.com';
$db   = 'if0_42218812_pn_shift_check';
$user = 'if0_42218812';
$pass = 'shiftchecking'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Shift Calculation Helper Functions
function calculate_shift($time_str = 'now') {
    $time = new DateTime($time_str);
    $time_formatted = $time->format('H:i');
    
    if ($time_formatted >= '07:00' && $time_formatted < '15:00') {
        return 'day'; // เวรเช้า (07.00 เป็นต้นไป)
    } elseif ($time_formatted >= '15:00' && $time_formatted < '22:00') {
        return 'eve'; // เวรบ่าย (15.00 เป็นต้นไป)
    } else {
        return 'night'; // เวรดึก (22.00 เป็นต้นไป)
    }
}

function get_shift_name($shift_key) {
    $names = [
        'day' => 'เวรเช้า (07:00 - 15:00)',
        'eve' => 'เวรบ่าย (15:00 - 22:00)',
        'night' => 'เวรดึก (22:00 - 07:00)'
    ];
    return isset($names[$shift_key]) ? $names[$shift_key] : 'ไม่ระบุ';
}

function get_current_shift_range($shift_key) {
    $now = new DateTime();
    $start = new DateTime();
    $end = new DateTime();
    
    if ($shift_key === 'day') {
        $start->setTime(7, 0, 0);
        $end->setTime(15, 0, 0);
    } elseif ($shift_key === 'eve') {
        $start->setTime(15, 0, 0);
        $end->setTime(22, 00, 0);
    } else { // night
        $hour = (int)$now->format('H');
        if ($hour >= 22) {
            $start->setTime(22, 00, 0);
            $end->modify('+1 day')->setTime(7, 0, 0);
        } else {
            $start->modify('-1 day')->setTime(22, 00, 0);
            $end->setTime(7, 0, 0);
        }
    }
    return [
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s')
    ];
}
