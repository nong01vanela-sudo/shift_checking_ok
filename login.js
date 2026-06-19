// login.js
document.addEventListener('DOMContentLoaded', () => {
    const otInput = document.getElementById('ot');
    const passInput = document.getElementById('pass');
    const toggleBtn = document.getElementById('toggle-password');
    const toggleIcon = document.getElementById('toggle-icon');
    const otHint = document.getElementById('ot-hint');
    const loginForm = document.getElementById('login-form');
    
    // Persistent password visibility state
    let isPasswordVisiblePersistent = false;

    // 1. OT Input restriction: Only numbers, 4-5 digits
    if (otInput) {
        otInput.addEventListener('input', (e) => {
            // Strip any non-digit characters
            let val = e.target.value.replace(/\D/g, '');
            
            // Limit length to 5 digits
            if (val.length > 5) {
                val = val.substring(0, 5);
            }
            
            e.target.value = val;
            
            // Update validation UI helper
            if (val.length === 0) {
                otHint.textContent = 'กรุณากรอกเลข OT 4 - 5 หลัก';
                otHint.className = 'validation-hint';
            } else if (val.length < 4) {
                otHint.textContent = 'เลข OT ต้องมีอย่างน้อย 4 หลัก (ปัจจุบัน: ' + val.length + ' หลัก)';
                otHint.className = 'validation-hint invalid';
            } else {
                otHint.textContent = 'เลข OT ถูกต้อง (' + val.length + ' หลัก)';
                otHint.className = 'validation-hint valid';
            }
        });
    }

    // Text labels for minimalist flat design
    const eyeIconOpen = '[ซ่อน]';
    const eyeIconClosed = '[แสดง]';

    // Function to set password visibility type
    function setPasswordVisibility(visible) {
        if (visible) {
            passInput.type = 'text';
            toggleIcon.textContent = eyeIconOpen;
        } else {
            passInput.type = 'password';
            toggleIcon.textContent = eyeIconClosed;
        }
    }

    if (toggleBtn && passInput) {
        // Toggle on Click
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent focus loss or form triggering
            isPasswordVisiblePersistent = !isPasswordVisiblePersistent;
            setPasswordVisibility(isPasswordVisiblePersistent);
        });

        // Hover effect to temporarily show password
        toggleBtn.addEventListener('mouseenter', () => {
            if (!isPasswordVisiblePersistent) {
                setPasswordVisibility(true);
            }
        });

        toggleBtn.addEventListener('mouseleave', () => {
            if (!isPasswordVisiblePersistent) {
                setPasswordVisibility(false);
            }
        });
    }

    // Frontend pre-submit validation
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            const otVal = otInput.value.trim();
            if (otVal.length < 4 || otVal.length > 5) {
                e.preventDefault();
                showErrorModal('กรุณาตรวจสอบข้อมูล', 'เลข OT ต้องเป็นตัวเลขความยาว 4 หรือ 5 หลักเท่านั้น');
            }
        });
    }
});

// Modal Dialog control functions
function showErrorModal(title, message) {
    const modal = document.getElementById('error-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalDesc = document.getElementById('modal-desc');
    
    if (modal && modalTitle && modalDesc) {
        modalTitle.textContent = title;
        modalDesc.textContent = message;
        modal.classList.add('active');
    }
}

function closeModal() {
    const modal = document.getElementById('error-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}
