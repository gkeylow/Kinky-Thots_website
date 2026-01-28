<?php
// Server-side password protection
session_start();

// Get password from environment variable (works with Docker and native)
$adminPassword = getenv('GALLERY_ADMIN_PASSWORD');
if (!$adminPassword) {
    die('FATAL: GALLERY_ADMIN_PASSWORD environment variable is required');
}
$isAuthenticated = isset($_SESSION['gallery_admin_auth']) && $_SESSION['gallery_admin_auth'] === true;

// Handle admin bypass via JWT verification
// Security note: Token passed via URL is visible in logs/history - only use for admin bypass
if (!$isAuthenticated && isset($_GET['admin_bypass'])) {
    $token = $_GET['admin_bypass'];
    $jwtSecret = getenv('JWT_SECRET');
    if ($jwtSecret && $token) {
        // Simple JWT decode (header.payload.signature)
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            // Must have userId AND isAdmin flag set to true
            if ($payload && isset($payload['userId']) && !empty($payload['isAdmin'])) {
                // Verify signature
                $header = $parts[0];
                $signature = hash_hmac('sha256', "$header.$parts[1]", $jwtSecret, true);
                $validSig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
                if (hash_equals($validSig, $parts[2])) {
                    // JWT is valid AND user is admin
                    $_SESSION['gallery_admin_auth'] = true;
                    $_SESSION['gallery_admin_user'] = $payload['username'] ?? 'Admin';
                    $isAuthenticated = true;
                    // Redirect to remove token from URL
                    header('Location: /gallery.php');
                    exit;
                }
            }
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['gallery_admin_auth'] = true;
        $isAuthenticated = true;
    } else {
        $loginError = 'Incorrect password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['gallery_admin_auth']);
    header('Location: /index.php');
    exit;
}

// Show login form if not authenticated
if (!$isAuthenticated):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow"/>
    <link rel="icon" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png" type="image/x-icon">
    <title>Admin Login - Kinky Thots</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #181818;
            color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #222;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .login-container h1 {
            color: #f805a7;
            margin-bottom: 10px;
        }
        .login-container p {
            color: #888;
            margin-bottom: 30px;
        }
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #333;
            border-radius: 8px;
            background: #181818;
            color: #fff;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .login-container input[type="password"]:focus {
            outline: none;
            border-color: #f805a7;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f805a7, #0bd0f3);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .login-container button:hover {
            transform: scale(1.02);
        }
        .error {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .back-link {
            margin-top: 20px;
            display: block;
            color: #0bd0f3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Access</h1>
        <p>Enter password to access the gallery</p>
        <?php if (isset($loginError)): ?>
            <p class="error"><?php echo htmlspecialchars($loginError); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" required autofocus>
            <button type="submit">Login</button>
        </form>
        <a href="/index.php" class="back-link">Back to Home</a>
    </div>
    <script>
    // Auto-bypass for admin users
    (async function() {
        const token = localStorage.getItem('kt_auth_token');
        if (!token) return;

        try {
            const res = await fetch('/api/auth/me', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            if (res.ok) {
                const user = await res.json();
                if (user.is_admin) {
                    // Admin user - bypass password
                    window.location.href = '/gallery.php?admin_bypass=' + encodeURIComponent(token);
                }
            }
        } catch (e) {
            console.log('Admin check failed:', e);
        }
    })();
    </script>
</body>
</html>
<?php
exit;
endif;

// Page configuration for header include
$pageTitle = 'Kinky Thots - Amateur XXX Gallery';
$pageRobots = 'noindex,nofollow';
$pageCss = ['/assets/dist/css/media-gallery.css?v=20260114'];

include __DIR__ . '/includes/header.php';
?>

<div class="container" id="mainContainer">
    <div class="header">
        <h1>Photo Gallery</h1>
        <p>Upload Your Images</p>
    </div>

    <!-- Upload section -->
    <div id="upload-section">
        <form id="upload-form">
            <div class="upload-area">
                <span class="upload-text">Drag & drop or click to select an image</span>
                <input type="file" id="image-input" name="image" accept="image/*,video/*" style="display:none;">
                <img id="image-preview" style="display:none; max-width:120px; max-height:120px; margin-top:10px; border-radius:8px;" />
            </div>
            <button type="submit" class="upload-btn">Upload Image</button>
            <div class="progress-bar" style="display:none;">
                <div class="progress-fill"></div>
            </div>
            <div id="upload-status" class="status" style="display:none;"></div>
        </form>
    </div>

    <!-- Gallery grid -->
    <div id="gallery-grid" class="gallery-grid mosaic-grid"></div>

    <!-- Lightbox overlay -->
    <div id="lightbox-overlay" class="lightbox-overlay" style="display:none;">
        <span class="lightbox-close" id="lightbox-close">&times;</span>
        <div id="lightbox-content">
            <img id="lightbox-img" alt="Full Image" style="display:none;" onerror="console.error('Lightbox image failed to load:', this.src);" />
            <video id="lightbox-video" controls style="display:none; max-width:90vw; max-height:80vh;"></video>
        </div>
        <div class="lightbox-nav">
            <button id="lightbox-prev" class="lightbox-nav-btn">&#8592;</button>
            <button id="lightbox-next" class="lightbox-nav-btn">&#8594;</button>
        </div>
    </div>
</div>

<!-- Built JS (Vite) -->
<script type="module" src="/assets/dist/js/gallery.js?v=20260114"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://static.elfsight.com/platform/platform.js" async></script>
<div class="elfsight-app-ea2f58c6-6128-4e92-b2d9-c0b5c09769c3" data-elfsight-app-lazy></div>
</body>
</html>
