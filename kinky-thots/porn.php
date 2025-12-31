<?php
// Dynamic video gallery using static manifest (no local file scanning required)
// This allows videos to be served entirely from CDN without local copies

// Configuration
$manifestFile = __DIR__ . '/data/video-manifest.json';
$cdnEnabled = true;
$cdnBaseUrl = 'https://6318.s3.nvme.de01.sonic.r-cdn.com';

// Fallback to local scanning if manifest doesn't exist
$useManifest = file_exists($manifestFile);

// Load video data
if ($useManifest) {
    $manifest = json_decode(file_get_contents($manifestFile), true);
    $videos = $manifest['videos'] ?? [];
    $cdnBaseUrl = $manifest['cdn_base_url'] ?? $cdnBaseUrl;
} else {
    // Legacy: scan local directory (only if manifest missing)
    $mediaDir = '/media/porn';
    $videos = [];
    
    if (is_dir($mediaDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($mediaDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) == 'mp4') {
                $videos[] = [
                    'filename' => basename($file->getPathname()),
                    'width' => null,
                    'height' => null,
                    'on_cdn' => true
                ];
            }
        }
        
        usort($videos, function($a, $b) {
            return strcasecmp($a['filename'], $b['filename']);
        });
    }
}

// Function to get MIME type
function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska'
    ];
    return $mimeTypes[$ext] ?? 'video/mp4';
}

// Function to determine aspect ratio category
function getAspectRatioClass($width, $height) {
    if (!$width || !$height) {
        return 'aspect-unknown';
    }
    
    $ratio = $width / $height;
    
    if ($ratio < 0.7) {
        return 'aspect-portrait';
    } elseif ($ratio >= 0.7 && $ratio < 1.4) {
        return 'aspect-square';
    } elseif ($ratio >= 1.4 && $ratio < 2.0) {
        return 'aspect-wide';
    } else {
        return 'aspect-ultrawide';
    }
}

// Thumbnail configuration
$thumbnailDir = '/assets/thumbnails/';
$thumbnailPath = __DIR__ . '/assets/thumbnails/';

// Process videos
$videoData = [];
foreach ($videos as $video) {
    $filename = $video['filename'];
    $width = $video['width'] ?? null;
    $height = $video['height'] ?? null;
    $onCdn = $video['on_cdn'] ?? true;

    // Skip videos not on CDN if CDN is enabled
    if ($cdnEnabled && !$onCdn) {
        continue;
    }

    $aspectClass = getAspectRatioClass($width, $height);

    // Build CDN URL
    $videoUrl = $cdnEnabled
        ? $cdnBaseUrl . '/' . rawurlencode($filename)
        : '/porn/kinky-thots-shorts/' . rawurlencode($filename);

    // Build thumbnail URL (local file for fast loading)
    $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbUrl = file_exists($thumbnailPath . $thumbFilename)
        ? $thumbnailDir . rawurlencode($thumbFilename)
        : null;

    $videoData[] = [
        'filename' => $filename,
        'url' => $videoUrl,
        'thumbnailUrl' => $thumbUrl,
        'mimeType' => getMimeType($filename),
        'width' => $width,
        'height' => $height,
        'aspectClass' => $aspectClass,
        'aspectRatio' => ($width && $height) ? round($width / $height, 2) : null
    ];
}

$videoCount = count($videoData);

