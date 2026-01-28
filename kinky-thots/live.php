<?php
$pageTitle = 'Live Cam - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageCss = ['/assets/dist/css/live.css'];
include __DIR__ . '/includes/header.php';
?>

<main class="live-container">
    <div class="live-content">
        <!-- Video Player Section -->
        <section class="video-section">
            <div class="video-wrapper">
                <div class="video-player" id="videoPlayer">
                    <!-- Offline placeholder -->
                    <div class="offline-placeholder" id="offlinePlaceholder">
                        <div class="offline-icon">📺</div>
                        <h2>Stream Currently Offline</h2>
                        <p>Check back soon for live content!</p>
                        <div class="schedule-info">
                            <p>Follow us to get notified when we go live</p>
                        </div>
                    </div>
                    <!-- HLS.js player for Red5 stream -->
                    <video id="liveVideo" controls playsinline style="width: 100%; height: 100%; display: block; background: #000;">
                        Your browser does not support the video tag.
                    </video>
                </div>

                <!-- Stream Info Bar -->
                <div class="stream-info">
                    <div class="stream-title">
                        <h3 id="streamTitle">Kinky-Thots Live</h3>
                        <span class="viewer-count" id="viewerCount">0 viewers</span>
                    </div>
                    <div class="stream-actions">
                        <button class="action-btn" id="fullscreenBtn" title="Fullscreen" aria-label="Toggle fullscreen mode">
                            <span>&#x26F6;</span>
                        </button>
                        <button class="action-btn" id="theaterBtn" title="Theater Mode" aria-label="Toggle theater mode">
                            <span>&#x25A2;</span>
                        </button>
                        <button class="action-btn" id="settingsBtn" title="Settings" aria-label="Open settings menu">
                            <span>⚙</span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Settings Panel -->
                <div class="settings-panel" id="settingsPanel" style="display: none;">
                    <div class="settings-header">
                        <h4>Stream Settings</h4>
                        <button class="close-btn" id="closeSettings" aria-label="Close settings">×</button>
                    </div>
                    <div class="settings-content">
                        <label class="setting-item">
                            <input type="checkbox" id="autoBitrate" checked>
                            <span>Auto Bitrate</span>
                        </label>
                        <label class="setting-item">
                            <input type="checkbox" id="lowDataMode">
                            <span>Low Data Mode</span>
                        </label>
                        <div class="volume-control">
                            <label>Volume:</label>
                            <input type="range" id="volumeControl" min="0" max="100" value="70">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Chat Section -->
        <section class="chat-section">
            <div class="chat-container">
                <div class="chat-header">
                    <h4>Live Chat</h4>
                    <div class="chat-status">
                        <span class="status-dot offline" id="chatStatusDot"></span>
                        <span class="status-label" id="chatStatusLabel">Offline</span>
                    </div>
                    <button class="tip-btn" id="tipBtn" title="Send a tip">💰 Tip</button>
                    <span class="chat-viewers" id="chatViewers">0 watching</span>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-welcome">Welcome to the live chat!</div>
                </div>
                <div class="chat-input-area">
                    <div class="reaction-bar">
                        <button class="reaction-btn" data-emoji="❤️" title="Love">❤️</button>
                        <button class="reaction-btn" data-emoji="🔥" title="Fire">🔥</button>
                        <button class="reaction-btn" data-emoji="😍" title="Heart Eyes">😍</button>
                        <button class="reaction-btn" data-emoji="👏" title="Clap">👏</button>
                        <button class="reaction-btn" data-emoji="💦" title="Splash">💦</button>
                        <button class="reaction-btn" data-emoji="🍑" title="Peach">🍑</button>
                    </div>
                    <div class="chat-input-row">
                        <input type="text" id="chatInput" placeholder="Send a message..." maxlength="500" autocomplete="off">
                        <button id="chatSendBtn" title="Send">➤</button>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- Floating Emoji Container -->
    <div id="floatingEmojis" class="floating-emojis"></div>

    <!-- Tip Modal -->
    <div class="tip-modal" id="tipModal">
        <div class="tip-modal-content">
            <button class="tip-modal-close" id="tipModalClose">&times;</button>
            <h3>Send a Tip</h3>
            <p>Support the stream with crypto!</p>
            <iframe
                src="https://nowpayments.io/embeds/donation-widget?api_key=227cc9af-e05b-46a6-a096-e66717e299ac"
                width="286"
                height="384"
                frameborder="0"
                scrolling="yes"
                style="border-radius: 12px;">
                Can't load widget
            </iframe>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- HLS.js for RTMP/HLS playback -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<!-- Built JS (Vite) -->
