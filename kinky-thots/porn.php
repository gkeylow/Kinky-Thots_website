<?php
// Dynamic video gallery with aspect ratio detection
$mediaDir = '/media/porn';
$webPath = '/porn';
$thumbDir = '/var/www/kinky-thots/porn/thumbnails';
$thumbWebPath = '/porn/thumbnails';

// CDN Configuration
$cdnEnabled = true;
$cdnBaseUrl = 'https://c5988z6295.r-cdn.com';  // PushrCDN push zone for videos

// Cache file for video metadata (speeds up page loads)
$cacheFile = '/var/www/kinky-thots/porn/video-metadata-cache.json';
$cacheMaxAge = 3600; // 1 hour

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

// Function to properly encode path for URLs
function encodeVideoPath($path) {
    $parts = explode('/', $path);
    $encodedParts = array_map('rawurlencode', $parts);
    return implode('/', $encodedParts);
}

// Function to check if thumbnail exists
function getThumbnailPath($videoPath, $thumbDir, $thumbWebPath, $mediaDir) {
    $relativePath = str_replace($mediaDir . '/', '', $videoPath);
    $thumbName = pathinfo($relativePath, PATHINFO_DIRNAME) . '/' . pathinfo($relativePath, PATHINFO_FILENAME) . '.jpg';
    $thumbName = ltrim($thumbName, './');
    $thumbFile = $thumbDir . '/' . $thumbName;
    
    if (file_exists($thumbFile)) {
        return $thumbWebPath . '/' . encodeVideoPath($thumbName);
    }
    return null;
}

// Function to get video dimensions using ffprobe
function getVideoDimensions($videoPath) {
    // Try ffprobe first (most accurate)
    $cmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($videoPath) . " 2>/dev/null";
    $output = trim(shell_exec($cmd));
    
    if ($output && strpos($output, 'x') !== false) {
        list($width, $height) = explode('x', $output);
        return ['width' => (int)$width, 'height' => (int)$height];
    }
    
    // Fallback: try to get from thumbnail if exists
    return null;
}

// Function to determine aspect ratio category
function getAspectRatioClass($width, $height) {
    if (!$width || !$height) {
        return 'aspect-unknown';
    }
    
    $ratio = $width / $height;
    
    // Portrait (9:16, vertical phone videos)
    if ($ratio < 0.7) {
        return 'aspect-portrait';
    }
    // Square-ish (1:1 to 4:3)
    elseif ($ratio >= 0.7 && $ratio < 1.4) {
        return 'aspect-square';
    }
    // Widescreen (16:9)
    elseif ($ratio >= 1.4 && $ratio < 2.0) {
        return 'aspect-wide';
    }
    // Ultra-wide (21:9 or wider)
    else {
        return 'aspect-ultrawide';
    }
}

// Function to load/save cache
function loadCache($cacheFile, $maxAge) {
    if (file_exists($cacheFile)) {
        $cacheTime = filemtime($cacheFile);
        if (time() - $cacheTime < $maxAge) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data) return $data;
        }
    }
    return [];
}

function saveCache($cacheFile, $data) {
    file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Recursively scan directory for video files
function scanVideos($dir) {
    $videos = [];
    $extensions = ['mp4'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $videos[] = $file->getPathname();
            }
        }
    }
    
    sort($videos);
    return $videos;
}

// Load cache and scan videos
$metadataCache = loadCache($cacheFile, $cacheMaxAge);
$videos = scanVideos($mediaDir);
$videoCount = count($videos);

// Process videos and get metadata
$videoData = [];
$cacheUpdated = false;

foreach ($videos as $videoPath) {
    $relativePath = str_replace($mediaDir . '/', '', $videoPath);
    $fileModTime = filemtime($videoPath);
    
    // Check cache
    if (isset($metadataCache[$relativePath]) && 
        isset($metadataCache[$relativePath]['mtime']) && 
        $metadataCache[$relativePath]['mtime'] === $fileModTime) {
        $dimensions = $metadataCache[$relativePath];
    } else {
        // Get fresh dimensions
        $dims = getVideoDimensions($videoPath);
        $dimensions = [
            'width' => $dims['width'] ?? null,
            'height' => $dims['height'] ?? null,
            'mtime' => $fileModTime
        ];
        $metadataCache[$relativePath] = $dimensions;
        $cacheUpdated = true;
    }
    
    $aspectClass = getAspectRatioClass($dimensions['width'], $dimensions['height']);
    
    // Use CDN URL if enabled, otherwise use origin
    // Try root path first (files uploaded via FTP are likely at root)
    $filename = basename($videoPath);
    $videoUrl = $cdnEnabled 
        ? $cdnBaseUrl . '/' . rawurlencode($filename)
        : $webPath . '/' . encodeVideoPath($relativePath);
    
    $videoData[] = [
        'path' => $videoPath,
        'relativePath' => $relativePath,
        'filename' => $filename,
        'encodedPath' => $videoUrl,
        'originPath' => $webPath . '/' . encodeVideoPath($relativePath),
        'cdnPathWithSubdir' => $cdnBaseUrl . '/' . encodeVideoPath($relativePath),
        'mimeType' => getMimeType($videoPath),
        'thumbnail' => getThumbnailPath($videoPath, $thumbDir, $thumbWebPath, $mediaDir),
        'width' => $dimensions['width'],
        'height' => $dimensions['height'],
        'aspectClass' => $aspectClass,
        'aspectRatio' => ($dimensions['width'] && $dimensions['height']) 
            ? round($dimensions['width'] / $dimensions['height'], 2) 
            : null
    ];
}

