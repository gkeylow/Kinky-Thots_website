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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https: http: data:; media-src 'self' http: https: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; font-src 'self' https:; connect-src 'self' https: http:;">
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <title>Kinky Thots - Sissy Long Legs</title>
    <link rel="stylesheet" href="/assets/sissylonglegs.css">
    <link rel="stylesheet" href="/assets/dropdown-nav.css">
    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
            margin: 0;
            padding: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Hero Section Overrides */
        .hero {
            margin-top: 0 !important;
            position: relative !important;
            min-height: 100vh !important;
            height: auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 200px 2rem 4rem 2rem !important;
            overflow: hidden !important;
        }
        
        .hero-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }
        
        .hero-slide.active {
            opacity: 1;
        }
        
        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.4);
        }
        
        .hero-content {
            position: relative !important;
            z-index: 1 !important;
            max-width: 900px !important;
            margin: 0 auto !important;
            text-align: center !important;
            color: #fff !important;
            padding-top: 40px !important;
        }
        
        .hero-content h1 {
            font-size: 3.5rem !important;
            margin-bottom: 1rem !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8) !important;
            padding-top: 20px !important;
        }
        
        .hero-content p {
            font-size: 1.1rem !important;
            line-height: 1.8 !important;
            margin-bottom: 1.5rem !important;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8) !important;
        }
        
        .hero-tagline {
            font-size: 1.3rem;
            color: #0bd0f3;
            font-weight: 500;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        }
        
        /* About Sissy Section */
        .about-sissy {
        
        .about-sissy .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            padding: 80px 0;
            color: #f5f5f5;
        }
        
        .about-sissy .section-header h2 {
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .about-content {
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.8;
            font-size: 1.1rem;
            padding: 0 2rem;
        }
        }
        
        .about-content p {
            margin-bottom: 1.5rem;
            color: #e0e0e0;
        }
        
        .about-content h3 {
            color: #f805a7;
            font-size: 1.8rem;
            margin: 2rem 0 1rem 0;
        }
        
        .cta-box {
            background: rgba(248, 5, 167, 0.1);
            border: 2px solid #f805a7;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 3rem;
            text-align: center;
        }
        
        .cta-box h3 {
            color: #0bd0f3;
            margin-bottom: 1rem;
        }
        
        .cta-box p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .about-content {
                padding: 0 1rem !important;
            }
            
            .about-content p {
                font-size: 1rem !important;
            }
            
            .about-sissy .section-header h2 {
                font-size: 2rem !important;
            }
            
            .hero {
                padding: 250px 1rem 3rem 1rem !important;
                align-items: flex-start !important;
            }
            
            .hero-content {
                padding-top: 80px !important;
            }
            
            .hero-content h1 {
                font-size: 2.2rem !important;
                padding-top: 30px !important;
            }
            
            .hero-content p {
                font-size: 1rem !important;
            }
            
            .hero-tagline {
                font-size: 1rem !important;
                letter-spacing: 1px !important;
            }
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
                    <li><a href="/gallery.html">Photo Gallery</a></li>
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

