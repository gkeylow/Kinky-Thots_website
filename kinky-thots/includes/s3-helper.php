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
    $baseUrl = getCdnBaseUrl($type);
    $encodedFilename = rawurlencode($filename);
    return $baseUrl . '/' . $encodedFilename;
}