// Save cache if updated
if ($cacheUpdated) {
    saveCache($cacheFile, $metadataCache);
}

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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https: http: data:; media-src 'self' http: https: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; font-src 'self' https:; connect-src 'self' https: http:;">
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <title>Kinky Thots - Video Gallery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
Now            background: #181818;
            color: #f5f5f5;
            min-height: 100vh;
        }

        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: #181818;
            z-index: 1000;
            padding: 1rem 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f805a7;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo a {
            text-decoration: none;
            color: #f805a7;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #0bd0f3;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: #0bd0f3;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header {
            text-align: center;
            margin-top: 100px;
            margin-bottom: 30px;
            color: #fff;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #0bd0f3;
            font-size: 1rem;
        }

        .video-stats {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .video-count {
            color: #f805a7;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .aspect-stats {
            color: #666;
            font-size: 0.85rem;
        }

        .aspect-stats span {
            margin: 0 8px;
        }

        .aspect-stats .portrait { color: #e74c3c; }
        .aspect-stats .square { color: #f39c12; }
        .aspect-stats .wide { color: #2ecc71; }
        .aspect-stats .ultrawide { color: #9b59b6; }

        /* Masonry-style Mosaic Grid */
        .video-grid {
            column-count: 4;
            column-gap: 12px;
            padding-bottom: 80px;
        }

        .video-container {
            break-inside: avoid;
            margin-bottom: 12px;
            background: #181818;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .video-container:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(11, 208, 243, 0.3);
            z-index: 10;
        }

        /* Aspect ratio indicator */
        .video-container::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            z-index: 5;
            opacity: 0.8;
        }

        .video-container.aspect-portrait::before { background: #e74c3c; }
        .video-container.aspect-square::before { background: #f39c12; }
        .video-container.aspect-wide::before { background: #2ecc71; }
        .video-container.aspect-ultrawide::before { background: #9b59b6; }
        .video-container.aspect-unknown::before { background: #666; }

        /* Video element - natural aspect ratio */
        .video-container video {
            width: 100%;
            height: auto;
            display: block;
            background: #000;
        }

        /* Aspect ratio specific styling */
        .video-container.aspect-portrait {
            /* Portrait videos get special treatment */
        }

        .video-container.aspect-portrait video {
            max-height: 500px;
            object-fit: contain;
        }

        .video-container.aspect-ultrawide video {
            /* Ultra-wide videos */
        }

        .site-footer {
            background: #181818;
            padding: 2rem 0;
            margin-top: 4rem;
            border-top: 2px solid #f805a7;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            flex-wrap: wrap;
        }

        .footer-left p {
            color: #f5f5f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-right .footer-link {
            color: #0bd0f3;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-right .footer-link:hover {
            color: #fff;
        }

        /* Responsive column counts */
        @media (max-width: 1400px) {
            .video-grid {
                column-count: 3;
            }
        }

        @media (max-width: 1024px) {
            .video-grid {
                column-count: 2;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: #181818;
                padding: 1rem 0;
            }

            .nav-links.active {
                display: flex;
            }

            .nav-toggle {
                display: block;
            }

            .video-grid {
                column-count: 1;
            }

            .video-container.aspect-portrait video {
                max-height: 70vh;
            }

            .header h1 {
                font-size: 2rem;
            }

            .footer-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .aspect-stats {
                display: none;
            }
        }

        /* Filter buttons (optional - for future use) */
        .filter-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .filter-btn {
            background: #333;
            border: none;
            color: #fff;
            padding: 8px 16px;
            margin: 4px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #f805a7;
        }

        .filter-btn .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
    </style>
    <link rel="stylesheet" href="/assets/dropdown-nav.css">
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
                <button class="dropdown-toggle">Subscriptions</button>
                <ul class="dropdown-menu">
                    <li><a href="/subscriptions.html">Pricing & Plans</a></li>
                    <li><a href="/porn.php">Video Gallery</a></li>
                    <li><a href="https://onlyfans.com/kinkythots" target="_blank">OnlyFans</a></li>
                    <li><a href="https://sharesome.com/KinkyThots" target="_blank">Sharesome (Free)</a></li>
                </ul>
            </li>
            
            <li><a href="#" class="login-btn disabled" title="Coming Soon">Login</a></li>
        </ul>
        
        <button class="nav-toggle">☰</button>
    </div>
</nav>
<script src="/assets/dropdown-nav.js"></script>

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

        <!-- Optional: Filter buttons -->
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
                 data-width="<?php echo $video['width'] ?? ''; ?>"
                 data-height="<?php echo $video['height'] ?? ''; ?>">
                <video controls preload="metadata"
                    <?php if ($video['thumbnail']): ?> poster="<?php echo htmlspecialchars($video['thumbnail']); ?>"<?php endif; ?>
                    <?php if ($video['width'] && $video['height']): ?> 
                        width="<?php echo $video['width']; ?>" 
                        height="<?php echo $video['height']; ?>"
                    <?php endif; ?>>
                    <source src="<?php echo htmlspecialchars($video['encodedPath']); ?>" type="<?php echo $video['mimeType']; ?>">
                    Your browser does not support the video tag.
                </video>
            </div>
            <?php endforeach; ?>
        </div>
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

    <script>
        // Mobile navigation toggle
        document.querySelector('.nav-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Navbar scroll effect
        let lastScroll = 0;
        const navbar = document.getElementById('navbar');
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll <= 0) {
                navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.2)';
            } else {
                navbar.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.4)';
            }
            
            lastScroll = currentScroll;
        });

        // Filter functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const videoContainers = document.querySelectorAll('.video-container');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter videos
                videoContainers.forEach(container => {
                    if (filter === 'all' || container.dataset.aspect === filter) {
                        container.style.display = 'block';
                    } else {
                        container.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
