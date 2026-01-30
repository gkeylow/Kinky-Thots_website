<?php
$pageTitle = 'Verify Email - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
    body {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .verify-main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        margin-top: 80px;
    }
    .verify-container { width: 100%; max-width: 480px; }
    .verify-card {
        background: #222;
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        text-align: center;
    }
    .verify-icon { width: 80px; height: 80px; margin: 0 auto 1.5rem; }
    .verify-icon.loading svg { animation: spin 1s linear infinite; }
    .verify-icon.success svg { color: #2ecc71; }
    .verify-icon.error svg { color: #e74c3c; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .verify-title { color: #f805a7; font-size: 1.75rem; margin-bottom: 1rem; }
    .verify-title.success { color: #2ecc71; }
    .verify-title.error { color: #e74c3c; }
    .verify-message { color: #ccc; font-size: 1.1rem; margin-bottom: 1.5rem; line-height: 1.6; }
    .verify-btn {
        display: inline-block;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .verify-btn:hover { transform: scale(1.02); box-shadow: 0 4px 20px rgba(248, 5, 167, 0.4); }
    .verify-btn.secondary { background: transparent; border: 2px solid #f805a7; margin-top: 1rem; }
    .verify-btn.secondary:hover { background: rgba(248, 5, 167, 0.1); }
    .resend-section { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #333; }
    .resend-section p { color: #888; margin-bottom: 1rem; }
    .resend-link { color: #0bd0f3; cursor: pointer; text-decoration: underline; }
    .resend-link:hover { color: #f805a7; }
    .resend-link.disabled { color: #666; cursor: not-allowed; text-decoration: none; }
    .resend-success { color: #2ecc71; margin-top: 0.5rem; display: none; }
    .resend-error { color: #e74c3c; margin-top: 0.5rem; display: none; }
    .state { display: none; }
    .state.active { display: block; }
    @media (max-width: 768px) { .verify-card { padding: 1.5rem; } .verify-title { font-size: 1.5rem; } }
';

include __DIR__ . '/includes/header.php';
?>

<main class="verify-main">
    <div class="verify-container">
        <div class="verify-card">
            <!-- Loading State -->
            <div class="state active" id="stateLoading">
                <div class="verify-icon loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f805a7" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.3"/>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="verify-title">Verifying Email...</h1>
                <p class="verify-message">Please wait while we verify your email address.</p>
            </div>

            <!-- Success State -->
            <div class="state" id="stateSuccess">
                <div class="verify-icon success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 class="verify-title success">Email Verified!</h1>
                <p class="verify-message">Your email has been verified successfully. You are now logged in.</p>
                <a href="/profile.php" class="verify-btn">Go to Profile</a>
                <br>
                <a href="/premium-content.php" class="verify-btn secondary">Browse Content</a>
            </div>

            <!-- Error State -->
            <div class="state" id="stateError">
                <div class="verify-icon error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="verify-title error">Verification Failed</h1>
                <p class="verify-message" id="errorMessage">The verification link is invalid or has expired.</p>
                <a href="/login.php" class="verify-btn">Go to Login</a>

                <div class="resend-section">
                    <p>Need a new verification link?</p>
                    <input type="email" id="resendEmail" placeholder="Enter your email" style="width: 100%; max-width: 280px; padding: 0.75rem 1rem; background: #181818; border: 2px solid #333; border-radius: 8px; color: #fff; font-size: 1rem; margin-bottom: 0.75rem;">
                    <br>
                    <span class="resend-link" id="resendVerification">Send new verification email</span>
                    <p class="resend-success" id="resendSuccess"></p>
                    <p class="resend-error" id="resendError"></p>
                </div>
            </div>

            <!-- No Token State -->
            <div class="state" id="stateNoToken">
                <div class="verify-icon error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4M12 16h.01" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="verify-title">No Verification Token</h1>
                <p class="verify-message">This page requires a verification link from your email. Please check your inbox for the verification email we sent you.</p>
                <a href="/login.php" class="verify-btn">Go to Login</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function() {
    const API_URL = window.location.hostname === 'localhost' ? 'http://localhost:3002' : '';

    const stateLoading = document.getElementById('stateLoading');
    const stateSuccess = document.getElementById('stateSuccess');
    const stateError = document.getElementById('stateError');
    const stateNoToken = document.getElementById('stateNoToken');

    function showState(state) {
        [stateLoading, stateSuccess, stateError, stateNoToken].forEach(s => s.classList.remove('active'));
        state.classList.add('active');
    }

    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
        showState(stateNoToken);
        return;
    }

    async function verifyEmail() {
        try {
            const res = await fetch(`${API_URL}/api/auth/verify-email`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });

            const data = await res.json();

            if (!res.ok) {
                document.getElementById('errorMessage').textContent = data.error || 'Verification failed. Please try again.';
                showState(stateError);
                return;
            }

            localStorage.setItem('kt_auth_token', data.token);
            localStorage.setItem('kt_auth_user', JSON.stringify(data.user));
            showState(stateSuccess);

            const redirectUrl = urlParams.get('redirect');
            if (redirectUrl) {
                setTimeout(() => { window.location.href = redirectUrl; }, 2000);
            }
        } catch (err) {
            console.error('Verification error:', err);
            document.getElementById('errorMessage').textContent = 'Connection error. Please try again.';
            showState(stateError);
        }
    }

    verifyEmail();

    let resendCooldown = false;
    document.getElementById('resendVerification').addEventListener('click', async () => {
        if (resendCooldown) return;

        const email = document.getElementById('resendEmail').value;
        if (!email) {
            document.getElementById('resendError').textContent = 'Please enter your email address';
            document.getElementById('resendError').style.display = 'block';
            return;
        }

        const resendLink = document.getElementById('resendVerification');
        const successDiv = document.getElementById('resendSuccess');
        const errorDiv = document.getElementById('resendError');

        resendLink.classList.add('disabled');
        resendLink.textContent = 'Sending...';
        successDiv.style.display = 'none';
        errorDiv.style.display = 'none';

        try {
            const res = await fetch(`${API_URL}/api/auth/resend-verification`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });

            const data = await res.json();

            if (res.ok) {
                successDiv.textContent = 'Verification email sent! Please check your inbox.';
                successDiv.style.display = 'block';
                resendCooldown = true;
                let countdown = 300;
                const updateCountdown = setInterval(() => {
                    countdown--;
                    resendLink.textContent = `Resend in ${Math.floor(countdown / 60)}:${(countdown % 60).toString().padStart(2, '0')}`;
                    if (countdown <= 0) {
                        clearInterval(updateCountdown);
                        resendCooldown = false;
                        resendLink.classList.remove('disabled');
                        resendLink.textContent = 'Send new verification email';
                    }
                }, 1000);
            } else {
                errorDiv.textContent = data.error || 'Failed to send email';
                errorDiv.style.display = 'block';
                resendLink.classList.remove('disabled');
                resendLink.textContent = 'Send new verification email';
            }
        } catch (err) {
            errorDiv.textContent = 'Connection error. Please try again.';
            errorDiv.style.display = 'block';
            resendLink.classList.remove('disabled');
            resendLink.textContent = 'Send new verification email';
        }
    });
})();
</script>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
