<?php
// equipment_general.php
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

$shiftConfig = [
    'day' => [
        'name'        => 'เวรเช้า',
        'table'       => 'day_check_equipment_general',
        'prefix'      => 'd',
        'prev_name'   => 'เวรดึก (เวรก่อนหน้า)',
        'prev_table'  => 'night_check_equipment_general',
        'prev_prefix' => 'n'
    ],
    'eve' => [
        'name'        => 'เวรบ่าย',
        'table'       => 'eve_check_equipment_general',
        'prefix'      => 'e',
        'prev_name'   => 'เวรเช้า (เวรก่อนหน้า)',
        'prev_table'  => 'day_check_equipment_general',
        'prev_prefix' => 'd'
    ],
    'night' => [
        'name'        => 'เวรดึก',
        'table'       => 'night_check_equipment_general',
        'prefix'      => 'n',
        'prev_name'   => 'เวรบ่าย (เวรก่อนหน้า)',
        'prev_table'  => 'eve_check_equipment_general',
        'prev_prefix' => 'e'
    ]
];

$config = $shiftConfig[$currentShift];

// Fetch catalog
$catalogStmt = $pdo->query("SELECT * FROM `stock_equipment_general` ORDER BY `id` ASC");
$catalog = $catalogStmt->fetchAll();

// Fetch previous shift data
$prevData    = [];
$prevTable   = $config['prev_table'];
$prevPrefix  = $config['prev_prefix'];

try {
    $prevQuery = "
        SELECT t1.* FROM `{$prevTable}` t1
        INNER JOIN (
            SELECT `equipment_general_name`, MAX(`id`) as max_id
            FROM `{$prevTable}`
            GROUP BY `equipment_general_name`
        ) t2 ON t1.`id` = t2.max_id
    ";
    foreach ($pdo->query($prevQuery)->fetchAll() as $row) {
        $prevData[$row['equipment_general_name']] = [
            'balance'  => (int)$row[$prevPrefix . '_balance'],
            'supply'   => (int)$row[$prevPrefix . '_supply'],
            'borrow'   => (int)$row[$prevPrefix . '_borrow'],
            'borrower' => $row[$prevPrefix . '_borrower']
        ];
    }
} catch (PDOException $e) { /* empty table – defaults to 0 */ }

// Fetch current shift data (if any) within the current shift window
$currentData = [];
$currentTable = $config['table'];
$p            = $config['prefix'];
$range        = get_current_shift_range($currentShift);
$startTime    = $range['start'];
$endTime      = $range['end'];

try {
    $currentQuery = "
        SELECT * FROM `{$currentTable}`
        WHERE `{$p}_timestamp` >= ? AND `{$p}_timestamp` < ?
    ";
    $stmt = $pdo->prepare($currentQuery);
    $stmt->execute([$startTime, $endTime]);
    foreach ($stmt->fetchAll() as $row) {
        $currentData[$row['equipment_general_name']] = [
            'id'       => (int)$row['id'],
            'balance'  => (int)$row[$p . '_balance'],
            'supply'   => (int)$row[$p . '_supply'],
            'borrow'   => (int)$row[$p . '_borrow'],
            'borrower' => $row[$p . '_borrower']
        ];
    }
} catch (PDOException $e) { /* empty or error */ }

