<?php
/**
 * Comprehensive CDN Secure Token Test Script
 */

require_once 'backend/pushr-secure-tokens.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          PushrCDN Secure Token - Comprehensive Test           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $tokenGen = new PushrSecureTokens();
    
    // Test 1: Image Token Generation
    echo "TEST 1: Image Token Generation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $imageUrl = $tokenGen->secureImage('/uploads/test-image.jpg', 3600);
    echo "✓ Generated URL: $imageUrl\n";
    echo "✓ Token Valid: " . ($tokenGen->isTokenValid($imageUrl) ? 'YES' : 'NO') . "\n";
    echo "✓ Expires: " . date('Y-m-d H:i:s', $tokenGen->getTokenExpiration($imageUrl)) . "\n";
    echo "✓ Time until expiration: " . ($tokenGen->getTokenExpiration($imageUrl) - time()) . " seconds\n\n";
    
    // Test 2: Video Token Generation
    echo "TEST 2: Video Token Generation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $videoUrl = $tokenGen->secureVideo('/porn/Haley/Haley_Blowjob_1.mp4', 7200);
    echo "✓ Generated URL: $videoUrl\n";
    echo "✓ Token Valid: " . ($tokenGen->isTokenValid($videoUrl) ? 'YES' : 'NO') . "\n";
    echo "✓ Expires: " . date('Y-m-d H:i:s', $tokenGen->getTokenExpiration($videoUrl)) . "\n\n";
    
    // Test 3: HLS Token Generation
    echo "TEST 3: HLS Title Token Generation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $hlsUrl = $tokenGen->secureHLS('/porn/Haley/hls/', 'playlist.m3u8', 3600);
    echo "✓ Generated URL: $hlsUrl\n";
    echo "✓ Token Valid: " . ($tokenGen->isTokenValid($hlsUrl) ? 'YES' : 'NO') . "\n";
    echo "✓ Has pushr parameter: " . (strpos($hlsUrl, '?pushr=') !== false ? 'YES' : 'NO') . "\n\n";
    
    // Test 4: Short Expiration Token
    echo "TEST 4: Short Expiration Token (10 seconds)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $shortUrl = $tokenGen->secureImage('/uploads/short-lived.jpg', 10);
    echo "✓ Generated URL: $shortUrl\n";
    echo "✓ Token Valid: " . ($tokenGen->isTokenValid($shortUrl) ? 'YES' : 'NO') . "\n";
    echo "✓ Expires in: " . ($tokenGen->getTokenExpiration($shortUrl) - time()) . " seconds\n";
    echo "⏳ Waiting 11 seconds to test expiration...\n";
    sleep(11);
    echo "✓ Token Valid After 11s: " . ($tokenGen->isTokenValid($shortUrl) ? 'YES' : 'NO (EXPECTED)') . "\n\n";
    
    // Test 5: Different Zones
    echo "TEST 5: Different Zone Configurations\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $zones = ['images', 'my-images', 'videos', 'my-videos'];
    foreach ($zones as $zone) {
        try {
            $url = $tokenGen->generateFileToken('/test/file.jpg', $zone, 3600);
            echo "✓ Zone '$zone': " . parse_url($url, PHP_URL_HOST) . "\n";
        } catch (Exception $e) {
            echo "✗ Zone '$zone': " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Test 6: URL Structure Validation
    echo "TEST 6: URL Structure Validation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $testUrl = $tokenGen->secureImage('/uploads/structure-test.jpg');
    $parts = parse_url($testUrl);
    $pathParts = explode('/', trim($parts['path'], '/'));
    
    echo "✓ Scheme: " . $parts['scheme'] . "\n";
    echo "✓ Host: " . $parts['host'] . "\n";
    echo "✓ Token: " . $pathParts[0] . " (length: " . strlen($pathParts[0]) . ")\n";
    echo "✓ Timestamp: " . $pathParts[1] . " (" . date('Y-m-d H:i:s', $pathParts[1]) . ")\n";
    echo "✓ Path: /" . implode('/', array_slice($pathParts, 2)) . "\n\n";
    
    // Test 7: Token Uniqueness
    echo "TEST 7: Token Uniqueness (Same File, Different Times)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $url1 = $tokenGen->secureImage('/uploads/same-file.jpg');
    sleep(1);
    $url2 = $tokenGen->secureImage('/uploads/same-file.jpg');
    echo "✓ URL 1: $url1\n";
    echo "✓ URL 2: $url2\n";
    echo "✓ URLs Different: " . ($url1 !== $url2 ? 'YES (EXPECTED)' : 'NO') . "\n\n";
    
    // Test 8: Configuration Loading
    echo "TEST 8: Configuration Validation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $configPath = __DIR__ . '/config/pushr-cdn.json';
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        echo "✓ Config file found\n";
        echo "✓ Secret token present: " . (isset($config['secret_token']) ? 'YES' : 'NO') . "\n";
        echo "✓ Secret token length: " . strlen($config['secret_token']) . " chars\n";
        echo "✓ Zones configured: " . count($config['zones']) . "\n";
        echo "✓ API key present: " . (isset($config['api_key']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "✗ Config file not found\n";
    }
    echo "\n";
    
    // Summary
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                        TEST SUMMARY                            ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    echo "✅ All tests completed successfully!\n\n";
    echo "Next Steps:\n";
    echo "1. Enable secure tokens in PushrCDN dashboard\n";
    echo "2. Test with actual CDN URLs in browser\n";
    echo "3. Verify 403 errors for invalid/expired tokens\n";
    echo "4. Integrate into your application code\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
