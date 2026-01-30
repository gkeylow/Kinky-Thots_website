<?php
$pageTitle = 'Kinky Thots - Premium Content Creators';
$pageDescription = 'Exclusive premium content from independent creators. Join our community for exclusive photos, videos, and live streams.';
$pageCss = ['/assets/dist/css/index.css'];
include __DIR__ . '/includes/header-landing.php';
?>

<style>
/* Landing page specific styles */
.landing-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 1rem 2rem;
    background: rgba(36, 36, 36, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-container {
    max-width: 1200px;
    margin: 0 auto;
}

.logo-link {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
}

.logo-text {
    font-size: 1.8rem;
    font-weight: bold;
    background: linear-gradient(45deg, #0bd0f3, #f805a7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.feature-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
    border-color: #0bd0f3;
    box-shadow: 0 20px 40px rgba(11, 208, 243, 0.2);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1.5rem;
}

.feature-card h3 {
    color: #fff;
    font-size: 1.4rem;
    margin-bottom: 1rem;
}

.feature-card p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
}

.stats-section {
    padding: 60px 0;
    background: #1a1a1a;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    text-align: center;
}

.stat-item h3 {
    font-size: 3rem;
    background: linear-gradient(45deg, #0bd0f3, #f805a7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.stat-item p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
}

.age-notice {
    background: rgba(248, 5, 167, 0.1);
    border: 1px solid rgba(248, 5, 167, 0.3);
    border-radius: 10px;
    padding: 1rem 2rem;
    margin-top: 2rem;
    display: inline-block;
}

.age-notice p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
}

@media (max-width: 768px) {
    .landing-header {
        padding: 0.8rem 1rem;
    }

    .logo-link img {
        width: 50px;
    }

    .logo-text {
        font-size: 1.4rem;
    }
}
</style>

<section class="hero" id="home">
    <div class="hero-content">
        <h1 class="dissolve">Kinky-Thots</h1>
        <p class="hero-tagline">Premium Content Creators</p>
        <p>Exclusive photos, videos, and live streams from independent models.</p>
        <div class="hero-buttons">
            <a href="https://kinky-thots.xxx/subscriptions.php" class="cta-button primary">Get Access</a>
            <a href="https://kinky-thots.xxx" class="cta-button secondary">Enter Site</a>
        </div>
        <div class="age-notice">
            <p>You must be 18+ to access our content</p>
        </div>
    </div>
</section>

<section class="skills" id="features">
    <div class="container">
        <div class="section-header">
            <h2>What We Offer</h2>
            <p>Premium content from real independent creators</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="https://i.ibb.co/pjzNBPf0/camera.png" width="75" alt="Photos">
                </div>
                <h3>Exclusive Photos</h3>
                <p>High-quality photography updated regularly. Get access to our full gallery of exclusive shots.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="https://i.ibb.co/yn1L7TpR/video.png" width="75" alt="Videos">
                </div>
                <h3>Premium Videos</h3>
                <p>Full-length videos in HD quality. New content added weekly for subscribers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="https://i.ibb.co/qLHz4Fcj/webcam.png" width="75" alt="Live">
                </div>
                <h3>Live Streams</h3>
                <p>Interactive live sessions with real-time chat. Connect directly with our creators.</p>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>10K+</h3>
                <p>Photos & Videos</p>
            </div>
            <div class="stat-item">
                <h3>6+</h3>
                <p>Years Creating</p>
            </div>
            <div class="stat-item">
                <h3>2</h3>
                <p>Featured Models</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>Content Access</p>
            </div>
        </div>
    </div>
</section>

<section class="about" id="about">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>About Kinky-Thots</h2>
                <p>We are independent content creators with over 6 years of experience in premium digital content.</p>
                <p>Our platform features exclusive content you won't find anywhere else, directly from the creators themselves.</p>
                <p>Join our community and get access to our full library of premium content.</p>
            </div>
            <div class="profile-image">
                <svg width="100%" height="400" viewBox="0 0 400 400">
                    <defs>
                        <linearGradient id="profileGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="5%" style="stop-color:#f805a7;stop-opacity:1" />
                            <stop offset="95%" style="stop-color:#0bd0f3;stop-opacity:1" />
                        </linearGradient>
                        <clipPath id="circleClip">
                            <circle cx="200" cy="150" r="60"/>
                        </clipPath>
                    </defs>
                    <rect width="400" height="400" fill="url(#profileGrad)" rx="20"/>
                    <image x="140" y="90" width="120" height="120" href="https://i.ibb.co/DDH6rDTw/profile-kinky-2-modified.png" clip-path="url(#circleClip)"/>
                    <circle cx="200" cy="150" r="60" fill="none" stroke="white" stroke-width="3" opacity="0.8"/>
                    <a href="https://kinky-thots.xxx" target="_blank">
                        <rect x="125" y="250" width="150" height="40" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="2" rx="20" style="cursor:pointer"/>
                        <rect x="125" y="250" width="150" height="40" fill="rgba(255,255,255,0.05)" rx="20" style="cursor:pointer">
                            <animate attributeName="fill" values="rgba(255,255,255,0.05);rgba(255,255,255,0.2);rgba(255,255,255,0.05)" dur="2s" repeatCount="indefinite"/>
                        </rect>
                        <text x="200" y="275" text-anchor="middle" fill="white" font-size="14" font-weight="bold" font-family="Arial, sans-serif" style="cursor:pointer;pointer-events:none">ENTER SITE</text>
                    </a>
                    <text x="200" y="320" text-anchor="middle" fill="white" font-size="16" font-family="Arial, sans-serif">Founders</text>
                </svg>
            </div>
        </div>
    </div>
</section>

<section class="contact" id="join">
    <div class="container">
        <div class="section-header">
            <h2>Ready to Join?</h2>
            <p>Get exclusive access to all our premium content</p>
        </div>
        <div class="contact-buttons">
            <a href="https://kinky-thots.xxx/subscriptions.php" class="cta-button primary">Subscribe Now</a>
            <a href="https://kinky-thots.xxx/free-content.php" class="cta-button secondary">Free Preview</a>
        </div>
        <div class="social-links">
            <a href="mailto:info@kinky-thots.xxx" class="social-link" title="Email"><img src="https://i.ibb.co/fVZdJCpS/email.png" width="30" alt="Email"></a>
            <a href="https://facebook.com/kinkythots" class="social-link" title="Facebook"><img src="https://i.ibb.co/WNG2YQhW/10462343.png" width="50" alt="Facebook"></a>
            <a href="https://x.com/kinkythotsmodel" class="social-link" title="X (Twitter)"><img src="https://i.ibb.co/j9Rqb5Lc/X-Logo.png" width="30" alt="X"></a>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