<section class="subscription-tiers" id="subscription">
    <div class="container">
        <div class="section-header">
            <h2>Choose Your Access Level</h2>
            <p>Get exclusive access to Sissy's premium content</p>
        </div>
        
        <div class="tiers-grid">
            <!-- Free Tier -->
            <div class="tier-card free">
                <div class="tier-badge">FREE</div>
                <h3>Sneak Peek</h3>
                <div class="tier-price">
                    <span class="price">$0</span>
                    <span class="period">/month</span>
                </div>
                <ul class="tier-features">
                    <li>✓ Preview clips & teasers</li>
                    <li>✓ Photo samples</li>
                    <li>✓ Public posts</li>
                    <li>✗ Full-length videos</li>
                    <li>✗ Exclusive content</li>
                    <li>✗ Direct messaging</li>
                </ul>
                <a href="https://sharesome.com/KinkyThots" target="_blank" class="tier-button free-button">View Free Content</a>
            </div>

            <!-- Premium Tier -->
            <div class="tier-card premium featured">
                <div class="tier-badge popular">MOST POPULAR</div>
                <h3>Premium Access</h3>
                <div class="tier-price">
                    <span class="price">$9.99</span>
                    <span class="period">/month</span>
                </div>
                <ul class="tier-features">
                    <li>✓ All free content</li>
                    <li>✓ <strong>Full-length videos</strong></li>
                    <li>✓ Exclusive photo sets</li>
                    <li>✓ Weekly new uploads</li>
                    <li>✓ Behind-the-scenes content</li>
                    <li>✓ Direct messaging</li>
                </ul>
                <a href="https://onlyfans.com/kinkythots" target="_blank" class="tier-button premium-button">Subscribe Now</a>
                <div class="tier-highlight">🔥 Access to 100+ full videos instantly!</div>
            </div>

            <!-- VIP Tier -->
            <div class="tier-card vip">
                <div class="tier-badge">VIP</div>
                <h3>VIP Experience</h3>
                <div class="tier-price">
                    <span class="price">$24.99</span>
                    <span class="period">/month</span>
                </div>
                <ul class="tier-features">
                    <li>✓ All premium content</li>
                    <li>✓ <strong>Custom video requests</strong></li>
                    <li>✓ Priority messaging</li>
                    <li>✓ Exclusive VIP-only videos</li>
                    <li>✓ Video calls available</li>
                    <li>✓ Your name in credits</li>
                </ul>
                <a href="https://onlyfans.com/kinkythots" target="_blank" class="tier-button vip-button">Go VIP</a>
                <div class="tier-highlight">👑 Get personalized content made just for you!</div>
            </div>
        </div>

        <div class="subscription-benefits">
            <h3>Why Subscribe?</h3>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">🎬</div>
                    <h4>Full-Length Videos</h4>
                    <p>Watch complete, uncut videos. No more teasers - get the whole experience.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">📅</div>
                    <h4>Regular Updates</h4>
                    <p>New content uploaded weekly. Never run out of fresh material.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">💬</div>
                    <h4>Direct Access</h4>
                    <p>Message Sissy directly. Get responses and build a connection.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🎁</div>
                    <h4>Exclusive Content</h4>
                    <p>Subscriber-only videos you won't find anywhere else.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.subscription-tiers {
    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
    padding: 80px 0;
}

.subscription-tiers .section-header h2 {
    background: linear-gradient(135deg, #f805a7, #0bd0f3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 2.5rem;
    margin-bottom: 1rem;
    text-align: center;
}

.subscription-tiers .section-header p {
    text-align: center;
    color: #e0e0e0;
    font-size: 1.2rem;
    margin-bottom: 3rem;
}

.tiers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 4rem;
}

.tier-card {
    background: #1a1a1a;
    border-radius: 16px;
    padding: 2rem;
    position: relative;
    border: 2px solid #333;
    transition: transform 0.3s, box-shadow 0.3s;
}

.tier-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(248, 5, 167, 0.3);
}

.tier-card.featured {
    border: 2px solid #f805a7;
    box-shadow: 0 0 30px rgba(248, 5, 167, 0.2);
    transform: scale(1.05);
}

.tier-badge {
    position: absolute;
    top: -15px;
    right: 20px;
    background: #333;
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
}

.tier-badge.popular {
    background: linear-gradient(135deg, #f805a7, #0bd0f3);
}

.tier-card h3 {
    color: #fff;
    font-size: 1.8rem;
    margin-bottom: 1rem;
    margin-top: 1rem;
}

.tier-price {
    margin-bottom: 2rem;
}

.tier-price .price {
    font-size: 3rem;
    font-weight: bold;
    color: #f805a7;
}

.tier-price .period {
    color: #999;
    font-size: 1rem;
}

.tier-features {
    list-style: none;
    margin-bottom: 2rem;
}

.tier-features li {
    padding: 0.75rem 0;
    color: #e0e0e0;
    border-bottom: 1px solid #333;
}

.tier-features li strong {
    color: #f805a7;
}

.tier-button {
    display: block;
    width: 100%;
    padding: 1rem;
    text-align: center;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.1rem;
    transition: all 0.3s;
}

.free-button {
    background: #333;
    color: #fff;
}

.free-button:hover {
    background: #444;
}

.premium-button {
    background: linear-gradient(135deg, #f805a7, #0bd0f3);
    color: #fff;
}

.premium-button:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 20px rgba(248, 5, 167, 0.5);
}

.vip-button {
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    color: #000;
}

.vip-button:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.5);
}

