<?php
// db_setup.php
header('Content-Type: text/html; charset=utf-8');

$host = 'sql205.infinityfree.com';
$user = 'if0_42218812';
$pass = 'shiftchecking'; 

try {
    // 1. Connect to MySQL Server (without specifying database to ensure we can create it)
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h3>1. Connected to MySQL successfully.</h3>";

    // 2. Create database pn_check if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `pn_check` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<h3>2. Database `pn_check` created or already exists.</h3>";

    // 3. Connect to the pn_check database
    $pdo->exec("USE `pn_check`");

    // 4. Create user table
    $tableQuery = "
        CREATE TABLE IF NOT EXISTS `user` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ot` INT(5) NOT NULL UNIQUE,
            `pass` VARCHAR(10) NOT NULL,
            `name` VARCHAR(25) NOT NULL,
            `surname` VARCHAR(25) NOT NULL,
            `role` INT(2) NOT NULL COMMENT '0: nurse, 1: HPN, 2: PN, 3: HP, 4: admin'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($tableQuery);
    echo "<h3>3. Table `user` created or already exists.</h3>";

    // 5. Create stock_set_sterile table
    $stockSetQuery = "
        CREATE TABLE IF NOT EXISTS `stock_set_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `set_name` VARCHAR(50) NOT NULL UNIQUE,
            `total_qty` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($stockSetQuery);
    echo "<h3>4. Table `stock_set_sterile` created or already exists.</h3>";

    // 6. Create shift check tables
    // Night Shift Check Table
    $nightQuery = "
        CREATE TABLE IF NOT EXISTS `night_check_set_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `set_name` VARCHAR(30) NOT NULL,
            `n_balance` INT(2) NOT NULL,
            `n_supply` INT(2) NOT NULL,
            `n_borrow` INT(2) NOT NULL,
            `n_borrower` VARCHAR(10),
            `n_checker` VARCHAR(25) NOT NULL,
            `n_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($nightQuery);
    echo "<h3>5. Table `night_check_set_sterile` created or already exists.</h3>";

    // Day Shift Check Table
    $dayQuery = "
        CREATE TABLE IF NOT EXISTS `day_check_set_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `set_name` VARCHAR(30) NOT NULL,
            `d_balance` INT(2) NOT NULL,
            `d_supply` INT(2) NOT NULL,
            `d_borrow` INT(2) NOT NULL,
            `d_borrower` VARCHAR(10),
            `d_checker` VARCHAR(25) NOT NULL,
            `d_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($dayQuery);
    echo "<h3>6. Table `day_check_set_sterile` created or already exists.</h3>";

    // Evening Shift Check Table
    $eveQuery = "
        CREATE TABLE IF NOT EXISTS `eve_check_set_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `set_name` VARCHAR(30) NOT NULL,
            `e_balance` INT(2) NOT NULL,
            `e_supply` INT(2) NOT NULL,
            `e_borrow` INT(2) NOT NULL,
            `e_borrower` VARCHAR(10),
            `e_checker` VARCHAR(25) NOT NULL,
            `e_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($eveQuery);
    echo "<h3>7. Table `eve_check_set_sterile` created or already exists.</h3>";

    // 7. Create stock_equipment_sterile table
    $stockEquipSterileQuery = "
        CREATE TABLE IF NOT EXISTS `stock_equipment_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_sterile_name` VARCHAR(50) NOT NULL UNIQUE,
            `total_qty` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($stockEquipSterileQuery);
    echo "<h3>8. Table `stock_equipment_sterile` created or already exists.</h3>";

    // 8. Create stock_equipment_general table
    $stockEquipGeneralQuery = "
        CREATE TABLE IF NOT EXISTS `stock_equipment_general` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_general_name` VARCHAR(50) NOT NULL UNIQUE,
            `total_qty` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($stockEquipGeneralQuery);
    echo "<h3>9. Table `stock_equipment_general` created or already exists.</h3>";

    // 9. Create check tables for Sterile Equipment (night, day, eve)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `night_check_equipment_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_sterile_name` VARCHAR(50) NOT NULL,
            `n_balance` INT(2) NOT NULL,
            `n_supply` INT(2) NOT NULL,
            `n_borrow` INT(2) NOT NULL,
            `n_borrower` VARCHAR(10),
            `n_checker` VARCHAR(25) NOT NULL,
            `n_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `day_check_equipment_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_sterile_name` VARCHAR(50) NOT NULL,
            `d_balance` INT(2) NOT NULL,
            `d_supply` INT(2) NOT NULL,
            `d_borrow` INT(2) NOT NULL,
            `d_borrower` VARCHAR(10),
            `d_checker` VARCHAR(25) NOT NULL,
            `d_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `eve_check_equipment_sterile` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_sterile_name` VARCHAR(50) NOT NULL,
            `e_balance` INT(2) NOT NULL,
            `e_supply` INT(2) NOT NULL,
            `e_borrow` INT(2) NOT NULL,
            `e_borrower` VARCHAR(10),
            `e_checker` VARCHAR(25) NOT NULL,
            `e_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<h3>10. Shift check tables for Sterile Equipment created or already exist.</h3>";

    // 10. Create check tables for General Equipment (night, day, eve)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `night_check_equipment_general` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_general_name` VARCHAR(50) NOT NULL,
            `n_balance` INT(2) NOT NULL,
            `n_supply` INT(2) NOT NULL,
            `n_borrow` INT(2) NOT NULL,
            `n_borrower` VARCHAR(10),
            `n_checker` VARCHAR(25) NOT NULL,
            `n_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `day_check_equipment_general` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_general_name` VARCHAR(50) NOT NULL,
            `d_balance` INT(2) NOT NULL,
            `d_supply` INT(2) NOT NULL,
            `d_borrow` INT(2) NOT NULL,
            `d_borrower` VARCHAR(10),
            `d_checker` VARCHAR(25) NOT NULL,
            `d_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `eve_check_equipment_general` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_general_name` VARCHAR(50) NOT NULL,
            `e_balance` INT(2) NOT NULL,
            `e_supply` INT(2) NOT NULL,
            `e_borrow` INT(2) NOT NULL,
            `e_borrower` VARCHAR(10),
            `e_checker` VARCHAR(25) NOT NULL,
            `e_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<h3>11. Shift check tables for General Equipment created or already exist.</h3>";

    // Seed initial users if table is empty
    $checkQuery = $pdo->query("SELECT COUNT(*) FROM `user`");
    $count = $checkQuery->fetchColumn();

    if ($count == 0) {
        $insertQuery = "
            INSERT INTO `user` (`ot`, `pass`, `name`, `surname`, `role`) VALUES
            (10001, '123456', 'สมชาย', 'ใจดี', 4),       -- Admin
            (20002, 'password', 'สมศรี', 'รักษ์ไทย', 0),    -- Nurse
            (30003, 'pn123', 'สมพงษ์', 'รักดี', 2),      -- PN
            (40004, 'hpn789', 'พรทิพย์', 'มุ่งมั่น', 1),    -- HPN
            (50005, 'hp555', 'นรากร', 'เรียนรู้', 3)       -- HP
        ";
        $pdo->exec($insertQuery);
        echo "<h3>12. Seeded default users successfully.</h3>";
    }

    // Seed stock_set_sterile if empty
    $checkStockQuery = $pdo->query("SELECT COUNT(*) FROM `stock_set_sterile`");
    $stockCount = $checkStockQuery->fetchColumn();

    if ($stockCount == 0) {
        $setsData = [
            ['Set DPL', 2],
            ['Set External', 1],
            ['Pelvic Clamp', 1],
            ['Set เจาะคอ', 2],
            ['Set Thoracotomy', 2],
            ['Set basic', 2],
            ['Set Skeletal traction', 1],
            ['Set ICD', 6],
            ['ขวด ICD', 11],
            ['จุกICD', 11],
            ['Set เจาะปอด-ท้อง', 7],
            ['Set I$D', 4],
            ['Set Foley\'s Cath', 34],
            ['Set Flush', 15],
            ['Set Irrigate', 32],
            ['Set Suture Plastic', 15],
            ['Set Suture ธรรมดา', 15],
            ['Set Dressing', 21],
            ['Guide Uro', 4],
            ['Silver cath', 2],
            ['Set Proctoscopy', 4],
            ['ชุด CVC 2', 4],
            ['ชุดการน์กันน้ำ', 4],
            ['Set คลอด', 2],
            ['Set PV', 2],
            ['Tank O2เล็ก', 8],
            ['Tank O2 กลาง(20)', 10],
            ['Tank O2 (เล็กใหม่)', 20],
            ['หัวต่อ O2 Tank เล็ก', 8],
            ['หัวต่อ O2 Tank กลาง', 2]
        ];

        $insertStock = $pdo->prepare("INSERT INTO `stock_set_sterile` (`set_name`, `total_qty`) VALUES (?, ?)");
        foreach ($setsData as $set) {
            $insertStock->execute($set);
        }
        echo "<h3>13. Seeded stock_set_sterile successfully (30 items).</h3>";
    } else {
        echo "<h3>13. Table `stock_set_sterile` already has data. Skipping seeding.</h3>";
    }

    // Seed stock_equipment_sterile if empty
    $checkEquipSterileQuery = $pdo->query("SELECT COUNT(*) FROM `stock_equipment_sterile`");
    $equipSterileCount = $checkEquipSterileQuery->fetchColumn();

    if ($equipSterileCount == 0) {
        $sterileData = [
            ['Plobe', 2],
            ['Adson Forceps', 4],
            ['Aligator', 1],
            ['Currette', 2],
            ['Eye Retractor', 1],
            ['ด้ามมีด', 3],
            ['Skin Hook', 1],
            ['Byonette', 5],
            ['Sinus Forceps', 1],
            ['กรรไกรตัดเล็บ', 1],
            ['Nasal speculum', 3],
            ['Towel Clip', 2],
            ['Allis Tissue Forceps', 2],
            ['Kocker', 1],
            ['กรรไกรตัดไหมธรรมดา', 6],
            ['กรรไกรMayo', 3],
            ['Metzenbaum ธรรมดา', 4],
            ['Zen Retractor', 6],
            ['Metzenbaum Plastic', 7],
            ['Holder Plastic', 6],
            ['Walsham', 1],
            ['Arch Forceps', 1],
            ['Clamp โค้งใหญ่', 6],
            ['Clamp โค้งเล็ก', 4],
            ['Forceps Suction', 15],
            ['Forceps', 8],
            ['Long Forceps', 4],
            ['Bone Ronger', 1],
            ['ฆ้อน', 2]
        ];

        $insertEquipSterile = $pdo->prepare("INSERT INTO `stock_equipment_sterile` (`equipment_sterile_name`, `total_qty`) VALUES (?, ?)");
        foreach ($sterileData as $item) {
            $insertEquipSterile->execute($item);
        }
        echo "<h3>14. Seeded stock_equipment_sterile successfully (29 items).</h3>";
    } else {
        echo "<h3>14. Table `stock_equipment_sterile` already has data. Skipping seeding.</h3>";
    }

    // Seed stock_equipment_general if empty
    $checkEquipGeneralQuery = $pdo->query("SELECT COUNT(*) FROM `stock_equipment_general`");
    $equipGeneralCount = $checkEquipGeneralQuery->fetchColumn();

    if ($equipGeneralCount == 0) {
        $generalData = [
            ['Spinal board', 5],
            ['หัวต่ออ๊อกซิเจน Pine line', 18],
            ['หัวต่ออ๊อกซิเจน Pine line(ห้อง Negative)', 4],
            ['หัวต่อพ่นยา', 4],
            ['คีมปากนกแก้ว', 1],
            ['เครื่องตัดเผือก', 2],
            ['ที่งัดเผือก', 2],
            ['Chinese Finger ชุดใหม่', 1],
            ['Thomas\'s Splint (Adult)', 4],
            ['Thomas\'s Splint (Ped)', 1],
            ['ไฟฉาย', 3],
            ['Knee Jerk', 3],
            ['เครื่องวัด BP ตั้ง', 3],
            ['ปืนเป่าลมแอร์', 1],
            ['Bedpan', 3],
            ['Fx .Bedpan  (เล็ก)', 2],
            ['Urinal', 7],
            ['สายวัด', 1],
            ['Pelvic SAM\'s Sling', 4],
            ['ที่ห้อยหมอนทราย', 2],
            ['T- piece (หัวAdapter)', 2],
            ['ปรอท วัดไข้(แบบหนีบ)', 3],
            ['ปรอทวัดไข้ (ทางหู)', 1],
            ['ปรอทวัดไข้ (ทางหน้าผาก)', 1],
            ['ชุดBipap', 8],
            ['Lack ใส่ขวดICD', 5],
            ['กล่องปลายเตียง', 20],
            ['เตียงสีส้ม', 15],
            ['ประแจปากเลื่อน(หมุนTank)', 1],
            ['Tourniquet', 9]
        ];

        $insertEquipGeneral = $pdo->prepare("INSERT INTO `stock_equipment_general` (`equipment_general_name`, `total_qty`) VALUES (?, ?)");
        foreach ($generalData as $item) {
            $insertEquipGeneral->execute($item);
        }
        echo "<h3>15. Seeded stock_equipment_general successfully (30 items).</h3>";
    } else {
        echo "<h3>15. Table `stock_equipment_general` already has data. Skipping seeding.</h3>";
    }

    echo "<h2 style='color: green;'>Database setup complete!</h2>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database Setup Failed:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
