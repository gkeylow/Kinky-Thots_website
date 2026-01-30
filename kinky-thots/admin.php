<?php
$pageTitle = 'Admin Dashboard - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
    .admin-container {
        max-width: 1400px;
        margin: 80px auto 40px;
        padding: 0 20px;
    }
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .admin-header h1 {
        font-size: 1.75rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .admin-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 1rem;
        flex-wrap: wrap;
    }
    .admin-tab {
        padding: 0.75rem 1.5rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #888;
        cursor: pointer;
        transition: all 0.2s;
    }
    .admin-tab:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .admin-tab.active {
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border-color: transparent;
        color: #fff;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        border: 1px solid rgba(11, 208, 243, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stat-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
    .admin-card {
        background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        border: 1px solid rgba(11, 208, 243, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .admin-card h2 {
        font-size: 1.1rem;
        color: #fff;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .filter-bar { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .filter-bar select, .filter-bar input {
        padding: 0.5rem 1rem;
        background: #1a1a1a;
        border: 1px solid rgba(11, 208, 243, 0.3);
        border-radius: 6px;
        color: #fff;
        font-size: 0.9rem;
    }
    .filter-bar select option { background: #1a1a1a; color: #fff; }
    .filter-bar input { min-width: 200px; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .data-table th {
        text-align: left;
        padding: 0.75rem;
        color: #888;
        font-weight: 500;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .data-table td {
        padding: 0.75rem;
        color: #fff;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .data-table tr:hover { background: rgba(255,255,255,0.02); }
    .tier-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .tier-badge.free { background: #333; color: #888; }
    .tier-badge.basic { background: linear-gradient(135deg, #4ECDC4, #45B7D1); color: #000; }
    .tier-badge.premium { background: linear-gradient(135deg, #f805a7, #0bd0f3); color: #fff; }
    .tier-badge.yearly { background: linear-gradient(135deg, #9b59b6, #8e44ad); color: #fff; }
    .tier-badge.lifetime { background: linear-gradient(135deg, #f39c12, #e67e22); color: #000; }
    .tier-badge.vip { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.active, .status-badge.finished, .status-badge.confirmed { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
    .status-badge.waiting, .status-badge.confirming { background: rgba(52, 152, 219, 0.2); color: #3498db; }
    .status-badge.expired, .status-badge.failed, .status-badge.cancelled { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
    .status-badge.partially_paid { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
    .action-btn { padding: 4px 10px; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer; transition: opacity 0.2s; }
    .action-btn:hover { opacity: 0.8; }
    .action-btn.edit { background: #3498db; color: #fff; }
    .action-btn.delete { background: #e74c3c; color: #fff; }
    .action-btn.upgrade { background: linear-gradient(135deg, #f805a7, #0bd0f3); color: #fff; }
    .loading { text-align: center; padding: 2rem; color: #888; }
    .empty-state { text-align: center; padding: 3rem; color: #666; }
    .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
    .pagination button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; color: #fff; cursor: pointer; }
    .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
    .pagination button.active { background: linear-gradient(135deg, #f805a7, #0bd0f3); border-color: transparent; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
    .modal.open { display: flex; }
    .modal-content { background: #1a1a1a; border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; }
    .modal-content h3 { color: #fff; margin-bottom: 1.5rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; color: #888; margin-bottom: 0.5rem; font-size: 0.9rem; }
    .form-group select, .form-group input { width: 100%; padding: 0.75rem; background: #1a1a1a; border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 6px; color: #fff; }
    .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
    .modal-actions button { flex: 1; padding: 0.75rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .btn-cancel { background: #333; color: #fff; }
    .btn-save { background: linear-gradient(135deg, #f805a7, #0bd0f3); color: #fff; }
    .not-admin { text-align: center; padding: 4rem 2rem; color: #e74c3c; }
    @media (max-width: 768px) {
        .data-table { font-size: 0.8rem; }
        .data-table th, .data-table td { padding: 0.5rem; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
';
include __DIR__ . '/includes/header.php';
?>

<main class="admin-container">
    <div id="notAdmin" class="not-admin" style="display: none;">
        <h2>Access Denied</h2>
        <p>You must be an admin to view this page.</p>
        <a href="/index.php" style="color: #0bd0f3;">Return to Home</a>
    </div>

    <div id="adminContent" style="display: none;">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <span id="adminUser" style="color: #888;"></span>
        </div>

        <div class="admin-tabs">
            <button class="admin-tab active" data-tab="dashboard">Dashboard</button>
            <button class="admin-tab" data-tab="members">Members</button>
            <button class="admin-tab" data-tab="transactions">Transactions</button>
            <button class="admin-tab" data-tab="content">Content</button>
        </div>

        <!-- Dashboard Tab -->
        <div id="tab-dashboard" class="tab-content active">
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card"><div class="stat-value" id="statTotalUsers">-</div><div class="stat-label">Total Members</div></div>
                <div class="stat-card"><div class="stat-value" id="statActiveSubscribers">-</div><div class="stat-label">Active Subscribers</div></div>
                <div class="stat-card"><div class="stat-value" id="statMonthlyRevenue">-</div><div class="stat-label">Monthly Revenue</div></div>
                <div class="stat-card"><div class="stat-value" id="statTotalRevenue">-</div><div class="stat-label">Total Revenue</div></div>
                <div class="stat-card"><div class="stat-value" id="statPendingPayments">-</div><div class="stat-label">Pending Payments</div></div>
                <div class="stat-card"><div class="stat-value" id="statTotalVideos">-</div><div class="stat-label">Total Videos</div></div>
            </div>
            <div class="admin-card"><h2>Recent Activity</h2><div id="recentActivity" class="loading">Loading...</div></div>
        </div>

        <!-- Members Tab -->
        <div id="tab-members" class="tab-content">
            <div class="admin-card">
                <h2>Manage Members</h2>
                <div class="filter-bar">
                    <input type="text" id="memberSearch" placeholder="Search by username or email...">
                    <select id="memberTierFilter">
                        <option value="">All Tiers</option>
                        <option value="free">Free</option>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                        <option value="yearly">Yearly</option>
                        <option value="lifetime">Lifetime</option>
                        <option value="vip">VIP</option>
                    </select>
                    <select id="memberStatusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div id="membersTable" class="loading">Loading...</div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div id="tab-transactions" class="tab-content">
            <div class="admin-card">
                <h2>Payment Transactions</h2>
                <div class="filter-bar">
                    <select id="txStatusFilter">
                        <option value="">All Status</option>
                        <option value="finished">Finished</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="confirming">Confirming</option>
                        <option value="waiting">Waiting</option>
                        <option value="partially_paid">Partial</option>
                        <option value="expired">Expired</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select id="txTierFilter">
                        <option value="">All Tiers</option>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                        <option value="yearly">Yearly</option>
                        <option value="lifetime">Lifetime</option>
                    </select>
                    <input type="date" id="txDateFrom" placeholder="From date">
                    <input type="date" id="txDateTo" placeholder="To date">
                </div>
                <div id="transactionsTable" class="loading">Loading...</div>
            </div>
        </div>

        <!-- Content Tab -->
        <div id="tab-content" class="tab-content">
            <div class="admin-card"><h2>Video Content</h2><div id="contentTable" class="loading">Loading...</div></div>
        </div>
    </div>
</main>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <h3>Edit User</h3>
        <form id="editUserForm">
            <input type="hidden" id="editUserId">
            <div class="form-group"><label>Username</label><input type="text" id="editUsername" disabled></div>
            <div class="form-group"><label>Email</label><input type="email" id="editEmail" disabled></div>
            <div class="form-group">
                <label>Subscription Tier</label>
                <select id="editTier">
                    <option value="free">Free</option>
                    <option value="basic">Basic</option>
                    <option value="premium">Premium</option>
                    <option value="yearly">Yearly</option>
                    <option value="lifetime">Lifetime</option>
                    <option value="vip">VIP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subscription Status</label>
                <select id="editStatus">
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Admin Access</label>
                <select id="editIsAdmin">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const AUTH_TOKEN_KEY = 'kt_auth_token';
const AUTH_USER_KEY = 'kt_auth_user';

let authToken = localStorage.getItem(AUTH_TOKEN_KEY);
let currentUser = JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null');
let membersPage = 1;
let transactionsPage = 1;

async function checkAdmin() {
    if (!authToken || !currentUser) {
        document.getElementById('notAdmin').style.display = 'block';
        return false;
    }
    try {
        const res = await fetch('/api/admin/check', { headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        if (!data.isAdmin) {
            document.getElementById('notAdmin').style.display = 'block';
            return false;
        }
        document.getElementById('adminContent').style.display = 'block';
        document.getElementById('adminUser').textContent = `Logged in as ${currentUser.username}`;
        document.getElementById('userTrigger').textContent = currentUser.username;
        document.body.classList.add('logged-in');
        const adminLink = document.getElementById('adminLink');
        if (adminLink) adminLink.style.display = 'block';
        return true;
    } catch (err) {
        document.getElementById('notAdmin').style.display = 'block';
        return false;
    }
}

document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
        if (tab.dataset.tab === 'members') loadMembers();
        if (tab.dataset.tab === 'transactions') loadTransactions();
        if (tab.dataset.tab === 'content') loadContent();
    });
});

async function loadStats() {
    try {
        const res = await fetch('/api/admin/stats', { headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        document.getElementById('statTotalUsers').textContent = data.totalUsers || 0;
        document.getElementById('statActiveSubscribers').textContent = data.activeSubscribers || 0;
        document.getElementById('statMonthlyRevenue').textContent = `$${(data.monthlyRevenue || 0).toFixed(2)}`;
        document.getElementById('statTotalRevenue').textContent = `$${(data.totalRevenue || 0).toFixed(2)}`;
        document.getElementById('statPendingPayments').textContent = data.pendingPayments || 0;
        document.getElementById('statTotalVideos').textContent = data.totalVideos || 0;
        if (data.recentActivity && data.recentActivity.length > 0) {
            document.getElementById('recentActivity').innerHTML = `<table class="data-table"><thead><tr><th>Time</th><th>User</th><th>Action</th></tr></thead><tbody>${data.recentActivity.map(a => `<tr><td>${new Date(a.created_at).toLocaleString()}</td><td>${a.username || 'Unknown'}</td><td>${a.action}</td></tr>`).join('')}</tbody></table>`;
        } else {
            document.getElementById('recentActivity').innerHTML = '<p class="empty-state">No recent activity</p>';
        }
    } catch (err) { console.error('Failed to load stats:', err); }
}

async function loadMembers() {
    const search = document.getElementById('memberSearch').value;
    const tier = document.getElementById('memberTierFilter').value;
    const status = document.getElementById('memberStatusFilter').value;
    try {
        const params = new URLSearchParams({ page: membersPage, limit: 20 });
        if (search) params.append('search', search);
        if (tier) params.append('tier', tier);
        if (status) params.append('status', status);
        const res = await fetch(`/api/admin/members?${params}`, { headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        if (!data.members || data.members.length === 0) {
            document.getElementById('membersTable').innerHTML = '<p class="empty-state">No members found</p>';
            return;
        }
        document.getElementById('membersTable').innerHTML = `<table class="data-table"><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Tier</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody>${data.members.map(m => `<tr><td>${m.id}</td><td>${m.username}${m.is_admin ? ' <span style="color:#f805a7;">★</span>' : ''}</td><td>${m.email}</td><td><span class="tier-badge ${m.subscription_tier}">${m.subscription_tier}</span></td><td><span class="status-badge ${m.subscription_status}">${m.subscription_status}</span></td><td>${new Date(m.created_at).toLocaleDateString()}</td><td><button class="action-btn edit" onclick="editUser(${m.id})">Edit</button> <button class="action-btn delete" onclick="deleteUser(${m.id}, '${m.username}')">Delete</button></td></tr>`).join('')}</tbody></table><div class="pagination"><button onclick="membersPage--; loadMembers()" ${membersPage <= 1 ? 'disabled' : ''}>Prev</button><button class="active">${membersPage}</button><button onclick="membersPage++; loadMembers()" ${data.members.length < 20 ? 'disabled' : ''}>Next</button></div>`;
    } catch (err) { document.getElementById('membersTable').innerHTML = '<p class="empty-state">Failed to load members</p>'; }
}

async function loadTransactions() {
    const status = document.getElementById('txStatusFilter').value;
    const tier = document.getElementById('txTierFilter').value;
    const dateFrom = document.getElementById('txDateFrom').value;
    const dateTo = document.getElementById('txDateTo').value;
    try {
        const params = new URLSearchParams({ page: transactionsPage, limit: 20 });
        if (status) params.append('status', status);
        if (tier) params.append('tier', tier);
        if (dateFrom) params.append('dateFrom', dateFrom);
        if (dateTo) params.append('dateTo', dateTo);
        const res = await fetch(`/api/admin/transactions?${params}`, { headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        if (!data.transactions || data.transactions.length === 0) {
            document.getElementById('transactionsTable').innerHTML = '<p class="empty-state">No transactions found</p>';
            return;
        }
        document.getElementById('transactionsTable').innerHTML = `<table class="data-table"><thead><tr><th>ID</th><th>User</th><th>Tier</th><th>Amount</th><th>Crypto</th><th>Status</th><th>Date</th></tr></thead><tbody>${data.transactions.map(t => `<tr><td title="${t.payment_id}">${t.payment_id?.substring(0, 8)}...</td><td>${t.username || 'Unknown'}</td><td><span class="tier-badge ${t.tier}">${t.tier || '-'}</span></td><td>$${parseFloat(t.price_amount || 0).toFixed(2)}</td><td>${t.pay_amount ? `${t.pay_amount} ${t.pay_currency?.toUpperCase()}` : '-'}</td><td><span class="status-badge ${t.payment_status}">${t.payment_status}</span></td><td>${new Date(t.created_at).toLocaleString()}</td></tr>`).join('')}</tbody></table><div class="pagination"><button onclick="transactionsPage--; loadTransactions()" ${transactionsPage <= 1 ? 'disabled' : ''}>Prev</button><button class="active">${transactionsPage}</button><button onclick="transactionsPage++; loadTransactions()" ${data.transactions.length < 20 ? 'disabled' : ''}>Next</button></div>`;
    } catch (err) { document.getElementById('transactionsTable').innerHTML = '<p class="empty-state">Failed to load transactions</p>'; }
}

async function loadContent() {
    try {
        const res = await fetch('/api/admin/content', { headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        if (!data.videos || data.videos.length === 0) {
            document.getElementById('contentTable').innerHTML = '<p class="empty-state">No videos found</p>';
            return;
        }
        document.getElementById('contentTable').innerHTML = `<table class="data-table"><thead><tr><th>Filename</th><th>Duration</th><th>Tier Access</th><th>Size</th></tr></thead><tbody>${data.videos.map(v => { const mins = Math.floor(v.duration_seconds / 60); const secs = v.duration_seconds % 60; const tierAccess = v.duration_seconds < 60 ? 'free' : v.duration_seconds <= 300 ? 'basic' : 'premium'; return `<tr><td>${v.filename}</td><td>${mins}:${secs.toString().padStart(2, '0')}</td><td><span class="tier-badge ${tierAccess}">${tierAccess}</span></td><td>${v.size ? (v.size / 1024 / 1024).toFixed(1) + ' MB' : '-'}</td></tr>`; }).join('')}</tbody></table>`;
    } catch (err) { document.getElementById('contentTable').innerHTML = '<p class="empty-state">Failed to load content</p>'; }
}

async function editUser(userId) {
    try {
        const res = await fetch(`/api/admin/members/${userId}`, { headers: { 'Authorization': `Bearer ${authToken}` } });
        const user = await res.json();
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editTier').value = user.subscription_tier;
        document.getElementById('editStatus').value = user.subscription_status;
        document.getElementById('editIsAdmin').value = user.is_admin ? '1' : '0';
        document.getElementById('editUserModal').classList.add('open');
    } catch (err) { alert('Failed to load user'); }
}

function closeModal() { document.getElementById('editUserModal').classList.remove('open'); }

async function deleteUser(userId, username) {
    if (!confirm(`Are you sure you want to delete "${username}"?\n\nThis action cannot be undone.`)) return;
    try {
        const res = await fetch(`/api/admin/members/${userId}`, { method: 'DELETE', headers: { 'Authorization': `Bearer ${authToken}` } });
        const data = await res.json();
        if (res.ok) { alert(data.message || 'User deleted successfully'); loadMembers(); }
        else { alert(data.error || 'Failed to delete user'); }
    } catch (err) { alert('Failed to delete user'); }
}

document.getElementById('editUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const userId = document.getElementById('editUserId').value;
    try {
        const res = await fetch(`/api/admin/members/${userId}`, {
            method: 'PUT',
            headers: { 'Authorization': `Bearer ${authToken}`, 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subscription_tier: document.getElementById('editTier').value,
                subscription_status: document.getElementById('editStatus').value,
                is_admin: document.getElementById('editIsAdmin').value === '1'
            })
        });
        if (res.ok) { closeModal(); loadMembers(); }
        else { alert('Failed to update user'); }
    } catch (err) { alert('Failed to update user'); }
});

document.getElementById('memberSearch').addEventListener('input', () => { membersPage = 1; loadMembers(); });
document.getElementById('memberTierFilter').addEventListener('change', () => { membersPage = 1; loadMembers(); });
document.getElementById('memberStatusFilter').addEventListener('change', () => { membersPage = 1; loadMembers(); });
document.getElementById('txStatusFilter').addEventListener('change', () => { transactionsPage = 1; loadTransactions(); });
document.getElementById('txTierFilter').addEventListener('change', () => { transactionsPage = 1; loadTransactions(); });
document.getElementById('txDateFrom').addEventListener('change', () => { transactionsPage = 1; loadTransactions(); });
document.getElementById('txDateTo').addEventListener('change', () => { transactionsPage = 1; loadTransactions(); });

document.getElementById('logoutLink')?.addEventListener('click', (e) => {
    e.preventDefault();
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
    window.location.href = '/index.php';
});

checkAdmin().then(isAdmin => { if (isAdmin) loadStats(); });
</script>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
