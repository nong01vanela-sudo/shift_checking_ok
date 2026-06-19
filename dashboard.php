<?php
// dashboard.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `user` WHERE `id` = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$roles = [
    0 => ['name' => 'Nurse', 'class' => 'role-0'],
    1 => ['name' => 'HPN',   'class' => 'role-1'],
    2 => ['name' => 'PN',    'class' => 'role-2'],
    3 => ['name' => 'HP',    'class' => 'role-3'],
    4 => ['name' => 'Admin', 'class' => 'role-4']
];

$roleId    = (int)$user['role'];
$roleName  = isset($roles[$roleId]) ? $roles[$roleId]['name'] : 'Unknown';
$roleClass = isset($roles[$roleId]) ? $roles[$roleId]['class'] : '';

$currentShift = isset($_SESSION['shift']) ? $_SESSION['shift'] : calculate_shift();
$shiftNames   = ['day' => 'เวรเช้า', 'eve' => 'เวรบ่าย', 'night' => 'เวรดึก'];
$shiftLabel   = $shiftNames[$currentShift] ?? 'ไม่ระบุ';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก | Stock Checking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-container">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">

        <!-- Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle" title="ย่อ/ขยายเมนู" onclick="toggleSidebar()">
            <svg viewBox="0 0 24 24" class="toggle-icon"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="sidebar-top">
            <!-- Brand -->
            <div class="sidebar-header">
                <div class="brand-text">
                    <h2 class="sidebar-title">STOCK CHECKING</h2>
                    <p class="sidebar-subtitle">ระบบนับสต็อก</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="nav-link active" title="หน้าหลัก">
                    <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span class="nav-label">หน้าหลัก</span>
                </a>
                <a href="set_sterile.php" class="nav-link" title="SET sterile">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span class="nav-label">SET sterile</span>
                </a>
                <a href="equipment_sterile.php" class="nav-link" title="เครื่องมือ sterile">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span class="nav-label">เครื่องมือ sterile</span>
                </a>
                <a href="equipment_general.php" class="nav-link" title="อุปกรณ์">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <span class="nav-label">อุปกรณ์</span>
                </a>
                <a href="#" class="nav-link disabled" title="ตรวจสอบ">
                    <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span class="nav-label">ตรวจสอบ</span>
                </a>
            </nav>
        </div>

        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="profile-card">
                <div class="profile-avatar">
                    <svg viewBox="0 0 24 24" class="nav-icon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></div>
                    <div class="profile-meta">
                        <span class="flat-role-badge <?php echo $roleClass; ?>"><?php echo $roleName; ?></span>
                        <span class="shift-pill"><?php echo $shiftLabel; ?></span>
                    </div>
                </div>
            </div>

            <a href="dashboard.php?logout=1" class="btn-signout" title="ออกจากระบบ">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span class="nav-label">ออกจากระบบ</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="main-content">
        <header class="page-header">
            <h1 class="page-title">ระบบตรวจสอบสินค้าคงคลัง</h1>
            <p class="page-subtitle">กรุณาเลือกรายการเมนูเพื่อดำเนินการต่อ</p>
        </header>

        <div class="dashboard-grid">
            <a href="set_sterile.php" class="grid-card">
                <svg class="grid-card-icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                <span class="card-number-badge">01</span>
                <h2 class="card-title-text">SET sterile</h2>
                <p class="card-desc-text">ตรวจนับและบันทึก Stock เซ็ตเครื่องมือปลอดเชื้อ</p>
            </a>

            <a href="equipment_sterile.php" class="grid-card">
                <svg class="grid-card-icon" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                <span class="card-number-badge">02</span>
                <h2 class="card-title-text">เครื่องมือ sterile</h2>
                <p class="card-desc-text">ตรวจนับเครื่องมือผ่าตัดรายชิ้น</p>
            </a>

            <a href="equipment_general.php" class="grid-card">
                <svg class="grid-card-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <span class="card-number-badge">03</span>
                <h2 class="card-title-text">อุปกรณ์</h2>
                <p class="card-desc-text">ตรวจสอบเวชภัณฑ์และอุปกรณ์การแพทย์</p>
            </a>

            <div class="grid-card disabled">
                <svg class="grid-card-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span class="card-number-badge">04</span>
                <h2 class="card-title-text">ตรวจสอบ</h2>
                <p class="card-desc-text">ดูประวัติการบันทึกข้อมูลทุกเวร</p>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const collapsed = sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
}

// Restore state
if (localStorage.getItem('sidebarCollapsed') === '1') {
    document.getElementById('sidebar').classList.add('collapsed');
}
</script>
</body>
</html>
