<?php
/**
 * Shared Header Include
 *
 * Variables to set before including:
 * - $pageTitle (required) - Page title
 * - $pageCss (optional) - Array of additional CSS files to include
 * - $pageStyles (optional) - Inline styles for the page
 * - $pageRobots (optional) - Robots meta content (default: "index,nofollow")
 * - $bodyClass (optional) - Additional body classes
 */

$pageTitle = $pageTitle ?? 'Kinky Thots';
$pageCss = $pageCss ?? [];
$pageStyles = $pageStyles ?? '';
$pageRobots = $pageRobots ?? 'index,nofollow';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="<?php echo htmlspecialchars($pageRobots); ?>">
    <meta name="author" content="kinky-thots">
    <meta name="copyright" content="kinky-thots">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png">
    <link rel="stylesheet" href="/assets/dist/css/main.css?v=<?php echo date('YmdHi'); ?>">
<?php foreach ($pageCss as $css): ?>
<?php
    // Handle relative CSS names - prepend path if needed
    if (strpos($css, '/') === false) {
        $css = '/assets/dist/css/' . $css;
    }
?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>?v=<?php echo date('YmdHi'); ?>">
<?php endforeach; ?>
<?php if ($pageStyles): ?>
    <style>
<?php echo $pageStyles; ?>
    </style>
<?php endif; ?>
</head>
<body<?php echo $bodyClass ? ' class="' . htmlspecialchars($bodyClass) . '"' : ''; ?>>
<nav id="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.php">Kinky-Thots<img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" width="50px"></a>
        </div>

        <ul class="nav-links">
            <li><a href="/index.php">Home</a></li>
            <li class="dropdown">
                <button class="dropdown-toggle">Models</button>
                <ul class="dropdown-menu">
                    <li><a href="/sissylonglegs.php">Sissy Long Legs</a></li>
                    <li><a href="/bustersherry.php">Buster Sherry</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <button class="dropdown-toggle">Content</button>
                <ul class="dropdown-menu">
                    <li><a href="/free-content.php">Free Teasers</a></li>
                    <li><a href="/plus-content.php">Plus Videos</a></li>
                    <li><a href="/premium-content.php">Full Access</a></li>
                    <li><a href="/live.php">Live Cam</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <button class="dropdown-toggle">Socials</button>
                <ul class="dropdown-menu">
                    <li><a href="https://onlyfans.com/kinkythots" target="_blank">OnlyFans</a></li>
                    <li><a href="https://sharesome.com/KinkyThots" target="_blank">Sharesome</a></li>
                    <li><a href="https://kinky-thots.bdsmlr.com" target="_blank">BDSMLR</a></li>
                    <li><a href="https://facebook.com/kinkythots" target="_blank">Facebook</a></li>
                    <li><a href="https://x.com/kinkythotsmodel" target="_blank">X (Twitter)</a></li>
                </ul>
            </li>
            <li class="dropdown" id="userDropdown">
                <button class="dropdown-toggle" id="userTrigger">Account</button>
                <ul class="dropdown-menu">
                    <li><a href="/profile.php">My Profile</a></li>
                    <li><a href="/members.php">Members</a></li>
                    <li><a href="/profile.php#security">Security</a></li>
                    <li><a href="/subscriptions.php">Subscription</a></li>
                    <li id="adminLink" style="display:none;"><a href="/admin.php" style="color:#f805a7;">Admin Dashboard</a></li>
                    <li><a href="#" id="logoutLink">Logout</a></li>
                </ul>
            </li>
            <li id="loginItem"><a href="/login.php" class="login-btn" id="authTrigger">Login</a></li>
        </ul>

        <button class="nav-toggle" aria-label="Toggle navigation menu">&#9776;</button>
    </div>
</nav>
<script type="module" src="/assets/dist/js/main.js"></script>
<script>
(function() {
    const token = localStorage.getItem('kt_auth_token');
    const user = JSON.parse(localStorage.getItem('kt_auth_user') || 'null');

    if (token && user) {
        document.body.classList.add('logged-in');
        document.getElementById('userTrigger').textContent = user.username;
        if (user.is_admin) {
            const adminLink = document.getElementById('adminLink');
            if (adminLink) adminLink.style.display = 'block';
        }
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('kt_auth_token');
            localStorage.removeItem('kt_auth_user');
            window.location.reload();
        });
    } else {
        document.body.classList.add('logged-out');
        if (token && !user) localStorage.removeItem('kt_auth_token');
        if (!token && user) localStorage.removeItem('kt_auth_user');
    }
})();
</script>
