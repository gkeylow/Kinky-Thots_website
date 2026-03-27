<?php
// IndieAuth user profile page: /u/{username}
// Serves HTML with IndieAuth discovery metadata so Owncast can find the auth endpoints.

$parts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$username = isset($parts[1]) ? preg_replace('/[^a-zA-Z0-9_]/', '', $parts[1]) : '';

if (!$username) {
    http_response_code(404);
    exit('Not found');
}

// Fetch public profile from backend API (use internal Docker hostname)
$ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
$raw = @file_get_contents("http://backend:3001/api/users/profile/{$username}", false, $ctx);
if (!$raw) {
    http_response_code(404);
    exit('User not found');
}
$user = json_decode($raw, true);
if (!$user || isset($user['error'])) {
    http_response_code(404);
    exit('User not found');
}

$siteUrl = 'https://kinky-thots.xxx';
$profileUrl = "{$siteUrl}/u/{$username}";
$authEndpoint = "{$siteUrl}/api/indieauth/authorize";
$tokenEndpoint = "{$siteUrl}/api/indieauth/token";
$metadataUrl = "{$siteUrl}/.well-known/indieauth-server";

// IndieAuth discovery: HTTP Link headers (spec says these take precedence over HTML links)
header("Link: <{$authEndpoint}>; rel=\"authorization_endpoint\"", false);
header("Link: <{$tokenEndpoint}>; rel=\"token_endpoint\"", false);
header("Link: <{$metadataUrl}>; rel=\"indieauth-metadata\"", false);
header('Content-Type: text/html; charset=UTF-8');

$displayName = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
$avatarUrl = !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url'], ENT_QUOTES, 'UTF-8') : '';
$bio = !empty($user['bio']) ? htmlspecialchars($user['bio'], ENT_QUOTES, 'UTF-8') : '';
$profileUrlEsc = htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8');
$authEndpointEsc = htmlspecialchars($authEndpoint, ENT_QUOTES, 'UTF-8');
$tokenEndpointEsc = htmlspecialchars($tokenEndpoint, ENT_QUOTES, 'UTF-8');
$metadataUrlEsc = htmlspecialchars($metadataUrl, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $displayName ?> — Kinky-Thots</title>

  <!-- IndieAuth discovery (HTTP Link headers above take precedence per spec) -->
  <link rel="indieauth-metadata" href="<?= $metadataUrlEsc ?>">
  <link rel="authorization_endpoint" href="<?= $authEndpointEsc ?>">
  <link rel="token_endpoint" href="<?= $tokenEndpointEsc ?>">

  <!-- Canonical profile URL -->
  <link rel="canonical" href="<?= $profileUrlEsc ?>">

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0f0f0f;
      color: #fff;
      font-family: system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .card {
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 20px;
      padding: 40px 32px;
      width: 100%;
      max-width: 420px;
      text-align: center;
    }
    .avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #f805a7;
      margin-bottom: 16px;
    }
    .avatar-placeholder {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: linear-gradient(135deg, #f805a7, #0bd0f3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0 auto 16px;
    }
    h1 {
      font-size: 1.6rem;
      background: linear-gradient(135deg, #f805a7, #0bd0f3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 6px;
    }
    .handle {
      color: #666;
      font-size: .9rem;
      margin-bottom: 16px;
    }
    .bio {
      color: #aaa;
      font-size: .95rem;
      line-height: 1.5;
      margin-bottom: 24px;
    }
    .site-link {
      display: inline-block;
      padding: 10px 24px;
      background: linear-gradient(135deg, #f805a7, #0bd0f3);
      border-radius: 999px;
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      font-size: .9rem;
    }
    .site-link:hover { opacity: .85; }
    .footer {
      margin-top: 24px;
      font-size: .75rem;
      color: #444;
    }
  </style>
</head>
<body>
<div class="card">
  <?php if ($avatarUrl): ?>
    <img class="avatar" src="<?= $avatarUrl ?>" alt="<?= $displayName ?> avatar">
  <?php else: ?>
    <div class="avatar-placeholder"><?= mb_strtoupper(mb_substr($displayName, 0, 1)) ?></div>
  <?php endif; ?>

  <h1><?= $displayName ?></h1>
  <p class="handle">kinky-thots.xxx/u/<?= $displayName ?></p>

  <?php if ($bio): ?>
    <p class="bio"><?= $bio ?></p>
  <?php endif; ?>

  <a class="site-link" href="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>">Visit Kinky-Thots</a>

  <p class="footer">IndieAuth identity: <?= $profileUrlEsc ?></p>
</div>
</body>
</html>