.tier-highlight {
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(248, 5, 167, 0.1);
    border-radius: 8px;
    text-align: center;
    color: #f805a7;
    font-size: 0.9rem;
}

.subscription-benefits {
    margin-top: 4rem;
}

.subscription-benefits h3 {
    text-align: center;
    color: #0bd0f3;
    font-size: 2rem;
    margin-bottom: 2rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.benefit-item {
    text-align: center;
    padding: 2rem;
    background: #1a1a1a;
    border-radius: 12px;
    border: 1px solid #333;
}

.benefit-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.benefit-item h4 {
    color: #f805a7;
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
}

.benefit-item p {
    color: #e0e0e0;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .tiers-grid {
        grid-template-columns: 1fr;
    }
    
    .tier-card.featured {
        transform: scale(1);
    }
    
    .subscription-tiers .section-header h2 {
        font-size: 2rem;
    }
}
</style>

<!-- About Sissy Section -->
<section class="about-sissy" id="about-sissy">
    <div class="container">
        <div class="section-header">
            <h2>From Good Girl to Cock Hungry Slut</h2>
        </div>
        <div class="about-content">
            <p>Growing up in a household where sex was shameful and women were meant to be submissive housewives, Sissy was the perfect daughter—until she wasn't. At 15, she gave her first blowjob in the back of a car and discovered her true calling. While her family preached purity, she was sneaking out to practice what she was born to do.</p>
            
            <p>Seventeen years later, she's still that bratty girl who loves breaking the rules, but now she's a 32-year-old PAWG with an ass that won't quit and a mouth that was made for worship. She gives head like it's her religion—sloppy, enthusiastic, and desperate to please. But here's the twist: she's still scared to swallow. She'll suck you dry but flinch when you want to finish in her mouth. It's that perfect mix of slut and innocence that makes her so fucking addictive.</p>
            
            <h3>What Makes Her Special</h3>
            <p>Sissy isn't your typical confident porn star. She's a work in progress—a girl who knows she's sexy but doesn't quite believe it yet. She'll arch that perfect ass for the camera but blush when you tell her how good she looks. She craves being dominated but gets bratty when you push her limits. She wants to be your slut but needs you to teach her how.</p>
            
            <p>Her specialty? Blowjobs that will ruin you for anyone else. She approaches cock like it's her favorite meal—eager, messy, and completely devoted. Watch her struggle to take it deeper, gag and keep going, look up at you with those innocent eyes while doing the dirtiest things. She's learning to love the taste of cum, even if she's not quite there yet.</p>
            
            <div class="cta-box">
                <h3>Watch Her Transformation</h3>
                <p>Subscribe to see this scared good girl become a confident whore. Watch her learn to embrace her slutty side, one cock at a time.</p>
                <a href="https://onlyfans.com/kinkythots" target="_blank" class="cta-button primary">Join Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section class="skills" id="skills">
    <div class="container">
        <div class="section-header">
            <h2>Recent Selfies</h2>
            <p><b>New Work Samples</b></p>
            <p>We will be uploading an image or 2 each time we've created new content. Please subscribe to see full photo/video sets created</p>
        </div>
        <div id="gallery-grid-sissy" class="gallery-grid mosaic-grid"></div>
    </div>
</section>
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
</div>

<!-- Portfolio Section -->
<a href="https://ibb.co/jvQCFdVb"><img src="https://i.ibb.co/mC2n7mvb/banner-throatgoat-1200-300.gif" alt="banner-throatgoat-1200-300" border="0"></a>

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
    <script src="/assets/scrolling.js"></script>
</footer>

<script>
    // Hero slideshow
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const totalSlides = slides.length;
    
    function nextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % totalSlides;
        slides[currentSlide].classList.add('active');
    }
    
    // Change image every 4 seconds
    if (totalSlides > 1) {
        setInterval(nextSlide, 4000);
    }
</script>
<script>
// Force gallery reload and log errors
window.addEventListener("load", function() {
    console.log("Page loaded, gallery should initialize...");
    setTimeout(function() {
        const grid = document.getElementById("gallery-grid-sissy");
        if (grid) {
            console.log("Gallery grid found");
            console.log("Grid content:", grid.innerHTML.substring(0, 200));
        } else {
            console.error("Gallery grid NOT found!");
        }
    }, 3000);
});
</script>
</body>
</html>

<script>
