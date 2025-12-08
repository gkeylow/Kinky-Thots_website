<?php
require_once 'backend/pushr-secure-tokens.php';

$tokenGen = new PushrSecureTokens();

// Generate test URLs
$testImage = '/uploads/1765150122666_SissyLongLegs_BJ_1.gif';
$testVideo = '/porn/Haley/Haley_Blowjob_1.mp4';

$secureImageUrl = $tokenGen->secureImage($testImage, 3600);
$directImageUrl = 'https://c5988z6292.r-cdn.com' . $testImage;

$secureVideoUrl = $tokenGen->secureVideo($testVideo, 3600);
$directVideoUrl = 'https://kinky-thots.com' . $testVideo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDN Secure Token Browser Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .test-section h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .url-display {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        
        .url-label {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .test-result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        img, video {
            max-width: 100%;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .token-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        
        .token-info strong {
            color: #856404;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
            margin: 10px 10px 10px 0;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-success {
            background: #28a745;
        }
        
        .status-error {
            background: #dc3545;
        }
        
        .status-pending {
            background: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 CDN Secure Token Test</h1>
        <p class="subtitle">Browser-based testing for PushrCDN secure tokens</p>
        
        <div class="test-section">
            <h2>Test Configuration</h2>
            <div class="token-info">
                <strong>Token Expiration:</strong> 1 hour (3600 seconds)<br>
                <strong>Generated At:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>Expires At:</strong> <?php echo date('Y-m-d H:i:s', time() + 3600); ?><br>
                <strong>Client IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Test 1: Image with Secure Token</h2>
            
            <div class="url-label">Secure URL (with token):</div>
            <div class="url-display"><?php echo htmlspecialchars($secureImageUrl); ?></div>
            
            <div class="url-label">Direct URL (no token):</div>
            <div class="url-display"><?php echo htmlspecialchars($directImageUrl); ?></div>
            
            <div id="imageTest" class="test-result info">
                <span class="status-indicator status-pending"></span>
                Loading image with secure token...
            </div>
            
            <img id="secureImage" 
                 src="<?php echo htmlspecialchars($secureImageUrl); ?>" 
                 alt="Secure Image Test"
                 onload="imageLoaded(true)"
                 onerror="imageLoaded(false)"
                 style="display: none;">
        </div>
        
        <div class="test-section">
            <h2>Test 2: Video with Secure Token</h2>
            
            <div class="url-label">Secure URL (with token):</div>
            <div class="url-display"><?php echo htmlspecialchars($secureVideoUrl); ?></div>
            
            <div id="videoTest" class="test-result info">
                <span class="status-indicator status-pending"></span>
                Loading video with secure token...
            </div>
            
            <video id="secureVideo" 
                   controls 
                   preload="metadata"
                   onloadedmetadata="videoLoaded(true)"
                   onerror="videoLoaded(false)"
                   style="display: none; max-height: 400px;">
                <source src="<?php echo htmlspecialchars($secureVideoUrl); ?>" type="video/mp4">
            </video>
        </div>
        
        <div class="test-section">
            <h2>Test 3: Network Analysis</h2>
            <div class="info">
                <strong>📊 Check Browser DevTools:</strong><br>
                1. Press F12 to open DevTools<br>
                2. Go to Network tab<br>
                3. Reload this page<br>
                4. Look for the image and video requests<br>
                5. Check response headers for CDN information
            </div>
            <button onclick="location.reload()">Reload Page</button>
            <button onclick="testExpiredToken()">Test Expired Token</button>
        </div>
        
        <div class="test-section">
            <h2>Test Results Summary</h2>
            <div id="summary" class="test-result info">
                <span class="status-indicator status-pending"></span>
                Waiting for tests to complete...
            </div>
        </div>
    </div>
    
    <script>
        let imageSuccess = false;
        let videoSuccess = false;
        
        function imageLoaded(success) {
            imageSuccess = success;
            const testDiv = document.getElementById('imageTest');
            const img = document.getElementById('secureImage');
            
            if (success) {
                testDiv.className = 'test-result success';
                testDiv.innerHTML = '<span class="status-indicator status-success"></span>' +
                    '✅ Image loaded successfully with secure token!<br>' +
                    'The token is valid and the CDN is serving the content.';
                img.style.display = 'block';
            } else {
                testDiv.className = 'test-result error';
                testDiv.innerHTML = '<span class="status-indicator status-error"></span>' +
                    '❌ Image failed to load.<br>' +
                    'Possible reasons:<br>' +
                    '• Secure tokens not enabled in PushrCDN dashboard<br>' +
                    '• File does not exist<br>' +
                    '• Token validation failed<br>' +
                    '• Network error';
            }
            
            updateSummary();
        }
        
        function videoLoaded(success) {
            videoSuccess = success;
            const testDiv = document.getElementById('videoTest');
            const video = document.getElementById('secureVideo');
            
            if (success) {
                testDiv.className = 'test-result success';
                testDiv.innerHTML = '<span class="status-indicator status-success"></span>' +
                    '✅ Video loaded successfully with secure token!<br>' +
                    'The token is valid and the video is ready to play.';
                video.style.display = 'block';
            } else {
                testDiv.className = 'test-result error';
                testDiv.innerHTML = '<span class="status-indicator status-error"></span>' +
                    '❌ Video failed to load.<br>' +
                    'Possible reasons:<br>' +
                    '• Secure tokens not enabled in PushrCDN dashboard<br>' +
                    '• File does not exist<br>' +
                    '• Token validation failed<br>' +
                    '• Network error';
            }
            
            updateSummary();
        }
        
        function updateSummary() {
            const summaryDiv = document.getElementById('summary');
            
            if (imageSuccess && videoSuccess) {
                summaryDiv.className = 'test-result success';
                summaryDiv.innerHTML = '<span class="status-indicator status-success"></span>' +
                    '<strong>✅ All Tests Passed!</strong><br>' +
                    'Secure tokens are working correctly. Your CDN content is protected.';
            } else if (!imageSuccess && !videoSuccess) {
                summaryDiv.className = 'test-result error';
                summaryDiv.innerHTML = '<span class="status-indicator status-error"></span>' +
                    '<strong>❌ Tests Failed</strong><br>' +
                    'Neither image nor video loaded. Check:<br>' +
                    '1. Secure tokens enabled in PushrCDN dashboard<br>' +
                    '2. Files exist at specified paths<br>' +
                    '3. Secret token matches dashboard configuration';
            } else {
                summaryDiv.className = 'test-result error';
                summaryDiv.innerHTML = '<span class="status-indicator status-error"></span>' +
                    '<strong>⚠️ Partial Success</strong><br>' +
                    'Image: ' + (imageSuccess ? '✅' : '❌') + '<br>' +
                    'Video: ' + (videoSuccess ? '✅' : '❌');
            }
        }
        
        function testExpiredToken() {
            alert('To test expired tokens:\n\n' +
                  '1. Wait for the token to expire (1 hour)\n' +
                  '2. Or modify the expiration time in the PHP code to 10 seconds\n' +
                  '3. Reload the page after expiration\n' +
                  '4. You should see 403 Forbidden errors');
        }
        
        // Set timeout for tests
        setTimeout(function() {
            if (!imageSuccess && !videoSuccess) {
                updateSummary();
            }
        }, 10000);
    </script>
</body>
</html>
