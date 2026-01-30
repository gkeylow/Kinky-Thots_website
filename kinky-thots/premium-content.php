<?php
// Premium Content - Videos over 5 minutes
// Duration-based content tier: Premium (> 300 seconds)

require_once __DIR__ . '/includes/s3-helper.php';

$manifestFile = __DIR__ . '/data/video-manifest.json';
$thumbnailDir = '/assets/thumbnails/';
$thumbnailPath = __DIR__ . '/assets/thumbnails/';

// Load and filter videos
$videos = [];
if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true);

    foreach ($manifest['videos'] ?? [] as $video) {
        $duration = $video['duration_seconds'] ?? 60;
        // Premium tier: videos over 300 seconds (5+ minutes)
        if ($duration > 300 && ($video['on_cdn'] ?? true)) {
            $filename = $video['filename'];
            $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

            $videos[] = [
                'filename' => $filename,
                'url' => getCdnUrl($filename), // CDN pull zone URL
                'thumbnailUrl' => file_exists($thumbnailPath . $thumbFilename)
                    ? $thumbnailDir . rawurlencode($thumbFilename)
                    : null,
                'duration' => $duration,
                'size_bytes' => $video['size_bytes'] ?? 0
            ];
        }
    }
}

// Sort by duration (longest first for premium)
usort($videos, fn($a, $b) => $b['duration'] - $a['duration']);
$videoCount = count($videos);

// Format duration as MM:SS or H:MM:SS
function formatDuration($seconds) {
    if ($seconds >= 3600) {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d:%02d', $hours, $mins, $secs);
    }
    $mins = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%d:%02d', $mins, $secs);
}

// Calculate total content duration
$totalDuration = array_sum(array_column($videos, 'duration'));
$totalHours = floor($totalDuration / 3600);
$totalMins = floor(($totalDuration % 3600) / 60);

// Page configuration for header include
$pageTitle = 'Premium Content - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageCss = ['/assets/dist/css/media-gallery.css'];
$pageStyles = '
    .container {
        padding-top: 100px;
    }
    .tier-header {
        text-align: center;
        padding: 2rem 1rem;
        background: linear-gradient(135deg, rgba(248, 5, 167, 0.1), rgba(11, 208, 243, 0.1));
        border-bottom: 1px solid rgba(248, 5, 167, 0.3);
        margin-bottom: 2rem;
    }
    .tier-badge {
        display: inline-block;
        padding: 6px 16px;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        color: #fff;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .tier-badge.lifetime {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #000;
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
        color: #f805a7;
        font-weight: 600;
    }
    .content-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    .content-stats .stat {
        text-align: center;
    }
    .content-stats .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .content-stats .stat-label {
        font-size: 0.85rem;
        color: #888;
    }
    .subscribe-cta {
        background: linear-gradient(135deg, rgba(248, 5, 167, 0.15), rgba(11, 208, 243, 0.15));
        border: 2px solid rgba(248, 5, 167, 0.5);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        margin: 2rem auto;
        max-width: 700px;
    }
    .subscribe-cta h3 {
        color: #fff;
        margin: 0 0 0.5rem;
        font-size: 1.5rem;
    }
    .subscribe-cta p {
        color: #aaa;
        margin: 0 0 1.5rem;
    }
    .subscribe-btns {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .subscribe-btn {
        display: inline-block;
        padding: 14px 36px;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: transform 0.2s, opacity 0.2s;
    }
    .subscribe-btn:hover {
        transform: scale(1.05);
        opacity: 0.9;
    }
    .subscribe-btn.lifetime {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #000;
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
    .duration-badge.long {
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
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
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border-color: #f805a7;
        color: #fff;
    }
    .lock-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        transform: none;
        width: auto;
        max-width: none;
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
    .exclusive-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        color: #fff;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        z-index: 5;
    }
';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="tier-header">
        <span class="tier-badge">PREMIUM - <span class="price">$15/mo</span></span>
        <span class="tier-badge lifetime">LIFETIME $250</span>
        <h1>Full-Length Videos</h1>
        <p>Exclusive content over 5 minutes - Our best videos</p>
        <div class="content-stats">
            <div class="stat">
                <div class="stat-value"><?php echo $videoCount; ?></div>
                <div class="stat-label">Premium Videos</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $totalHours > 0 ? $totalHours . 'h ' . $totalMins . 'm' : $totalMins . ' min'; ?></div>
                <div class="stat-label">Total Content</div>
            </div>
        </div>
    </div>

    <div class="content-nav">
        <a href="/free-content.php">Free Teasers</a>
        <a href="/basic-content.php">Extended</a>
        <a href="/premium-content.php" class="active">Full Access</a>
    </div>

    <div id="subscribeCta" class="subscribe-cta" style="display: none;">
        <h3>Unlock Full-Length Content</h3>
        <p>Get unlimited access to all premium videos with a Premium subscription</p>
        <div class="subscribe-btns">
            <a href="/subscriptions.php?tier=premium" class="subscribe-btn">Subscribe $15/mo</a>
            <a href="/subscriptions.php?tier=lifetime" class="subscribe-btn lifetime">Lifetime $250</a>
        </div>
    </div>

    <div class="video-stats">
        <div class="video-count">
            <?php echo $videoCount; ?> full-length video<?php echo $videoCount !== 1 ? 's' : ''; ?> available
        </div>
    </div>

    <div class="sort-bar">
        <select id="sortSelect">
            <option value="longest">Longest First</option>
            <option value="shortest">Shortest First</option>
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
                <div class="lock-text">Premium Content</div>
                <div class="lock-tier">Subscribe to Premium ($15/mo) to unlock</div>
                <a href="/subscriptions.php" class="lock-upgrade-btn">Subscribe</a>
            </div>
            <span class="exclusive-badge">Premium</span>
            <span class="duration-badge<?php echo $video['duration'] >= 600 ? ' long' : ''; ?>"><?php echo formatDuration($video['duration']); ?></span>
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
        <p>No premium videos available at this time.</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script type="module" src="/assets/dist/js/porn.js"></script>
<script>
// Content gating for Premium tier
(function() {
    const user = JSON.parse(localStorage.getItem('kt_auth_user') || 'null');
    const tier = user?.subscription_tier || 'free';

    // Tiers that can access Premium content
    const hasAccess = ['premium', 'lifetime', 'vip'].includes(tier);

    if (!hasAccess) {
        // Show subscribe CTA
        document.getElementById('subscribeCta').style.display = 'block';

        // Lock all videos
        document.querySelectorAll('.video-container').forEach(container => {
            container.classList.add('content-locked');
        });
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

<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