<script type="module" src="/assets/dist/js/live.js"></script>

<!-- Auth Modal -->
<div class="auth-modal-overlay" id="authModal">
  <div class="auth-modal">
    <div class="auth-modal-header">
      <h2 class="auth-modal-title" id="authModalTitle">Welcome Back</h2>
      <button class="auth-modal-close" id="authModalClose">&times;</button>
    </div>

    <div class="auth-tabs">
      <button class="auth-tab active" data-tab="login">Login</button>
      <button class="auth-tab" data-tab="register">Register</button>
    </div>

    <form class="auth-form active" id="loginForm">
      <div class="auth-field">
        <label class="auth-label" for="loginEmail">Email or Username</label>
        <input type="text" class="auth-input" id="loginEmail" placeholder="Enter email or username" required>
      </div>
      <div class="auth-field">
        <label class="auth-label" for="loginPassword">Password</label>
        <input type="password" class="auth-input" id="loginPassword" placeholder="Enter password" required>
        <div class="auth-error" id="loginError"></div>
      </div>
      <button type="submit" class="auth-submit">Login</button>
      <a href="#" class="auth-forgot-link" id="forgotPasswordLink">Forgot Password?</a>
    </form>

    <form class="auth-form" id="forgotForm">
      <p style="color: #b0b0b0; margin-bottom: 1rem;">Enter your email and we'll send you a reset link.</p>
      <div class="auth-field">
        <label class="auth-label" for="forgotEmail">Email</label>
        <input type="email" class="auth-input" id="forgotEmail" placeholder="Enter your email" required>
        <div class="auth-error" id="forgotError"></div>
        <div class="auth-success" id="forgotSuccess"></div>
      </div>
      <button type="submit" class="auth-submit">Send Reset Link</button>
      <a href="#" class="auth-forgot-link" id="backToLoginLink">Back to Login</a>
    </form>

    <form class="auth-form" id="registerForm">
      <div class="auth-field">
        <label class="auth-label" for="registerUsername">Username</label>
        <input type="text" class="auth-input" id="registerUsername" placeholder="3-30 characters" required minlength="3" maxlength="30">
      </div>
      <div class="auth-field">
        <label class="auth-label" for="registerEmail">Email</label>
        <input type="email" class="auth-input" id="registerEmail" placeholder="Enter your email" required>
      </div>
      <div class="auth-field">
        <label class="auth-label" for="registerPassword">Password</label>
        <input type="password" class="auth-input" id="registerPassword" placeholder="Min 8 chars, upper, lower, number" required minlength="8">
      </div>
      <div class="auth-field">
        <label class="auth-label" for="registerConfirm">Confirm Password</label>
        <input type="password" class="auth-input" id="registerConfirm" placeholder="Confirm password" required>
        <div class="auth-error" id="registerError"></div>
      </div>
      <button type="submit" class="auth-submit">Create Account</button>
    </form>
  </div>
</div>

<script src="https://static.elfsight.com/platform/platform.js" async></script>
<div class="elfsight-app-ea2f58c6-6128-4e92-b2d9-c0b5c09769c3" data-elfsight-app-lazy></div>

<!-- Tip Modal Script -->
<script>
(function() {
  const tipBtn = document.getElementById('tipBtn');
  const tipModal = document.getElementById('tipModal');
  const tipModalClose = document.getElementById('tipModalClose');

  tipBtn.addEventListener('click', () => {
    tipModal.classList.add('active');
  });

  tipModalClose.addEventListener('click', () => {
    tipModal.classList.remove('active');
  });

  tipModal.addEventListener('click', (e) => {
    if (e.target === tipModal) {
      tipModal.classList.remove('active');
    }
  });
})();
</script>
</body>
</html>
