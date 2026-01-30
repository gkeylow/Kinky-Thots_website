<?php
/**
 * Landing Page Header (No Navigation)
 * For kinky-thots.com SFW landing page
 */

$pageTitle = $pageTitle ?? 'Kinky Thots - Premium Content Creators';
$pageDescription = $pageDescription ?? 'Exclusive premium content from independent creators. Join our community for exclusive photos, videos, and live streams.';
$pageCss = $pageCss ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="author" content="kinky-thots">
    <meta name="copyright" content="kinky-thots">

    <!-- Open Graph / Social -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="https://i.ibb.co/vCYpJSng/icon-kt-250.png">
    <meta property="og:url" content="https://kinky-thots.com">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="https://i.ibb.co/gZY9MTG4/icon-kt-favicon.png">
    <link rel="canonical" href="https://kinky-thots.com">
    <link rel="stylesheet" href="https://kinky-thots.xxx/assets/dist/css/main.css?v=<?php echo date('YmdHi'); ?>">
<?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="https://kinky-thots.xxx<?php echo htmlspecialchars($css); ?>?v=<?php echo date('YmdHi'); ?>">
<?php endforeach; ?>
</head>
<body>
<header class="landing-header">
    <div class="logo-container">
        <a href="/" class="logo-link">
            <img src="https://i.ibb.co/vCYpJSng/icon-kt-250.png" alt="Kinky Thots" width="80">
            <span class="logo-text">Kinky-Thots</span>
        </a>
    </div>
</header>
