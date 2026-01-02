<?php
// Basic Content - Videos 1-5 minutes
// Duration-based content tier: Basic (60-300 seconds)

$manifestFile = __DIR__ . '/data/video-manifest.json';
$cdnBaseUrl = 'https://6318.s3.nvme.de01.sonic.r-cdn.com';
$thumbnailDir = '/assets/thumbnails/';
$thumbnailPath = __DIR__ . '/assets/thumbnails/';

// Load and filter videos
$videos = [];
if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true);
    $cdnBaseUrl = $manifest['cdn_base_url'] ?? $cdnBaseUrl;

    foreach ($manifest['videos'] ?? [] as $video) {
        $duration = $video['duration_seconds'] ?? 60;
        // Basic tier: videos 60-300 seconds (1-5 minutes)
        if ($duration >= 60 && $duration <= 300 && ($video['on_cdn'] ?? true)) {
            $filename = $video['filename'];
            $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

            $videos[] = [
                'filename' => $filename,
                'url' => $cdnBaseUrl . '/' . rawurlencode($filename),
                'thumbnailUrl' => file_exists($thumbnailPath . $thumbFilename)
                    ? $thumbnailDir . rawurlencode($thumbFilename)
                    : null,
                'duration' => $duration,
                'size_bytes' => $video['size_bytes'] ?? 0
            ];
        }
    }
}

// Sort by duration (shortest first)
usort($videos, fn($a, $b) => $a['duration'] - $b['duration']);
$videoCount = count($videos);

