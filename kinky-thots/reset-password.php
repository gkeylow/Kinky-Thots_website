<?php
$pageTitle = 'Reset Password - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
    body {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .reset-main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        margin-top: 80px;
    }
    .reset-container { width: 100%; max-width: 420px; }
    .reset-card {
        background: #222;
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }
    .reset-card h1 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
    }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; color: #b0b0b0; font-size: 0.9rem; }
    .form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        background: #181818;
        border: 2px solid #333;
        border-radius: 8px;
        color: #fff;
        font-size: 1rem;
    }
    .form-group input:focus { outline: none; border-color: #0bd0f3; }
    .reset-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .reset-btn:hover { transform: scale(1.02); box-shadow: 0 4px 20px rgba(248, 5, 167, 0.4); }
    .reset-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
    .message.error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
    .message.success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
    .back-link { display: block; text-align: center; margin-top: 1rem; color: #0bd0f3; text-decoration: none; }
    .back-link:hover { color: #f805a7; }
    .password-requirements { font-size: 0.8rem; color: #888; margin-top: 0.5rem; }
    @media (max-width: 768px) { .reset-card { padding: 1.5rem; } }
';

include __DIR__ . '/includes/header.php';
?>

<main class="reset-main">
    <div class="reset-container">
        <div class="reset-card">
            <h1>Reset Password</h1>
            <div id="message" class="message" style="display: none;"></div>
            <form id="resetForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" placeholder="Enter new password" required minlength="8">
                    <p class="password-requirements">Min 8 characters, uppercase, lowercase, and number</p>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" placeholder="Confirm new password" required>
                </div>
                <button type="submit" id="submitBtn" class="reset-btn">Reset Password</button>
            </form>
            <a href="/login.php" class="back-link">Back to Login</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    const form = document.getElementById('resetForm');
    const message = document.getElementById('message');
    const submitBtn = document.getElementById('submitBtn');
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
        message.textContent = 'Invalid reset link. Please request a new password reset.';
        message.className = 'message error';
        message.style.display = 'block';
        form.style.display = 'none';
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            message.textContent = 'Passwords do not match';
            message.className = 'message error';
            message.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Resetting...';
        message.style.display = 'none';

        try {
            const response = await fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, password })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                message.textContent = 'Password reset successful! Redirecting to login...';
                message.className = 'message success';
                message.style.display = 'block';
                form.style.display = 'none';
                setTimeout(() => { window.location.href = '/login.php'; }, 2000);
            } else {
                message.textContent = data.error || 'Failed to reset password';
                message.className = 'message error';
                message.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            }
        } catch (err) {
            message.textContent = 'Network error. Please try again.';
            message.className = 'message error';
            message.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Reset Password';
        }
    });
</script>

<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