// Handle form submission
$saveSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_stock') {
    $balances  = $_POST['balance']  ?? [];
    $supplies  = $_POST['supply']   ?? [];
    $borrows   = $_POST['borrow']   ?? [];
    $borrowers = $_POST['borrower'] ?? [];

    $checkerName  = mb_substr($user['name'] . ' ' . $user['surname'], 0, 25, 'UTF-8');

    $pdo->beginTransaction();
    try {
        $insertSql  = "INSERT INTO `{$currentTable}` (`equipment_general_name`,`{$p}_balance`,`{$p}_supply`,`{$p}_borrow`,`{$p}_borrower`,`{$p}_checker`) VALUES (?,?,?,?,?,?)";
        $updateSql  = "UPDATE `{$currentTable}` SET `{$p}_balance` = ?, `{$p}_supply` = ?, `{$p}_borrow` = ?, `{$p}_borrower` = ?, `{$p}_checker` = ? WHERE `id` = ?";
        
        $insertStmt = $pdo->prepare($insertSql);
        $updateStmt = $pdo->prepare($updateSql);

        foreach ($catalog as $item) {
            $name = $item['equipment_general_name'];
            $max  = (int)$item['total_qty'];
            $bal  = min((int)($balances[$name]  ?? 0), $max);
            $sup  = min((int)($supplies[$name]  ?? 0), $max);
            $bor  = min((int)($borrows[$name]   ?? 0), $max);
            $borr = mb_substr(trim($borrowers[$name] ?? ''), 0, 10, 'UTF-8');
            if ($borr === '') $borr = null;

            if (isset($currentData[$name])) {
                // Update existing record for the active shift session
                $updateStmt->execute([$bal, $sup, $bor, $borr, $checkerName, $currentData[$name]['id']]);
            } else {
                // Insert a new record
                $insertStmt->execute([$name, $bal, $sup, $bor, $borr, $checkerName]);
            }
        }

        $pdo->commit();
        $_SESSION['save_success'] = true;
        header('Location: equipment_general.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $saveError = $e->getMessage();
    }
}

