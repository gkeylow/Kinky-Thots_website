<?php
/**
 * S3 Presigned URL Generator for Sonic CDN
 * Generates time-limited URLs for video access
 */

function getS3Config() {
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../config/sonic-s3-cdn.json';
        $config = json_decode(file_get_contents($configFile), true);
    }
    return $config;
}

/**
 * Generate a presigned URL for an S3 object
 *
 * @param string $key The object key (filename)
 * @param int $expiresIn Expiration time in seconds (default 1 hour)
 * @param string $type Bucket type: 'videos' or 'images'
 * @return string The presigned URL
 */
function generatePresignedUrl($key, $expiresIn = 3600, $type = 'videos') {
    $config = getS3Config();

    // Support both new nested structure and legacy flat structure
    $s3Config = $config['s3'][$type] ?? $config['s3'];

    $accessKey = $s3Config['access_key'];
    $secretKey = $s3Config['secret_key'];
    $bucket = $s3Config['bucket'];
    $region = $s3Config['region'];
    $endpoint = $s3Config['endpoint'];

    // Parse endpoint to get host
    $parsedEndpoint = parse_url($endpoint);
    $host = $parsedEndpoint['host'];

    // AWS Signature V4
    $service = 's3';
    $algorithm = 'AWS4-HMAC-SHA256';
    $now = time();
    $datestamp = gmdate('Ymd', $now);
    $amzDate = gmdate('Ymd\THis\Z', $now);
    $expiration = $expiresIn;

    $credentialScope = "$datestamp/$region/$service/aws4_request";
    $credential = urlencode("$accessKey/$credentialScope");

    // Canonical URI (path-style: /bucket/key)
    $canonicalUri = '/' . $bucket . '/' . rawurlencode($key);

    // Query string parameters
    $queryParams = [
        'X-Amz-Algorithm' => $algorithm,
        'X-Amz-Credential' => "$accessKey/$credentialScope",
        'X-Amz-Date' => $amzDate,
        'X-Amz-Expires' => $expiration,
        'X-Amz-SignedHeaders' => 'host'
    ];
    ksort($queryParams);

    $canonicalQueryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

    // Canonical headers
    $canonicalHeaders = "host:$host\n";
    $signedHeaders = 'host';

    // Canonical request
    $payloadHash = 'UNSIGNED-PAYLOAD';
    $canonicalRequest = "GET\n$canonicalUri\n$canonicalQueryString\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

    // String to sign
    $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

    // Signing key
    $kDate = hash_hmac('sha256', $datestamp, "AWS4$secretKey", true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

    // Signature
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    // Final URL
    $url = "$endpoint$canonicalUri?$canonicalQueryString&X-Amz-Signature=$signature";

    return $url;
}

/**
 * Get CDN base URL (for fallback/reference)
 * @param string $type Bucket type: 'videos' or 'images'
 */
function getCdnBaseUrl($type = 'videos') {
    $config = getS3Config();

    // New structure: cdn.videos_url or cdn.images_url
    $urlKey = $type . '_url';
    if (isset($config['cdn'][$urlKey])) {
        return $config['cdn'][$urlKey];
    }

    // Fallback: get from s3 config cdn_hostname
    if (isset($config['s3'][$type]['cdn_hostname'])) {
        return 'https://' . $config['s3'][$type]['cdn_hostname'];
    }

    // Legacy fallback
    return $config['cdn']['base_url'] ?? 'https://6318.s3.nvme.de01.sonic.r-cdn.com';
}

/**
 * Generate a CDN URL for video content
 * Push zones serve content directly without tokens
 *
 * @param string $filename The video filename
 * @param string $type Bucket type: 'videos' or 'images'
 * @return string The CDN URL
 */
function getCdnUrl($filename, $type = 'videos') {
    $config = getS3Config();
    $secureToken = $config['security']['token'] ?? null;

    // Only apply secure token to videos (not images)
    if ($secureToken && $type === 'videos') {
        return generateSecureTokenUrl($filename, $type, $secureToken);
    }

    // Unsigned URL for images or when no token configured
    $baseUrl = getCdnBaseUrl($type);
    // Encode path segments individually, not the slashes
    $parts = explode('/', $filename);
    $encodedPath = implode('/', array_map('rawurlencode', $parts));
    return $baseUrl . '/' . $encodedPath;
}

/**
 * Generate a Pushr secure token URL
 * Format: https://[cdn-host]/[token]/[expiration]/[path]
 * Token = base64url(MD5(secret + exp + path + file + ip))
 */
function generateSecureTokenUrl($filename, $type = 'videos', $secret = null) {
    if (!$secret) {
        $config = getS3Config();
        $secret = $config['security']['token'] ?? null;
        if (!$secret) {
            // No token configured, return unsigned URL
            return getCdnBaseUrl($type) . '/' . $filename;
        }
    }

    $baseUrl = getCdnBaseUrl($type);

    // Parse path and file
    $pathParts = explode('/', $filename);
    $file = array_pop($pathParts);
    $path = '/' . implode('/', $pathParts) . '/';

    // Get visitor IP (use placeholder if CLI)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Expiration: 2 hours from now
    $exp = time() + 7200;

    // Generate token: MD5(secret + exp + path + file + ip)
    $md5 = base64_encode(md5($secret . $exp . $path . $file . $ip, true));
    $md5 = strtr($md5, '+/', '-_');
    $md5 = str_replace('=', '', $md5);

    // Encode filename for URL
    $encodedFile = rawurlencode($file);
    $encodedPath = implode('/', array_map('rawurlencode', $pathParts));

    // Build URL: host/token/exp/path/file
    return $baseUrl . '/' . $md5 . '/' . $exp . '/' . $encodedPath . '/' . $encodedFile;
}
