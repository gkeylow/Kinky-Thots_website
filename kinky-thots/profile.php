<?php
$pageTitle = 'My Profile - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
        .profile-container {
            max-width: 800px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-header h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        .profile-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid rgba(11, 208, 243, 0.3);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .profile-card h2 {
            font-size: 1.25rem;
            color: #fff;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .profile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .profile-row:last-child {
            border-bottom: none;
        }
        .profile-label {
            color: #888;
            font-size: 0.9rem;
        }
        .profile-value {
            color: #fff;
            font-weight: 500;
        }
        .tier-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .tier-badge.free {
            background: #333;
            color: #888;
        }
        .tier-badge.plus {
            background: linear-gradient(135deg, #4ECDC4, #45B7D1);
            color: #000;
        }
        .tier-badge.premium {
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            color: #fff;
        }
        .tier-badge.vip {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
        }
        .status-active {
            color: #2ecc71;
        }
        .status-expired {
            color: #e74c3c;
        }
        .color-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .color-swatch {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
        }
        /* Avatar styles */
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }
        .avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 1rem;
        }
        .avatar-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(11, 208, 243, 0.5);
            background: #1a1a1a;
        }
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #333, #222);
            border: 3px solid rgba(11, 208, 243, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #666;
        }
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            cursor: pointer;
        }
        .avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        .avatar-overlay-text {
            color: #fff;
            font-size: 0.85rem;
            text-align: center;
        }
        .avatar-upload-input {
            display: none;
        }
        .avatar-actions {
            display: flex;
            gap: 0.5rem;
        }
        .avatar-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .avatar-btn-upload {
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            border: none;
            color: #fff;
        }
        .avatar-btn-delete {
            background: transparent;
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        .avatar-btn-delete:hover {
            background: rgba(231, 76, 60, 0.1);
        }
        /* Bio styles */
        .bio-textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(11, 208, 243, 0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.2s;
        }
        .bio-textarea:focus {
            outline: none;
            border-color: #0bd0f3;
        }
        .bio-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .bio-counter.warning {
            color: #f39c12;
        }
        .bio-counter.error {
            color: #e74c3c;
        }
        /* Security section styles */
        .security-section {
            margin-bottom: 1.5rem;
        }
        .security-section h3 {
            color: #0bd0f3;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .current-email {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .current-email span {
            color: #fff;
        }
        .security-divider {
            border: none;
            border-top: 1px solid rgba(11, 208, 243, 0.2);
            margin: 2rem 0;
        }
        .profile-form {
            margin-top: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            color: #b0b0b0;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(11, 208, 243, 0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #0bd0f3;
        }
        .form-input[type="color"] {
            width: 60px;
            height: 40px;
            padding: 4px;
            cursor: pointer;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #333;
        }
        .btn-secondary:hover {
            background: #444;
        }
        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        .subscription-cta {
            text-align: center;
            padding: 1.5rem;
            background: rgba(248, 5, 167, 0.05);
            border-radius: 12px;
            margin-top: 1rem;
        }
        .subscription-cta p {
            color: #888;
            margin-bottom: 1rem;
        }
        .not-logged-in {
            text-align: center;
            padding: 3rem;
        }
        .not-logged-in h2 {
            color: #fff;
            margin-bottom: 1rem;
        }
        .not-logged-in p {
            color: #888;
            margin-bottom: 1.5rem;
        }
        .logout-section {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .logout-btn {
            background: transparent;
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 0.75rem 2rem;
        }
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.1);
        }
        @media (max-width: 600px) {
            .profile-container { margin-top: 80px; }
            .profile-card { padding: 1.5rem; }
            .profile-row { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .form-row { flex-direction: column; }
        }
';

include 'includes/header.php';
?>

<main class="profile-container">
    <div class="profile-header">
        <h1>My Profile</h1>
    </div>

    <!-- Not logged in state -->
    <div id="notLoggedIn" class="profile-card not-logged-in" style="display: none;">
        <h2>Please Log In</h2>
        <p>You need to be logged in to view your profile.</p>
        <a href="/login.php" class="btn">Go to Login</a>
    </div>

    <!-- Profile content (shown when logged in) -->
    <div id="profileContent" style="display: none;">
        <!-- Alerts -->
        <div id="alertContainer"></div>

        <!-- Avatar Section -->
        <div class="profile-card">
            <div class="avatar-section">
                <div class="avatar-container">
                    <div class="avatar-placeholder" id="avatarPlaceholder">?</div>
                    <img src="" alt="Profile Picture" class="avatar-image" id="avatarImage" style="display: none;">
                    <label class="avatar-overlay" for="avatarInput">
                        <span class="avatar-overlay-text">Change<br>Photo</span>
                    </label>
                    <input type="file" id="avatarInput" class="avatar-upload-input" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <div class="avatar-actions">
                    <label for="avatarInput" class="avatar-btn avatar-btn-upload">Upload Photo</label>
                    <button type="button" class="avatar-btn avatar-btn-delete" id="deleteAvatarBtn" style="display: none;">Remove</button>
                </div>
            </div>
        </div>

        <!-- Account Info -->
        <div class="profile-card">
            <h2>Account Information</h2>
            <div class="profile-row">
                <span class="profile-label">Username</span>
                <span class="profile-value" id="profileUsername">-</span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Email</span>
                <span class="profile-value" id="profileEmail">-</span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Member Since</span>
                <span class="profile-value" id="profileCreated">-</span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Last Login</span>
                <span class="profile-value" id="profileLastLogin">-</span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Chat Color</span>
                <span class="profile-value color-preview">
                    <span class="color-swatch" id="colorSwatch"></span>
                    <span id="profileColor">-</span>
                </span>
            </div>
        </div>

        <!-- Bio Section -->
        <div class="profile-card">
            <h2>About Me</h2>
            <form id="bioForm" class="profile-form">
                <div class="form-group">
                    <label class="form-label" for="bioText">Bio</label>
                    <textarea class="bio-textarea" id="bioText" placeholder="Tell others about yourself..." maxlength="500"></textarea>
                    <div class="bio-counter"><span id="bioCount">0</span>/500</div>
                </div>
                <button type="submit" class="btn" id="bioSubmit">Save Bio</button>
            </form>
        </div>

        <!-- Subscription -->
        <div class="profile-card">
            <h2>Subscription</h2>
            <div class="profile-row">
                <span class="profile-label">Current Plan</span>
                <span class="profile-value"><span class="tier-badge" id="profileTier">FREE</span></span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Status</span>
                <span class="profile-value" id="profileStatus">-</span>
            </div>
            <div class="profile-row" id="expiresRow" style="display: none;">
                <span class="profile-label">Expires</span>
                <span class="profile-value" id="profileExpires">-</span>
            </div>
            <div class="subscription-cta" id="upgradeCta">
                <p>Upgrade your plan to unlock more content</p>
                <a href="/subscriptions.php" class="btn">View Plans</a>
            </div>
            <div id="cancelCta" style="display: none; text-align: center; margin-top: 1rem;">
                <button class="btn btn-secondary" id="cancelSubBtn">Cancel Subscription</button>
            </div>
        </div>

        <!-- Change Color -->
        <div class="profile-card">
            <h2>Customize Chat Color</h2>
            <form id="colorForm" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="newColor">Select Color</label>
                        <input type="color" class="form-input" id="newColor" value="#0bd0f3">
                    </div>
                    <button type="submit" class="btn" id="colorSubmit">Update Color</button>
                </div>
            </form>
        </div>

        <!-- Security Section -->
        <div class="profile-card" id="security">
            <h2>Security</h2>

            <!-- Change Email -->
            <div class="security-section">
                <h3>Change Email</h3>
                <p class="current-email">Current email: <span id="currentEmailDisplay">-</span></p>
                <form id="emailForm" class="profile-form">
                    <div class="form-group">
                        <label class="form-label" for="newEmail">New Email</label>
                        <input type="email" class="form-input" id="newEmail" required placeholder="Enter new email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="emailPassword">Current Password</label>
                        <input type="password" class="form-input" id="emailPassword" required placeholder="Verify with your password">
                    </div>
                    <button type="submit" class="btn" id="emailSubmit">Update Email</button>
                </form>
            </div>

            <hr class="security-divider">

            <!-- Change Password -->
            <div class="security-section" id="password-section">
                <h3>Change Password</h3>
                <form id="passwordForm" class="profile-form">
                    <div class="form-group">
                        <label class="form-label" for="currentPassword">Current Password</label>
                        <input type="password" class="form-input" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newPassword">New Password</label>
                        <input type="password" class="form-input" id="newPassword" required minlength="8"
                               placeholder="Min 8 chars, uppercase, lowercase, number">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm New Password</label>
                        <input type="password" class="form-input" id="confirmPassword" required>
                    </div>
                    <button type="submit" class="btn" id="passwordSubmit">Change Password</button>
                </form>
            </div>
        </div>

        <!-- Logout -->
        <div class="logout-section">
            <button class="btn logout-btn" id="logoutBtn">Log Out</button>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    const AUTH_TOKEN_KEY = 'kt_auth_token';
    const AUTH_USER_KEY = 'kt_auth_user';

    let currentUser = null;

    function getToken() {
        return localStorage.getItem(AUTH_TOKEN_KEY);
    }

    function getUser() {
        return JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null');
    }

    function setUser(user) {
        localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
    }

    function logout() {
        localStorage.removeItem(AUTH_TOKEN_KEY);
        localStorage.removeItem(AUTH_USER_KEY);
        window.location.href = '/index.php';
    }

    function showAlert(message, type = 'success') {
        const container = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        container.innerHTML = '';
        container.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return 'Never';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    async function loadProfile() {
        const token = getToken();
        const user = getUser();

        // Update nav
        const authTrigger = document.getElementById('authTrigger');
        if (authTrigger && user) {
            authTrigger.textContent = user.username;
        }

        if (!token || !user) {
            document.getElementById('notLoggedIn').style.display = 'block';
            document.getElementById('profileContent').style.display = 'none';
            return;
        }

        try {
            const response = await fetch('/api/auth/me', {
                headers: { 'Authorization': `Bearer ${token}` }
            });

            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    logout();
                    return;
                }
                throw new Error('Failed to load profile');
            }

            const data = await response.json();
            currentUser = data.user;

            // Update local storage with fresh data
            setUser(currentUser);

            // Display profile data
            document.getElementById('profileUsername').textContent = currentUser.username;
            document.getElementById('profileEmail').textContent = currentUser.email;
            document.getElementById('profileCreated').textContent = formatDate(currentUser.created_at);
            document.getElementById('profileLastLogin').textContent = formatDateTime(currentUser.last_login_at);
            document.getElementById('profileColor').textContent = currentUser.display_color || '#0bd0f3';
            document.getElementById('colorSwatch').style.backgroundColor = currentUser.display_color || '#0bd0f3';
            document.getElementById('newColor').value = currentUser.display_color || '#0bd0f3';

            // Display current email in security section
            document.getElementById('currentEmailDisplay').textContent = currentUser.email;

            // Display avatar
            updateAvatarDisplay(currentUser.avatar_url, currentUser.username);

            // Display bio
            const bioText = document.getElementById('bioText');
            bioText.value = currentUser.bio || '';
            updateBioCounter();

            // Subscription info
            const tier = currentUser.subscription_tier || 'free';
            const tierBadge = document.getElementById('profileTier');
            tierBadge.textContent = tier.toUpperCase();
            tierBadge.className = `tier-badge ${tier}`;

            const status = currentUser.subscription_status || 'active';
            const statusEl = document.getElementById('profileStatus');
            statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusEl.className = `profile-value status-${status === 'active' ? 'active' : 'expired'}`;

            // Show expiration for paid tiers
            if (tier !== 'free' && currentUser.subscription_expires_at) {
                document.getElementById('expiresRow').style.display = 'flex';
                document.getElementById('profileExpires').textContent = formatDate(currentUser.subscription_expires_at);
            }

            // Hide upgrade CTA for premium/vip
            if (tier === 'premium' || tier === 'vip') {
                document.getElementById('upgradeCta').style.display = 'none';
            }

            // Show cancel button for paid subscriptions with active status
            if (tier !== 'free' && tier !== 'vip' && status === 'active') {
                document.getElementById('cancelCta').style.display = 'block';
            }

            document.getElementById('notLoggedIn').style.display = 'none';
            document.getElementById('profileContent').style.display = 'block';

        } catch (err) {
            console.error('Profile load error:', err);
            showAlert('Failed to load profile data', 'error');
        }
    }

    // Color form handler
    document.getElementById('colorForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('colorSubmit');
        const color = document.getElementById('newColor').value;

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const response = await fetch('/api/auth/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({ display_color: color })
            });

            const data = await response.json();

            if (response.ok) {
                currentUser.display_color = color;
                setUser(currentUser);
                document.getElementById('profileColor').textContent = color;
                document.getElementById('colorSwatch').style.backgroundColor = color;
                showAlert('Chat color updated successfully!');
            } else {
                showAlert(data.error || 'Failed to update color', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Update Color';
        }
    });

    // Email change form handler
    document.getElementById('emailForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('emailSubmit');
        const newEmail = document.getElementById('newEmail').value;
        const password = document.getElementById('emailPassword').value;

        // Basic email validation
        if (!newEmail || !newEmail.includes('@')) {
            showAlert('Please enter a valid email address', 'error');
            return;
        }

        if (newEmail === currentUser.email) {
            showAlert('New email is the same as current email', 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const response = await fetch('/api/auth/change-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    newEmail,
                    password
                })
            });

            const data = await response.json();

            if (response.ok) {
                currentUser.email = newEmail;
                setUser(currentUser);
                document.getElementById('profileEmail').textContent = newEmail;
                document.getElementById('currentEmailDisplay').textContent = newEmail;
                showAlert('Email updated successfully!');
                document.getElementById('emailForm').reset();
            } else {
                showAlert(data.error || 'Failed to update email', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Update Email';
        }
    });

    // Password form handler
    document.getElementById('passwordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('passwordSubmit');
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            showAlert('New passwords do not match', 'error');
            return;
        }

        // Basic password validation
        if (newPassword.length < 8) {
            showAlert('Password must be at least 8 characters', 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Changing...';

        try {
            const response = await fetch('/api/auth/change-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    currentPassword,
                    newPassword
                })
            });

            const data = await response.json();

            if (response.ok) {
                showAlert('Password changed successfully!');
                document.getElementById('passwordForm').reset();
            } else {
                showAlert(data.error || 'Failed to change password', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Change Password';
        }
    });

    // Cancel subscription handler
    document.getElementById('cancelSubBtn')?.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to cancel your subscription?\n\nYou will keep access until your current billing period ends.')) {
            return;
        }

        const btn = document.getElementById('cancelSubBtn');
        btn.disabled = true;
        btn.textContent = 'Cancelling...';

        try {
            const response = await fetch('/api/subscriptions/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                }
            });

            const data = await response.json();

            if (response.ok) {
                showAlert('Subscription cancelled. You will keep access until the end of your billing period.');
                document.getElementById('profileStatus').textContent = 'Cancelled';
                document.getElementById('profileStatus').className = 'profile-value status-expired';
                document.getElementById('cancelCta').style.display = 'none';
            } else {
                showAlert(data.error || 'Failed to cancel subscription', 'error');
                btn.disabled = false;
                btn.textContent = 'Cancel Subscription';
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.textContent = 'Cancel Subscription';
        }
    });

    // Logout handler
    document.getElementById('logoutBtn').addEventListener('click', () => {
        if (confirm('Are you sure you want to log out?')) {
            logout();
        }
    });

    // Avatar helper function
    function updateAvatarDisplay(avatarUrl, username) {
        const avatarImage = document.getElementById('avatarImage');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        const deleteBtn = document.getElementById('deleteAvatarBtn');

        if (avatarUrl) {
            avatarImage.src = avatarUrl;
            avatarImage.style.display = 'block';
            avatarPlaceholder.style.display = 'none';
            deleteBtn.style.display = 'inline-block';
        } else {
            avatarImage.style.display = 'none';
            avatarPlaceholder.style.display = 'flex';
            avatarPlaceholder.textContent = username ? username.charAt(0).toUpperCase() : '?';
            deleteBtn.style.display = 'none';
        }
    }

    // Bio counter helper
    function updateBioCounter() {
        const bioText = document.getElementById('bioText');
        const bioCount = document.getElementById('bioCount');
        const counter = bioText.parentElement.querySelector('.bio-counter');
        const length = bioText.value.length;

        bioCount.textContent = length;
        counter.classList.remove('warning', 'error');
        if (length > 450) counter.classList.add('warning');
        if (length >= 500) counter.classList.add('error');
    }

    // Bio text input handler
    document.getElementById('bioText').addEventListener('input', updateBioCounter);

    // Avatar upload handler
    document.getElementById('avatarInput').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Invalid file type. Use JPG, PNG, GIF, or WebP', 'error');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File too large. Maximum size is 5MB', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        const uploadBtn = document.querySelector('.avatar-btn-upload');
        const originalText = uploadBtn.textContent;
        uploadBtn.textContent = 'Uploading...';

        try {
            const response = await fetch('/api/auth/avatar', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${getToken()}`
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                currentUser.avatar_url = data.avatar_url;
                setUser(currentUser);
                updateAvatarDisplay(data.avatar_url, currentUser.username);
                showAlert('Profile picture updated!');
            } else {
                showAlert(data.error || 'Failed to upload avatar', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            uploadBtn.textContent = originalText;
            e.target.value = ''; // Reset file input
        }
    });

    // Delete avatar handler
    document.getElementById('deleteAvatarBtn').addEventListener('click', async () => {
        if (!confirm('Remove your profile picture?')) return;

        const btn = document.getElementById('deleteAvatarBtn');
        btn.disabled = true;
        btn.textContent = 'Removing...';

        try {
            const response = await fetch('/api/auth/avatar', {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${getToken()}`
                }
            });

            const data = await response.json();

            if (response.ok) {
                currentUser.avatar_url = null;
                setUser(currentUser);
                updateAvatarDisplay(null, currentUser.username);
                showAlert('Profile picture removed');
            } else {
                showAlert(data.error || 'Failed to remove avatar', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Remove';
        }
    });

    // Bio form handler
    document.getElementById('bioForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('bioSubmit');
        const bioText = document.getElementById('bioText').value;

        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const response = await fetch('/api/auth/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({ bio: bioText })
            });

            const data = await response.json();

            if (response.ok) {
                currentUser.bio = bioText;
                setUser(currentUser);
                showAlert('Bio updated successfully!');
            } else {
                showAlert(data.error || 'Failed to update bio', 'error');
            }
        } catch (err) {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Bio';
        }
    });

    // Initialize
    loadProfile();

    // Check if user needs to change password (redirected from login)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('change_password') === '1') {
        // Scroll to password section and show message
        setTimeout(() => {
            const passwordSection = document.getElementById('password-section');
            if (passwordSection) {
                passwordSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                passwordSection.style.border = '2px solid #f39c12';
                passwordSection.style.borderRadius = '8px';
                passwordSection.style.padding = '20px';
                showAlert('Please change your password to continue.', 'warning');
            }
        }, 500);
        // Clean up URL
        window.history.replaceState({}, document.title, '/profile.php');
    }
</script>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