if (isset($_SESSION['save_success'])) {
    $saveSuccess = true;
    unset($_SESSION['save_success']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อุปกรณ์ | Stock Checking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-container">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">

        <button class="sidebar-toggle" id="sidebarToggle" title="ย่อ/ขยายเมนู" onclick="toggleSidebar()">
            <svg viewBox="0 0 24 24" class="toggle-icon"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="sidebar-top">
            <div class="sidebar-header">
                <div class="brand-text">
                    <h2 class="sidebar-title">STOCK CHECKING</h2>
                    <p class="sidebar-subtitle">ระบบนับสต็อก</p>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="nav-link" title="หน้าหลัก">
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
                <a href="equipment_general.php" class="nav-link active" title="อุปกรณ์">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <span class="nav-label">อุปกรณ์</span>
                </a>
                <a href="#" class="nav-link disabled" title="ตรวจสอบ">
                    <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span class="nav-label">ตรวจสอบ</span>
                </a>
            </nav>
        </div>

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
            <div>
                <h1 class="page-title">อุปกรณ์</h1>
                <p class="page-subtitle">
                    ตรวจนับยอด Stock &mdash;
                    <strong><?php echo htmlspecialchars($config['name']); ?> (เวรปัจจุบัน)</strong>
                </p>
            </div>
            <div class="page-header-meta">
                <span class="shift-pill large"><?php echo $shiftLabel; ?></span>
            </div>
        </header>

        <?php if ($saveSuccess): ?>
            <div class="alert-banner success">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="20 6 9 17 4 12"/></svg>
                บันทึกยอดตรวจนับเวรปัจจุบันเรียบร้อยแล้ว
            </div>
        <?php endif; ?>
        <?php if (isset($saveError)): ?>
            <div class="alert-banner error">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                เกิดข้อผิดพลาด: <?php echo htmlspecialchars($saveError); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="equipment_general.php" id="stockForm">
            <input type="hidden" name="action" value="save_stock">

            <div class="table-container">
                <table class="flat-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 280px;">
                        <col style="width: 80px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 85px;">
                        <col style="width: 85px;">
                        <col style="width: 85px;">
                        <col style="width: 150px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th rowspan="2" class="th-center col-no">ลำดับ</th>
                            <th rowspan="2" class="col-name">ชื่ออุปกรณ์</th>
                            <th rowspan="2" class="th-center col-stock">STOCK</th>
                            <th colspan="3" class="th-center th-prev"><?php echo htmlspecialchars($config['prev_name']); ?></th>
                            <th colspan="4" class="th-center th-current"><?php echo htmlspecialchars($config['name']); ?> &mdash; เวรปัจจุบัน</th>
                        </tr>
                        <tr>
                            <!-- prev cols -->
                            <th class="th-center th-prev sub">คงเหลือ</th>
                            <th class="th-center th-prev sub">ส่ง Supply</th>
                            <th class="th-center th-prev sub">ติด/ยืม</th>
                            <!-- current cols -->
                            <th class="th-center th-current sub">คงเหลือ</th>
                            <th class="th-center th-current sub">ส่ง Supply</th>
                            <th class="th-center th-current sub">ติด/ยืม</th>
                            <th class="th-current sub">Ward/หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $index = 1; foreach ($catalog as $item):
                        $name  = $item['equipment_general_name'];
                        $maxQ  = (int)$item['total_qty'];
                        $pBal  = $prevData[$name]['balance']  ?? 0;
                        $pSup  = $prevData[$name]['supply']   ?? 0;
                        $pBor  = $prevData[$name]['borrow']   ?? 0;
                        $pBorr = $prevData[$name]['borrower'] ?? '';
                        $cBal  = $currentData[$name]['balance']  ?? 0;
                        $cSup  = $currentData[$name]['supply']   ?? 0;
                        $cBor  = $currentData[$name]['borrow']   ?? 0;
                        $cBorr = $currentData[$name]['borrower'] ?? '';
                    ?>
                        <tr class="stock-row" data-set-name="<?php echo htmlspecialchars($name); ?>" data-total="<?php echo $maxQ; ?>">
                            <td class="td-center"><?php echo $index++; ?></td>
                            <td class="td-name">
                                <div class="set-name-text"><?php echo htmlspecialchars($name); ?></div>
                            </td>
                            <td class="td-center td-stock"><?php echo $maxQ; ?></td>

                            <!-- previous shift -->
                            <td class="td-center td-prev"><?php echo $pBal; ?></td>
                            <td class="td-center td-prev"><?php echo $pSup; ?></td>
                            <td class="td-center td-prev">
                                <?php echo $pBor; ?>
                                <?php if ($pBor > 0 && !empty($pBorr)): ?>
                                    <div class="borrow-note"><?php echo htmlspecialchars($pBorr); ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- current shift inputs -->
                            <td class="td-center td-current">
                                <input type="number" name="balance[<?php echo htmlspecialchars($name); ?>]"
                                    class="table-input-number" min="0" max="<?php echo $maxQ; ?>"
                                    value="<?php echo $cBal; ?>" required
                                    data-max="<?php echo $maxQ; ?>"
                                    oninput="clampValue(this)">
                            </td>
                            <td class="td-center td-current">
                                <input type="number" name="supply[<?php echo htmlspecialchars($name); ?>]"
                                    class="table-input-number" min="0" max="<?php echo $maxQ; ?>"
                                    value="<?php echo $cSup; ?>" required
                                    data-max="<?php echo $maxQ; ?>"
                                    oninput="clampValue(this)">
                            </td>
                            <td class="td-center td-current">
                                <input type="number" name="borrow[<?php echo htmlspecialchars($name); ?>]"
                                    class="table-input-number" min="0" max="<?php echo $maxQ; ?>"
                                    value="<?php echo $cBor; ?>" required
                                    data-max="<?php echo $maxQ; ?>"
                                    oninput="clampValue(this)">
                            </td>
                            <td class="td-current">
                                <input type="text" name="borrower[<?php echo htmlspecialchars($name); ?>]"
                                    class="table-input-text" placeholder="Ward / หมายเหตุ"
                                    value="<?php echo htmlspecialchars($cBorr); ?>"
                                    maxlength="10">
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions-bar">
                <div class="actions-left" style="display: flex; gap: 12px;">
                    <a href="dashboard.php" class="btn-flat-secondary">← ย้อนกลับ</a>
                    <button type="button" class="btn-flat-danger" onclick="clearDraft()">ล้างค่า</button>
                </div>
                <button type="submit" class="btn-flat" style="width:auto;padding:0 32px;">
                    บันทึกยอด Stock
                </button>
            </div>
        </form>
    </main>
</div>

<script>
// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const collapsed = sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
}
if (localStorage.getItem('sidebarCollapsed') === '1') {
    document.getElementById('sidebar').classList.add('collapsed');
}

// Clamp number input to its max (= total_qty)
function clampValue(input) {
    const max = parseInt(input.dataset.max, 10);
    const val = parseInt(input.value, 10);
    if (!isNaN(val) && val > max) {
        input.value = max;
    }
    if (!isNaN(val) && val < 0) {
        input.value = 0;
    }
}

// Draft persistence variables
const USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
const CURRENT_SHIFT = <?php echo json_encode($currentShift); ?>;
const DRAFT_KEY = 'equipment_general_draft_' + USER_ID + '_' + CURRENT_SHIFT;

// Detect mobile or tablet device
function detectMobileOrTablet() {
    const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    const isLowWidth = window.matchMedia("(max-width: 1024px)").matches;
    const isUserAgentMobile = /Mobi|Android|iPhone|iPad|Macintosh/i.test(navigator.userAgent);
    return hasTouch || isLowWidth || (isUserAgentMobile && hasTouch);
}

// Convert numbers inputs to select dropdowns on desktop
function setupInputs() {
    const isMobile = detectMobileOrTablet();
    if (!isMobile) {
        document.querySelectorAll('.table-input-number').forEach(input => {
            const max = parseInt(input.dataset.max, 10) || 0;
            const name = input.name;
            const val = input.value;
            
            const select = document.createElement('select');
            select.name = name;
            select.className = 'table-input-select';
            select.required = input.required;
            select.dataset.max = max;
            
            for (let i = 0; i <= max; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = i;
                if (i == val) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            }
            
            input.parentNode.replaceChild(select, input);
        });
    }
}

// Save inputs to localStorage
function saveDraft() {
    const data = {};
    document.querySelectorAll('#stockForm input[name^="balance"], #stockForm select[name^="balance"], #stockForm input[name^="supply"], #stockForm select[name^="supply"], #stockForm input[name^="borrow"], #stockForm select[name^="borrow"], #stockForm input[name^="borrower"]').forEach(input => {
        data[input.name] = input.value;
    });
    localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
}

// Load inputs from localStorage
function loadDraft() {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (raw) {
        try {
            const data = JSON.parse(raw);
            for (const [name, val] of Object.entries(data)) {
                const el = document.querySelector(`[name="${CSS.escape(name)}"]`);
                if (el) {
                    el.value = val;
                }
            }
        } catch (e) {
            console.error('Error parsing draft', e);
        }
    }
}

// Row validation helper & limit enforcer
function updateInputLimit(el, maxVal) {
    if (el.tagName === 'SELECT') {
        Array.from(el.options).forEach(opt => {
            const optVal = parseInt(opt.value, 10);
            if (optVal > maxVal) {
                opt.disabled = true;
                opt.style.display = 'none';
            } else {
                opt.disabled = false;
                opt.style.display = '';
            }
        });
        if (parseInt(el.value, 10) > maxVal) {
            el.value = maxVal;
        }
    } else {
        el.max = maxVal;
        if (parseInt(el.value, 10) > maxVal) {
            el.value = maxVal;
        }
    }
}

function enforceClamping(tr) {
    const total = parseInt(tr.dataset.total, 10);
    const balInput = tr.querySelector('[name^="balance"]');
    const supInput = tr.querySelector('[name^="supply"]');
    const borInput = tr.querySelector('[name^="borrow"]');
    
    if (!balInput || !supInput || !borInput) return;
    
    const bal = parseInt(balInput.value, 10) || 0;
    const sup = parseInt(supInput.value, 10) || 0;
    const bor = parseInt(borInput.value, 10) || 0;
    
    // Limits
    const maxBal = total - (sup + bor);
    const maxSup = total - (bal + bor);
    const maxBor = total - (bal + sup);
    
    updateInputLimit(balInput, maxBal < 0 ? 0 : maxBal);
    updateInputLimit(supInput, maxSup < 0 ? 0 : maxSup);
    updateInputLimit(borInput, maxBor < 0 ? 0 : maxBor);
}

function checkRowBalance(tr) {
    const total = parseInt(tr.dataset.total, 10);
    const balInput = tr.querySelector('[name^="balance"]');
    const supInput = tr.querySelector('[name^="supply"]');
    const borInput = tr.querySelector('[name^="borrow"]');
    
    if (!balInput || !supInput || !borInput) return;
    
    const bal = parseInt(balInput.value, 10) || 0;
    const sup = parseInt(supInput.value, 10) || 0;
    const bor = parseInt(borInput.value, 10) || 0;
    const sum = bal + sup + bor;
    
    if (sum <= total) {
        tr.classList.remove('unbalanced');
        tr.classList.add('balanced');
    } else {
        tr.classList.remove('balanced');
        tr.classList.add('unbalanced');
    }
}

function updateAllRows() {
    document.querySelectorAll('.stock-row').forEach(tr => {
        enforceClamping(tr);
        checkRowBalance(tr);
    });
}

function handleFormInput(e) {
    const input = e.target;
    const tr = input.closest('.stock-row');
    if (tr) {
        enforceClamping(tr);
        checkRowBalance(tr);
    }
    saveDraft();
}

// Clear all inputs and localStorage draft
function clearDraft() {
    if (confirm('คุณต้องการล้างค่าที่กรอกไว้ทั้งหมดใช่หรือไม่?')) {
        localStorage.removeItem(DRAFT_KEY);
        document.querySelectorAll('#stockForm input[name^="balance"], #stockForm select[name^="balance"], #stockForm input[name^="supply"], #stockForm select[name^="supply"], #stockForm input[name^="borrow"], #stockForm select[name^="borrow"]').forEach(input => {
            input.value = 0;
        });
        document.querySelectorAll('#stockForm input[name^="borrower"]').forEach(input => {
            input.value = '';
        });
        updateAllRows();
    }
}

// Attach listeners
document.addEventListener('DOMContentLoaded', () => {
    loadDraft();
    setupInputs();
    updateAllRows();
    
    const form = document.getElementById('stockForm');
    if (form) {
        form.addEventListener('input', handleFormInput);
        form.addEventListener('change', handleFormInput);
        form.addEventListener('submit', (e) => {
            let allBalanced = true;
            let firstUnbalancedRow = null;
            
            document.querySelectorAll('.stock-row').forEach(tr => {
                const total = parseInt(tr.dataset.total, 10);
                const bal = parseInt(tr.querySelector('[name^="balance"]').value, 10) || 0;
                const sup = parseInt(tr.querySelector('[name^="supply"]').value, 10) || 0;
                const bor = parseInt(tr.querySelector('[name^="borrow"]').value, 10) || 0;
                
                if (bal + sup + bor > total) {
                    allBalanced = false;
                    if (!firstUnbalancedRow) {
                        firstUnbalancedRow = tr;
                    }
                }
            });
            
            if (!allBalanced) {
                e.preventDefault();
                alert('กรุณาตรวจสอบยอดรวมของอุปกรณ์ให้ถูกต้อง\n\nยอดคงเหลือ + ส่ง Supply + ติด/ยืม จะต้องเท่ากับหรือน้อยกว่า ยอด STOCK!');
                if (firstUnbalancedRow) {
                    firstUnbalancedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const focusEl = firstUnbalancedRow.querySelector('[name^="balance"]');
                    if (focusEl) focusEl.focus();
                }
            } else {
                localStorage.removeItem(DRAFT_KEY);
            }
        });
    }
});
</script>

</body>
</html>
