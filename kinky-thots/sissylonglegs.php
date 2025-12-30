<?php
// Get random images from kinky-thots-animated-gifs directory
$imageDir = '/media/porn/kinky-thots-animated-gifs';
$webPath = '/porn/kinky-thots-animated-gifs';
$images = [];

$extensions = ['gif', 'jpg', 'jpeg', 'png'];
$files = scandir($imageDir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $extensions)) {
        $images[] = $file;
    }
}

shuffle($images);
$heroImages = array_slice($images, 0, 5);
$portfolioImages = array_slice($images, 5, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="kinky-thots"/>
    <meta name="copyright" content="kinky-thots"/>
    <meta name="robots" content="index,nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https: http: data:; media-src 'self' http: https: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https:; connect-src 'self' https: http:;">
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <title>Kinky Thots - Sissy Long Legs</title>
    <!-- Built CSS (Vite + Tailwind) -->
    <link rel="stylesheet" href="/assets/dist/css/main.css">
    <link rel="stylesheet" href="/assets/dist/css/index.css">
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

<!-- Hero Section -->
<section class="hero" id="home">
    <!-- Dynamic Background Slideshow -->
    <div class="hero-slideshow">
        <?php foreach ($heroImages as $index => $image): ?>
        <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
            <img loading="eager" style="width: 100%; height: auto; display: block;" src="<?php echo htmlspecialchars($webPath . '/' . rawurlencode($image)); ?>" alt="Sissy Long Legs">
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="hero-content">
        <h1 class="dissolve">Sissy Long Legs</h1>
        <p class="hero-tagline">Your Favorite Bratty PAWG Finding Her Confidence</p>
        <p>Raised in a strict, traditional household where “good girls don’t,” Sissy spent her teenage years being told a woman’s place was in the kitchen. As she grew up, she realized her place was wherever she chose. What began as simple rebellion against a conservative upbringing became a journey of self-expression, body confidence, and creative performance. Now 32, this spirited PAWG has spent nearly two decades refining her craft on camera—and she’s genuinely great at it.</p>
        <p>Don’t let her innocent face fool you. Behind those shy eyes is a woman learning to own her spotlight while still figuring things out. She knows she has curves that turn heads, but she’s still on the path to believing in her power. Watch her balance sweet and sassy, playful and bold, as she grows from a nervous good girl into a confident leading lady.</p>
        <div class="hero-buttons">
            <a href="https://onlyfans.com/kinkythots" target="_blank" class="cta-button primary">Subscribe</a>
            <a href="https://sharesome.com/KinkyThots" target="_blank" class="cta-button secondary">Sneak Peak</a>
        </div>
    </div>
</section>

<!-- Skills Section - What She Does Best -->
<section class="skills" id="skills">
    <div class="container">
        <div class="section-header">
            <h2>What She Does Best</h2>
            <p>Sissy's specialties that keep fans coming back</p>
        </div>
        <div class="skills-grid">
            <div class="skill-card">
                <div class="hover-image"></div>
                <div class="skill-content">
                    <div class="skill-icon"><img src="https://i.ibb.co/pjzNBPf0/camera.png" width="75px"></div>
                    <h3>Solo Content</h3>
                    <p>Watch her explore herself with toys, fingers, and pure desire. From teasing strips to intense orgasms, she holds nothing back.</p>
                </div>
            </div>
            <div class="skill-card">
                <div class="hover-image"></div>
                <div class="skill-content">
                    <div class="skill-icon"><img src="https://i.ibb.co/yn1L7TpR/video.png" width="75px"></div>
                    <h3>Blowjob Queen</h3>
                    <p>Her true calling. Sloppy, enthusiastic, and utterly devoted. Watch her worship cock like it's her favorite meal.</p>
                </div>
            </div>
            <div class="skill-card">
                <div class="hover-image"></div>
                <div class="skill-content">
                    <div class="skill-icon"><img src="https://i.ibb.co/qLHz4Fcj/webcam.png" width="75px"></div>
                    <h3>Live Shows</h3>
                    <p>Catch her live on cam where anything can happen. Interactive, spontaneous, and always ready to please her audience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Section - Free Samples -->
<section class="portfolio" id="portfolio">
    <div class="container">
        <div class="section-header">
            <h2>Free Samples</h2>
            <p>A taste of what's waiting for subscribers</p>
        </div>
        <div class="portfolio-grid">
            <?php foreach ($portfolioImages as $image): ?>
            <div class="portfolio-item">
                <div class="image-container">
                    <img src="<?php echo htmlspecialchars($webPath . '/' . rawurlencode($image)); ?>" alt="Sissy Long Legs">
                    <div class="hover-text">
                        <h4>Want More?</h4>
                        <p>Subscribe to see the full set</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Gallery Section - Recent Selfies -->
<section class="gallery-section" id="gallery">
    <div class="container">
        <div class="section-header">
            <h2>Recent Selfies</h2>
            <p><b>New Work Samples</b></p>
            <p>We will be uploading an image or 2 each time we've created new content. Please subscribe to see full photo/video sets created</p>
        </div>
        <div id="gallery-grid-sissy" class="gallery-grid mosaic-grid"></div>
    </div>
</section>
<!-- Built JS (Vite) -->
<script type="module" src="/assets/dist/js/sissylonglegs.js"></script>
<!-- Legacy gallery script (pending full migration) -->
<script src="/assets/sissygallery.js?v=1765148000"></script>

<!-- Lightbox overlay for sissylonglegs gallery -->
<div id="lightbox-overlay-sissy" class="lightbox-overlay" style="display:none;">
    <span class="lightbox-close" id="lightbox-close-sissy">&times;</span>
    <div id="lightbox-content-sissy">
        <img id="lightbox-img-sissy" src="" alt="Full Image" style="display:none;" />
        <video id="lightbox-video-sissy" controls style="display:none; max-width:90vw; max-height:80vh;"></video>
    </div>
    <div class="lightbox-nav">
        <button class="lightbox-nav-btn" id="lightbox-prev-sissy">&#8592;</button>
        <button class="lightbox-nav-btn" id="lightbox-next-sissy">&#8594;</button>
    </div>
</div>

<!-- Contact Section -->
<section class="contact" id="contact">
    <div class="container">
        <div class="section-header">
            <h2>Ready for NSFW Content?</h2>
            <p>Come subscribe to our content!</p>
        </div>
        <div class="contact-buttons">
            <a href="https://onlyfans.com/kinkythots" target="_blank" class="cta-button primary">Full Portfolio</a>
            <a href="https://sharesome.com/KinkyThots" target="_blank" class="cta-button secondary">Free View</a>
        </div>
        <div class="social-links">
            <a href="mailto:info@kinky-thots.com" class="social-link"><img src="https://i.ibb.co/fVZdJCpS/email.png" width="50px"></a>
            <a href="https://facebook.com/kinkythots" class="social-link"><img src="https://i.ibb.co/WNG2YQhW/10462343.png" width="100px"></a>
            <a href="https://x.com/kinkythotsmodel" class="social-link"><img src="https://i.ibb.co/j9Rqb5Lc/X-Logo.png" width="50px"></a>
            <a href="https://kinky-thots.bdsmlr.com" class="social-link"><img src="https://i.ibb.co/gL33B4Tx/bdsmlr.png" width="50px"></a>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-left">
                <p>© 2025 <a href="/index.html">Kinky-Thots</a> <img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" width="25px">All rights reserved.</p>
            </div>
            <div class="footer-right">
                <a href="https://kinky-thots.com/terms.html" class="footer-link">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
