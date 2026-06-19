<?php
// index.php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$loginFailed = false;
$submittedOt = '';

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ot = isset($_POST['ot']) ? trim($_POST['ot']) : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
    
    // Save for field persistence
    $submittedOt = $ot;

    // Validate OT input pattern (4-5 digits)
    if (preg_match('/^\d{4,5}$/', $ot)) {
        try {
            // Retrieve user from database
            $stmt = $pdo->prepare("SELECT * FROM `user` WHERE `ot` = ?");
            $stmt->execute([$ot]);
            $user = $stmt->fetch();

            // Direct comparison of plaintext password as per user requirements
            if ($user && $user['pass'] === $pass) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['shift'] = calculate_shift();
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                header('Location: dashboard.php');
                exit;
            } else {
                $loginFailed = true;
            }
        } catch (PDOException $e) {
            // Log database errors silently in production, or handle here
            $loginFailed = true;
        }
    } else {
        $loginFailed = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | Stock Checking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">STOCK CHECKING</h1>
                <p class="login-subtitle">ระบบเช็คสินค้าคงคลัง</p>
            </div>

            <!-- Login Form -->
            <form id="login-form" method="POST" action="index.php" novalidate>
                <!-- OT Field -->
                <div class="form-group">
                    <label for="ot" class="form-label">เลข OT (รหัสพนักงาน)</label>
                    <div class="input-container-flat">
                        <input 
                            type="text" 
                            id="ot" 
                            name="ot" 
                            class="form-control-flat" 
                            placeholder="ระบุตัวเลข 4 - 5 หลัก" 
                            value="<?php echo htmlspecialchars($submittedOt); ?>"
                            maxlength="5"
                            required
                            autocomplete="off"
                            inputmode="numeric"
                        >
                    </div>
                    <div id="ot-hint" class="validation-hint">ระบุตัวเลข 4 - 5 หลัก</div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="pass" class="form-label">รหัสผ่าน (Password)</label>
                    <div class="input-container-flat">
                        <input 
                            type="password" 
                            id="pass" 
                            name="pass" 
                            class="form-control-flat" 
                            placeholder="รหัสผ่านไม่เกิน 10 ตัวอักษร" 
                            maxlength="10"
                            required
                        >
                        <!-- Text-based clean password toggle -->
                        <button type="button" id="toggle-password" class="password-toggle-btn-flat" title="แสดง/ซ่อน รหัสผ่าน">
                            <span id="toggle-icon">[แสดง]</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-flat">
                    เข้าสู่ระบบ
                </button>
            </form>
        </div>
    </div>

    <!-- Custom Modal Popup Alert (Monochrome Flat Style) -->
    <div id="error-modal" class="modal-overlay">
        <div class="flat-modal-box">
            <h2 id="modal-title" class="flat-modal-title">เกิดข้อผิดพลาด</h2>
            <p id="modal-desc" class="flat-modal-desc">ไม่พบผู้ใช้งาน กรุณาตรวจสอบผู้ใช้งาน</p>
            <div class="flat-modal-actions">
                <button type="button" class="btn-flat-modal" onclick="closeModal()">ตกลง</button>
            </div>
        </div>
    </div>

    <script src="login.js"></script>

    <!-- Trigger Modal if Login Failed -->
    <?php if ($loginFailed): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showErrorModal('ไม่พบผู้ใช้งาน', 'กรุณาตรวจสอบผู้ใช้งาน');
            });
        </script>
    <?php endif; ?>
</body>
</html>
