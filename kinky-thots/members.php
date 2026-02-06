<?php
$pageTitle = 'Members - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
    .members-container { max-width: 1200px; margin: 100px auto 40px; padding: 0 20px; }
    .members-header { text-align: center; margin-bottom: 2rem; }
    .members-header h1 { font-size: 2rem; background: linear-gradient(135deg, #f805a7, #0bd0f3); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
    .members-filters { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2rem; }
    .filter-input { padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 8px; color: #fff; font-size: 1rem; min-width: 200px; }
    .filter-input:focus { outline: none; border-color: #0bd0f3; }
    .filter-select { padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 8px; color: #fff; font-size: 1rem; cursor: pointer; }
    .filter-select option { background: #1a1a1a; }
    .members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    .member-card { background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; align-items: center; transition: transform 0.2s, border-color 0.2s; }
    .member-card:hover { transform: translateY(-3px); border-color: #0bd0f3; }
    .member-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(11, 208, 243, 0.5); margin-bottom: 1rem; background: #333; }
    .member-avatar-placeholder { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #333, #222); border: 3px solid rgba(11, 208, 243, 0.3); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #666; margin-bottom: 1rem; }
    .member-username { font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 0.5rem; }
    .member-tier { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem; }
    .member-tier.free { background: #333; color: #888; }
    .member-tier.plus { background: linear-gradient(135deg, #4ECDC4, #45B7D1); color: #000; }
    .member-tier.premium { background: linear-gradient(135deg, #f805a7, #0bd0f3); color: #fff; }
    .member-tier.yearly, .member-tier.vip { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
    .member-tier.lifetime { background: linear-gradient(135deg, #9b59b6, #8e44ad); color: #fff; }
    .member-last-seen { font-size: 0.85rem; color: #666; margin-bottom: 1rem; }
    .member-last-seen.online { color: #2ecc71; }
    .dm-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem; background: linear-gradient(135deg, #f805a7, #0bd0f3); border: none; border-radius: 20px; color: #fff; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: opacity 0.2s; }
    .dm-btn:hover { opacity: 0.9; }
    .dm-btn.locked { background: #333; color: #666; cursor: not-allowed; }
    .dm-btn svg { width: 18px; height: 18px; }
    .load-more { text-align: center; margin-top: 2rem; }
    .btn { display: inline-block; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #f805a7, #0bd0f3); border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer; text-decoration: none; }
    .btn:hover { opacity: 0.9; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-secondary { background: #333; }
    .login-prompt { text-align: center; padding: 3rem; background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 16px; }
    .login-prompt h2 { color: #fff; margin-bottom: 1rem; }
    .login-prompt p { color: #888; margin-bottom: 1.5rem; }
    .loading { text-align: center; padding: 3rem; color: #888; }
    .no-members { text-align: center; padding: 3rem; color: #666; }
    .dm-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
    .dm-modal-overlay.active { display: flex; }
    .dm-modal { background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 16px; width: 100%; max-width: 500px; max-height: 80vh; display: flex; flex-direction: column; }
    .dm-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .dm-recipient-info { display: flex; align-items: center; gap: 0.75rem; }
    .dm-recipient-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #333; }
    .dm-recipient-name { font-weight: 600; color: #fff; }
    .dm-modal-close { background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer; padding: 0.5rem; line-height: 1; }
    .dm-modal-close:hover { color: #fff; }
    .dm-messages { flex: 1; overflow-y: auto; padding: 1rem 1.5rem; min-height: 300px; max-height: 400px; }
    .dm-message { margin-bottom: 0.75rem; max-width: 80%; }
    .dm-message.sent { margin-left: auto; }
    .dm-message-bubble { padding: 0.75rem 1rem; border-radius: 16px; word-wrap: break-word; }
    .dm-message.sent .dm-message-bubble { background: linear-gradient(135deg, #f805a7, #0bd0f3); color: #fff; border-bottom-right-radius: 4px; }
    .dm-message.received .dm-message-bubble { background: #2a2a2a; color: #fff; border-bottom-left-radius: 4px; }
    .dm-message-time { font-size: 0.7rem; color: #666; margin-top: 0.25rem; }
    .dm-message.sent .dm-message-time { text-align: right; }
    .dm-input-form { display: flex; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
    .dm-input { flex: 1; padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(11, 208, 243, 0.3); border-radius: 20px; color: #fff; font-size: 1rem; resize: none; }
    .dm-input:focus { outline: none; border-color: #0bd0f3; }
    .dm-send-btn { padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #f805a7, #0bd0f3); border: none; border-radius: 20px; color: #fff; font-weight: 600; cursor: pointer; }
    .dm-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .dm-loading { text-align: center; padding: 2rem; color: #888; }
    .dm-empty { text-align: center; padding: 2rem; color: #666; }
    .upgrade-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); display: none; justify-content: center; align-items: center; z-index: 1001; }
    .upgrade-modal-overlay.active { display: flex; }
    .upgrade-modal { background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); border: 1px solid rgba(248, 5, 167, 0.5); border-radius: 16px; padding: 2rem; text-align: center; max-width: 400px; }
    .upgrade-modal h3 { color: #f805a7; margin-bottom: 1rem; }
    .upgrade-modal p { color: #888; margin-bottom: 1.5rem; }
    .upgrade-modal-actions { display: flex; gap: 1rem; justify-content: center; }
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
    .alert-error { background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; }
    @media (max-width: 768px) { .members-container { margin-top: 80px; } .members-filters { flex-direction: column; align-items: center; } .filter-input { width: 100%; max-width: 300px; } .dm-modal { max-height: 90vh; } .dm-messages { min-height: 200px; } }
';
include __DIR__ . '/includes/header.php';
?>

<main class="members-container">
    <div class="members-header"><h1>Community Members</h1></div>

    <div id="notLoggedIn" class="login-prompt" style="display: none;">
        <h2>Please Log In</h2>
        <p>You need to be logged in to view members.</p>
        <a href="/login.php?redirect=/members.php" class="btn">Go to Login</a>
    </div>

    <div id="membersContent" style="display: none;">
        <div id="alertContainer"></div>
        <div class="members-filters">
            <input type="text" class="filter-input" id="searchInput" placeholder="Search members...">
            <select class="filter-select" id="tierFilter">
                <option value="">All Tiers</option>
                <option value="plus">Plus</option>
                <option value="premium">Premium</option>
                <option value="yearly">Yearly</option>
                <option value="vip">VIP</option>
                <option value="lifetime">Lifetime</option>
            </select>
        </div>
        <div id="membersGrid" class="members-grid"></div>
        <div id="loadingIndicator" class="loading" style="display: none;">Loading members...</div>
        <div id="noMembers" class="no-members" style="display: none;">No members found</div>
        <div id="loadMore" class="load-more" style="display: none;"><button class="btn btn-secondary" id="loadMoreBtn">Load More</button></div>
    </div>
</main>

<!-- DM Modal -->
<div id="dmModal" class="dm-modal-overlay">
    <div class="dm-modal">
        <div class="dm-modal-header">
            <div class="dm-recipient-info">
                <img src="" alt="" class="dm-recipient-avatar" id="dmRecipientAvatar">
                <span class="dm-recipient-name" id="dmRecipientName"></span>
            </div>
            <button class="dm-modal-close" id="dmModalClose">&times;</button>
        </div>
        <div class="dm-messages" id="dmMessages"><div class="dm-loading">Loading messages...</div></div>
        <form class="dm-input-form" id="dmForm">
            <textarea class="dm-input" id="dmInput" placeholder="Type a message..." maxlength="2000" rows="1"></textarea>
            <button type="submit" class="dm-send-btn" id="dmSendBtn">Send</button>
        </form>
    </div>
</div>

<!-- Upgrade Prompt Modal -->
<div id="upgradeModal" class="upgrade-modal-overlay">
    <div class="upgrade-modal">
        <h3>Upgrade Required</h3>
        <p>Direct messaging is available for subscribers only. Upgrade your plan to start chatting with other members!</p>
        <div class="upgrade-modal-actions">
            <button class="btn btn-secondary" id="upgradeModalClose">Close</button>
            <a href="/subscriptions.php" class="btn">View Plans</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const AUTH_TOKEN_KEY = 'kt_auth_token';
const AUTH_USER_KEY = 'kt_auth_user';
let currentUser = null, canSendDM = false, currentPage = 1, hasMore = true, isLoading = false, currentDmUserId = null, searchTimeout = null, ws = null;

function getToken() { return localStorage.getItem(AUTH_TOKEN_KEY); }
function getUser() { return JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null'); }
function showAlert(message, type = 'error') { const c = document.getElementById('alertContainer'); c.innerHTML = `<div class="alert alert-${type}">${message}</div>`; setTimeout(() => c.innerHTML = '', 5000); }
function formatRelativeTime(d) { if (!d) return 'Never'; const diff = new Date() - new Date(d), m = Math.floor(diff / 60000), h = Math.floor(diff / 3600000), days = Math.floor(diff / 86400000); if (m < 5) return 'Online now'; if (m < 60) return `${m} min ago`; if (h < 24) return `${h} hour${h > 1 ? 's' : ''} ago`; if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`; return new Date(d).toLocaleDateString(); }
function formatMessageTime(d) { return new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function renderMemberCard(m) {
    const isOnline = m.last_login_at && (new Date() - new Date(m.last_login_at)) < 300000;
    const avatarHtml = m.avatar_url ? `<img src="${escapeHtml(m.avatar_url)}" alt="${escapeHtml(m.username)}" class="member-avatar">` : `<div class="member-avatar-placeholder">${m.username.charAt(0).toUpperCase()}</div>`;
    const dmBtnClass = canSendDM ? 'dm-btn' : 'dm-btn locked';
    const dmIcon = canSendDM ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
    return `<div class="member-card" data-user-id="${m.id}">${avatarHtml}<div class="member-username">${escapeHtml(m.username)}</div><span class="member-tier ${m.subscription_tier}">${m.subscription_tier.toUpperCase()}</span><span class="member-last-seen ${isOnline ? 'online' : ''}">${formatRelativeTime(m.last_login_at)}</span><button class="${dmBtnClass}" data-user-id="${m.id}" data-username="${escapeHtml(m.username)}" data-avatar="${escapeHtml(m.avatar_url || '')}">${dmIcon} Message</button></div>`;
}

async function loadMembers(append = false) {
    if (isLoading) return;
    isLoading = true;
    const grid = document.getElementById('membersGrid'), loading = document.getElementById('loadingIndicator'), noMembers = document.getElementById('noMembers'), loadMoreDiv = document.getElementById('loadMore');
    if (!append) { grid.innerHTML = ''; currentPage = 1; }
    loading.style.display = 'block'; noMembers.style.display = 'none'; loadMoreDiv.style.display = 'none';
    const search = document.getElementById('searchInput').value, tier = document.getElementById('tierFilter').value;
    try {
        const params = new URLSearchParams({ page: currentPage, limit: 20, ...(search && { search }), ...(tier && { tier }) });
        const response = await fetch(`/api/members?${params}`, { headers: { 'Authorization': `Bearer ${getToken()}` } });
        if (!response.ok) { if (response.status === 401 || response.status === 403) { localStorage.removeItem(AUTH_TOKEN_KEY); localStorage.removeItem(AUTH_USER_KEY); window.location.href = '/login.php?redirect=/members.php'; return; } throw new Error('Failed'); }
        const data = await response.json();
        loading.style.display = 'none';
        if (data.members.length === 0 && currentPage === 1) { noMembers.style.display = 'block'; return; }
        const filtered = data.members.filter(m => m.id !== currentUser.id);
        filtered.forEach(m => { grid.innerHTML += renderMemberCard(m); });
        hasMore = currentPage < data.pagination.totalPages;
        if (hasMore) loadMoreDiv.style.display = 'block';
    } catch (err) { loading.style.display = 'none'; showAlert('Failed to load members'); } finally { isLoading = false; }
}

async function openDmModal(userId, username, avatarUrl) {
    if (!canSendDM) { document.getElementById('upgradeModal').classList.add('active'); return; }
    currentDmUserId = userId;
    const modal = document.getElementById('dmModal'), mc = document.getElementById('dmMessages');
    document.getElementById('dmRecipientName').textContent = username;
    const av = document.getElementById('dmRecipientAvatar');
    if (avatarUrl) { av.src = avatarUrl; av.style.display = 'block'; } else { av.style.display = 'none'; }
    mc.innerHTML = '<div class="dm-loading">Loading messages...</div>';
    modal.classList.add('active');
    try {
        const r = await fetch(`/api/messages/conversation/${userId}`, { headers: { 'Authorization': `Bearer ${getToken()}` } });
        if (!r.ok) throw new Error();
        const data = await r.json();
        if (data.messages.length === 0) { mc.innerHTML = '<div class="dm-empty">No messages yet. Start the conversation!</div>'; }
        else { mc.innerHTML = data.messages.map(m => `<div class="dm-message ${m.isMine ? 'sent' : 'received'}"><div class="dm-message-bubble">${escapeHtml(m.content)}</div><div class="dm-message-time">${formatMessageTime(m.createdAt)}</div></div>`).join(''); mc.scrollTop = mc.scrollHeight; }
        await fetch(`/api/messages/read/${userId}`, { method: 'POST', headers: { 'Authorization': `Bearer ${getToken()}` } });
    } catch (e) { mc.innerHTML = '<div class="dm-empty">Failed to load messages</div>'; }
}

function closeDmModal() { document.getElementById('dmModal').classList.remove('active'); currentDmUserId = null; }

async function sendMessage(content) {
    if (!currentDmUserId || !content.trim()) return;
    const btn = document.getElementById('dmSendBtn'), input = document.getElementById('dmInput');
    btn.disabled = true;
    try {
        const r = await fetch('/api/messages/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` }, body: JSON.stringify({ recipientId: currentDmUserId, content: content.trim() }) });
        const data = await r.json();
        if (!r.ok) throw new Error(data.error || 'Failed');
        const mc = document.getElementById('dmMessages');
        const em = mc.querySelector('.dm-empty'); if (em) em.remove();
        mc.innerHTML += `<div class="dm-message sent"><div class="dm-message-bubble">${escapeHtml(data.message.content)}</div><div class="dm-message-time">${formatMessageTime(data.message.createdAt)}</div></div>`;
        mc.scrollTop = mc.scrollHeight;
        input.value = '';
    } catch (e) { showAlert(e.message); } finally { btn.disabled = false; }
}

function connectWebSocket() {
    const token = getToken(); if (!token) return;
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    ws = new WebSocket(`${protocol}//${window.location.host}/ws/chat?token=${token}`);
    ws.onmessage = (e) => { try { const d = JSON.parse(e.data); if (d.type === 'new_dm' && currentDmUserId === d.fromId) { const mc = document.getElementById('dmMessages'); const em = mc.querySelector('.dm-empty'); if (em) em.remove(); mc.innerHTML += `<div class="dm-message received"><div class="dm-message-bubble">${escapeHtml(d.preview)}</div><div class="dm-message-time">${formatMessageTime(d.createdAt)}</div></div>`; mc.scrollTop = mc.scrollHeight; fetch(`/api/messages/read/${d.fromId}`, { method: 'POST', headers: { 'Authorization': `Bearer ${getToken()}` } }); } } catch (x) {} };
    ws.onclose = () => { setTimeout(connectWebSocket, 5000); };
}

async function init() {
    const token = getToken(), user = getUser();
    if (!token || !user) { document.getElementById('notLoggedIn').style.display = 'block'; return; }
    try {
        const r = await fetch('/api/auth/me', { headers: { 'Authorization': `Bearer ${token}` } });
        if (!r.ok) throw new Error('Invalid session');
        const data = await r.json();
        currentUser = data.user;
        const tier = currentUser.subscription_tier || 'free';
        canSendDM = tier !== 'free';
        document.getElementById('membersContent').style.display = 'block';
        loadMembers();
        connectWebSocket();
    } catch (e) { localStorage.removeItem(AUTH_TOKEN_KEY); localStorage.removeItem(AUTH_USER_KEY); window.location.href = '/login.php?redirect=/members.php'; }
}

document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => loadMembers(), 300); });
document.getElementById('tierFilter').addEventListener('change', () => loadMembers());
document.getElementById('loadMoreBtn').addEventListener('click', () => { currentPage++; loadMembers(true); });
document.getElementById('membersGrid').addEventListener('click', (e) => { const btn = e.target.closest('.dm-btn'); if (btn) { openDmModal(parseInt(btn.dataset.userId), btn.dataset.username, btn.dataset.avatar); } });
document.getElementById('dmModalClose').addEventListener('click', closeDmModal);
document.getElementById('dmModal').addEventListener('click', (e) => { if (e.target.id === 'dmModal') closeDmModal(); });
document.getElementById('dmForm').addEventListener('submit', (e) => { e.preventDefault(); sendMessage(document.getElementById('dmInput').value); });
document.getElementById('upgradeModalClose').addEventListener('click', () => document.getElementById('upgradeModal').classList.remove('active'));
document.getElementById('upgradeModal').addEventListener('click', (e) => { if (e.target.id === 'upgradeModal') document.getElementById('upgradeModal').classList.remove('active'); });
document.getElementById('dmInput').addEventListener('input', function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 100) + 'px'; });
document.getElementById('dmInput').addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.getElementById('dmForm').dispatchEvent(new Event('submit')); } });
init();
</script>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