// Format duration as MM:SS
function formatDuration($seconds) {
    $mins = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%d:%02d', $mins, $secs);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Extended Videos - Kinky Thots</title>
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="/assets/dist/css/main.css">
    <link rel="stylesheet" href="/assets/dist/css/media-gallery.css">
    <style>
        .tier-header {
            text-align: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(11, 208, 243, 0.1));
            border-bottom: 1px solid rgba(52, 152, 219, 0.3);
            margin-bottom: 2rem;
        }
        .tier-badge {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .tier-header h1 {
            font-size: 2rem;
            margin: 0.5rem 0;
            color: #fff;
        }
        .tier-header p {
            color: #888;
            margin: 0;
        }
        .tier-header .price {
            color: #3498db;
            font-weight: 600;
        }
        .upgrade-cta {
            background: linear-gradient(135deg, rgba(248, 5, 167, 0.1), rgba(11, 208, 243, 0.1));
            border: 1px solid rgba(248, 5, 167, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin: 2rem auto;
            max-width: 600px;
        }
        .upgrade-cta h3 {
            color: #fff;
            margin: 0 0 0.5rem;
        }
        .upgrade-cta p {
            color: #888;
            margin: 0 0 1rem;
        }
        .upgrade-btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s, opacity 0.2s;
        }
        .upgrade-btn:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        .duration-badge {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.85);
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 5;
        }
        .sort-bar {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }
        .sort-bar select {
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }
        .content-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .content-nav a {
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #888;
            text-decoration: none;
            transition: all 0.2s;
        }
        .content-nav a:hover {
            border-color: #0bd0f3;
            color: #fff;
        }
        .content-nav a.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-color: #3498db;
            color: #fff;
        }
        .content-nav a.locked {
            opacity: 0.5;
        }
        .content-nav a .nav-lock {
            margin-left: 6px;
            font-size: 0.8em;
        }
        .lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(5px);
        }
        .content-locked .lock-overlay {
            display: flex;
        }
        .lock-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .lock-text {
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .lock-tier {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .lock-upgrade-btn {
            padding: 8px 20px;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<nav id="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.html">Kinky-Thots<img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" width="50px"></a>
        </div>
        <ul class="nav-links">
            <li><a href="/index.html">Home</a></li>
            <li class="dropdown">
                <button class="dropdown-toggle">Content</button>
                <ul class="dropdown-menu">
                    <li><a href="/free-content.php">Free Teasers</a></li>
                    <li><a href="/basic-content.php">Extended Videos</a></li>
                    <li><a href="/premium-content.php">Full Access</a></li>
                    <li><a href="/gallery.php">Photo Gallery</a></li>
                    <li><a href="/live.html">Live Cam</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <button class="dropdown-toggle">Models</button>
                <ul class="dropdown-menu">
                    <li><a href="/sissylonglegs.html">Sissy Long Legs</a></li>
                    <li><a href="/bustersherry.html">Buster Sherry</a></li>
                </ul>
            </li>
            <li class="dropdown" id="userDropdown" style="display: none;">
                <button class="dropdown-toggle" id="userTrigger">Account</button>
                <ul class="dropdown-menu">
                    <li><a href="/profile.html">My Profile</a></li>
                    <li><a href="/subscriptions.html">Subscription</a></li>
                    <li><a href="#" id="logoutLink">Logout</a></li>
                </ul>
            </li>
            <li id="loginItem"><a href="/live.html" class="login-btn" id="authTrigger">Login</a></li>
        </ul>
        <button class="nav-toggle" aria-label="Toggle navigation menu">&#9776;</button>
    </div>
</nav>
<script type="module" src="/assets/dist/js/main.js"></script>
<script>
(function() {
    const user = JSON.parse(localStorage.getItem('kt_auth_user') || 'null');
    if (user) {
        document.getElementById('loginItem').style.display = 'none';
        document.getElementById('userDropdown').style.display = 'block';
        document.getElementById('userTrigger').textContent = user.username;
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('kt_auth_token');
            localStorage.removeItem('kt_auth_user');
            window.location.reload();
        });
    }
})();
</script>

<div class="container">
    <div class="tier-header">
        <span class="tier-badge">BASIC - <span class="price">$8/mo</span></span>
        <h1>Extended Videos</h1>
        <p>Videos 1-5 minutes - Perfect for exploring more content</p>
    </div>

    <div class="content-nav">
        <a href="/free-content.php">Free Teasers</a>
        <a href="/basic-content.php" class="active">Extended</a>
        <a href="/premium-content.php" id="premiumNav">Full Access <span class="nav-lock"></span></a>
    </div>

    <div class="video-stats">
        <div class="video-count">
            <?php echo $videoCount; ?> extended video<?php echo $videoCount !== 1 ? 's' : ''; ?> available
        </div>
    </div>

    <div class="sort-bar">
        <select id="sortSelect">
            <option value="shortest">Shortest First</option>
            <option value="longest">Longest First</option>
            <option value="name">Alphabetical</option>
        </select>
    </div>

    <div class="video-grid" id="videoGrid">
        <?php foreach ($videos as $index => $video): ?>
        <div class="video-container"
             data-video-url="<?php echo htmlspecialchars($video['url']); ?>"
             data-video-type="video/mp4"
             data-duration="<?php echo $video['duration']; ?>"
             data-name="<?php echo htmlspecialchars($video['filename']); ?>"
             data-index="<?php echo $index; ?>">
            <div class="lock-overlay">
                <div class="lock-icon">&#128274;</div>
                <div class="lock-text">Basic Content</div>
                <div class="lock-tier">Subscribe to Basic ($8/mo) to unlock</div>
                <a href="/subscriptions.html" class="lock-upgrade-btn">Subscribe</a>
            </div>
            <span class="duration-badge"><?php echo formatDuration($video['duration']); ?></span>
            <?php if ($video['thumbnailUrl']): ?>
            <div class="video-thumbnail" onclick="openLightbox(this)">
                <img src="<?php echo htmlspecialchars($video['thumbnailUrl']); ?>"
                     alt="Video thumbnail"
                     loading="lazy">
                <div class="play-button">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
            <?php else: ?>
            <video controls preload="metadata">
                <source src="<?php echo htmlspecialchars($video['url']); ?>" type="video/mp4">
            </video>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($videoCount === 0): ?>
    <div class="no-content">
        <p>No extended videos available at this time.</p>
    </div>
    <?php endif; ?>

    <div class="upgrade-cta">
        <h3>Ready for the full experience?</h3>
        <p>Upgrade to Premium ($15/mo) for unlimited access to all videos over 5 minutes!</p>
        <a href="/subscriptions.html" class="upgrade-btn">Upgrade to Premium</a>
    </div>
</div>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-left">
                <p>&copy; 2025 <a href="/index.html">Kinky-Thots</a> All rights reserved.</p>
            </div>
            <div class="footer-right">
                <a href="/terms.html" class="footer-link">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer>

<script type="module" src="/assets/dist/js/porn.js"></script>
<script>
// Content gating for Basic tier
(function() {
    const user = JSON.parse(localStorage.getItem('kt_auth_user') || 'null');
    const tier = user?.subscription_tier || 'free';

    // Tiers that can access Basic content
    const hasAccess = ['basic', 'premium', 'lifetime', 'vip'].includes(tier);

    if (!hasAccess) {
        // Lock all videos for free users
        document.querySelectorAll('.video-container').forEach(container => {
            container.classList.add('content-locked');
        });
    }

    // Update premium nav lock
    const premiumNav = document.getElementById('premiumNav');
    if (['free', 'basic'].includes(tier)) {
        premiumNav.querySelector('.nav-lock').textContent = '&#128274;';
        premiumNav.classList.add('locked');
    }
})();

// Sort functionality
document.getElementById('sortSelect').addEventListener('change', function() {
    const grid = document.getElementById('videoGrid');
    const containers = Array.from(grid.querySelectorAll('.video-container'));

    containers.sort((a, b) => {
        switch(this.value) {
            case 'shortest':
                return parseInt(a.dataset.duration) - parseInt(b.dataset.duration);
            case 'longest':
                return parseInt(b.dataset.duration) - parseInt(a.dataset.duration);
            case 'name':
                return a.dataset.name.localeCompare(b.dataset.name);
        }
    });

    containers.forEach(c => grid.appendChild(c));
});
</script>

<script src="https://static.elfsight.com/platform/platform.js" async></script>
<div class="elfsight-app-ea2f58c6-6128-4e92-b2d9-c0b5c09769c3" data-elfsight-app-lazy></div>
</body>
</html>