// Count by aspect ratio
$aspectCounts = [
    'portrait' => 0,
    'square' => 0,
    'wide' => 0,
    'ultrawide' => 0,
    'unknown' => 0
];
foreach ($videoData as $v) {
    $key = str_replace('aspect-', '', $v['aspectClass']);
    if (isset($aspectCounts[$key])) {
        $aspectCounts[$key]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="kinky-thots"/>
    <meta name="copyright" content="kinky-thots"/>
    <meta name="robots" content="noindex, nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https: http: data:; media-src 'self' http: https: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https:; connect-src 'self' https: http:;">
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <title>Kinky Thots - Video Gallery</title>
    <!-- Built CSS (Vite + Tailwind) -->
    <link rel="stylesheet" href="/assets/dist/css/main.css">
    <link rel="stylesheet" href="/assets/dist/css/media-gallery.css">
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
                <button class="dropdown-toggle">About</button>
                <ul class="dropdown-menu">
                    <li><a href="/index.html#about">About Us</a></li>
                    <li><a href="/index.html#skills">Our Skills</a></li>
                    <li><a href="/index.html#portfolio">Portfolio</a></li>
                    <li><a href="/index.html#contact">Contact</a></li>
                </ul>
            </li>

            <li class="dropdown">
                <button class="dropdown-toggle">Models</button>
                <ul class="dropdown-menu">
                    <li><a href="/sissylonglegs.html">Sissy Long Legs</a></li>
                    <li><a href="/bustersherry.html">Buster Sherry</a></li>
                </ul>
            </li>

            <li class="dropdown">
                <button class="dropdown-toggle">Media</button>
                <ul class="dropdown-menu">
                    <li><a href="/porn.php">Video Gallery</a></li>
                    <li><a href="/gallery.php">Photo Gallery</a></li>
                    <li><a href="/live.html">Live Cam</a></li>
                    <li><a href="https://onlyfans.com/kinkythots" target="_blank">Full Content</a></li>
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
<!-- Built JS (Vite) - Navigation -->
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
        <div class="header">
            <h1>Video Gallery</h1>
            <p>Exclusive Content Collection</p>
        </div>

        <!-- Subscription banner (shown for non-premium users) -->
        <div id="subscriptionBanner" class="subscription-banner" style="display: none;">
            <div class="subscription-banner-text">
                <strong id="bannerAccessText">Unlock all content</strong><br>
                <span id="bannerSubText">Subscribe to access premium videos</span>
            </div>
            <a href="/subscriptions.html" class="subscription-banner-btn">View Plans</a>
        </div>

        <div class="video-stats">
            <div class="video-count">
                <?php echo $videoCount; ?> video<?php echo $videoCount !== 1 ? 's' : ''; ?> available
            </div>
            <div class="aspect-stats">
                <?php if ($aspectCounts['portrait'] > 0): ?>
                    <span class="portrait">● <?php echo $aspectCounts['portrait']; ?> Portrait</span>
                <?php endif; ?>
                <?php if ($aspectCounts['square'] > 0): ?>
                    <span class="square">● <?php echo $aspectCounts['square']; ?> Square</span>
                <?php endif; ?>
                <?php if ($aspectCounts['wide'] > 0): ?>
                    <span class="wide">● <?php echo $aspectCounts['wide']; ?> Widescreen</span>
                <?php endif; ?>
                <?php if ($aspectCounts['ultrawide'] > 0): ?>
                    <span class="ultrawide">● <?php echo $aspectCounts['ultrawide']; ?> Ultra-wide</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter buttons -->
        <div class="filter-bar">
            <button class="filter-btn active" data-filter="all">All</button>
            <?php if ($aspectCounts['portrait'] > 0): ?>
                <button class="filter-btn" data-filter="portrait"><span class="dot" style="background:#e74c3c"></span>Portrait</button>
            <?php endif; ?>
            <?php if ($aspectCounts['square'] > 0): ?>
                <button class="filter-btn" data-filter="square"><span class="dot" style="background:#f39c12"></span>Square</button>
            <?php endif; ?>
            <?php if ($aspectCounts['wide'] > 0): ?>
                <button class="filter-btn" data-filter="wide"><span class="dot" style="background:#2ecc71"></span>Widescreen</button>
            <?php endif; ?>
            <?php if ($aspectCounts['ultrawide'] > 0): ?>
                <button class="filter-btn" data-filter="ultrawide"><span class="dot" style="background:#9b59b6"></span>Ultra-wide</button>
            <?php endif; ?>
        </div>

        <div class="video-grid" id="videoGrid">
            <?php foreach ($videoData as $index => $video): ?>
            <div class="video-container <?php echo $video['aspectClass']; ?>"
                 data-aspect="<?php echo str_replace('aspect-', '', $video['aspectClass']); ?>"
                 data-ratio="<?php echo $video['aspectRatio'] ?? 'unknown'; ?>"
                 data-video-url="<?php echo htmlspecialchars($video['url']); ?>"
                 data-video-type="<?php echo $video['mimeType']; ?>"
                 data-index="<?php echo $index; ?>">
                <!-- Lock overlay (shown via JS for gated content) -->
                <div class="lock-overlay">
                    <div class="lock-icon">🔒</div>
                    <div class="lock-text">Premium Content</div>
                    <div class="lock-tier">Upgrade to unlock</div>
                    <a href="/subscriptions.html" class="lock-upgrade-btn">Subscribe</a>
                </div>
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
                <video controls preload="metadata"
                    <?php if ($video['width'] && $video['height']): ?>
                        width="<?php echo $video['width']; ?>"
                        height="<?php echo $video['height']; ?>"
                    <?php endif; ?>>
                    <source src="<?php echo htmlspecialchars($video['url']); ?>" type="<?php echo $video['mimeType']; ?>">
                    Your browser does not support the video tag.
                </video>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($useManifest): ?>
        <div class="manifest-info">
            Serving from CDN manifest
        </div>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <p>© 2025 <a href="/index.html">Kinky-Thots</a> <img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" width="25px"> All rights reserved.</p>
                </div>
                <div class="footer-right">
                    <a href="https://kinky-thots.com/terms.html" class="footer-link">Terms & Conditions</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Built JS (Vite) -->
    <script type="module" src="/assets/dist/js/porn.js"></script>

    <!-- Content Gating Script -->
    <script>
    (function() {
        const AUTH_TOKEN_KEY = 'kt_auth_token';
        const AUTH_USER_KEY = 'kt_auth_user';

        // Subscription tier access levels (percentage of content)
        const TIER_ACCESS = {
            free: 0.2,      // 20%
            basic: 0.6,     // 60%
            premium: 1.0,   // 100%
            vip: 1.0        // 100%
        };

        function initContentGating() {
            const user = JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null');
            const token = localStorage.getItem(AUTH_TOKEN_KEY);
            const isLoggedIn = user && token;
            const userTier = user?.subscription_tier || 'free';
            const accessLevel = TIER_ACCESS[userTier] || TIER_ACCESS.free;

            // Get all video containers
            const videos = document.querySelectorAll('.video-container[data-index]');
            const totalVideos = videos.length;
            const accessibleCount = Math.ceil(totalVideos * accessLevel);

            // Show subscription banner for non-premium users
            const banner = document.getElementById('subscriptionBanner');
            if (banner && accessLevel < 1.0) {
                banner.style.display = 'flex';
                const accessText = document.getElementById('bannerAccessText');
                const subText = document.getElementById('bannerSubText');

                if (isLoggedIn) {
                    const lockedCount = totalVideos - accessibleCount;
                    accessText.textContent = `${lockedCount} videos locked`;
                    subText.textContent = `Upgrade to ${userTier === 'free' ? 'Basic ($5/mo)' : 'Premium ($10/mo)'} for more access`;
                } else {
                    accessText.textContent = 'Sign in to unlock content';
                    subText.textContent = 'Free members get 20% access, Premium gets 100%';
                }
            }

            // Apply content locking
            videos.forEach((container, index) => {
                if (index >= accessibleCount) {
                    container.classList.add('content-locked');

                    // Update lock overlay text based on required tier
                    const lockTier = container.querySelector('.lock-tier');
                    if (lockTier) {
                        if (accessLevel < 0.6) {
                            lockTier.textContent = 'Basic ($5/mo) or higher';
                        } else {
                            lockTier.textContent = 'Premium ($10/mo) required';
                        }
                    }
                }
            });

            // Log access info (debug)
            console.log(`Content Gating: ${userTier} tier, ${accessibleCount}/${totalVideos} videos accessible`);
        }

        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initContentGating);
        } else {
            initContentGating();
        }
    })();
    </script>

    <!-- Elfsight Age Verification -->
    <script src="https://static.elfsight.com/platform/platform.js" async></script>
    <div class="elfsight-app-ea2f58c6-6128-4e92-b2d9-c0b5c09769c3" data-elfsight-app-lazy></div>
</body>
</html>
