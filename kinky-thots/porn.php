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
                    <li><a href="/sissylonglegs.php">Sissy Long Legs</a></li>
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

            <li><a href="#" class="login-btn disabled" title="Coming Soon">Login</a></li>
        </ul>

        <button class="nav-toggle" aria-label="Toggle navigation menu">&#9776;</button>
    </div>
</nav>
<!-- Built JS (Vite) - Navigation -->
<script type="module" src="/assets/dist/js/main.js"></script>

    <div class="container">
        <div class="header">
            <h1>Video Gallery</h1>
            <p>Exclusive Content Collection</p>
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
            <?php foreach ($videoData as $video): ?>
            <div class="video-container <?php echo $video['aspectClass']; ?>"
                 data-aspect="<?php echo str_replace('aspect-', '', $video['aspectClass']); ?>"
                 data-ratio="<?php echo $video['aspectRatio'] ?? 'unknown'; ?>"
                 data-video-url="<?php echo htmlspecialchars($video['url']); ?>"
                 data-video-type="<?php echo $video['mimeType']; ?>">
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
</body>
</html>
