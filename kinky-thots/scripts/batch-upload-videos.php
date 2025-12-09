#!/usr/bin/env php
<?php
/**
 * Batch Video Upload Script for Sonic S3 CDN
 * Uploads all videos from /media/porn/kinky-thots-shorts/ to CDN
 * and updates the video manifest
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$videoDir = '/media/porn/kinky-thots-shorts';
$manifestFile = '/var/www/kinky-thots/data/video-manifest.json';
$configPath = '/var/www/kinky-thots/config/sonic-s3-cdn.json';

// Load S3 config
$config = json_decode(file_get_contents($configPath), true);
$endpoint = $config['s3']['endpoint'];
$bucket = $config['s3']['bucket'];
$accessKey = $config['s3']['access_key'];
$secretKey = $config['s3']['secret_key'];
$region = $config['s3']['region'];
$cdnBaseUrl = $config['cdn']['base_url'];

echo "=== Sonic S3 Batch Video Upload ===\n";
echo "Source: $videoDir\n";
echo "CDN: $cdnBaseUrl\n\n";

// Get video files
$files = glob("$videoDir/*.{mp4,MP4}", GLOB_BRACE);
$totalFiles = count($files);
$totalSize = 0;

foreach ($files as $f) {
    $totalSize += filesize($f);
}

echo "Found $totalFiles video files (" . formatSize($totalSize) . " total)\n\n";

// AWS Signature V4
function signRequest($method, $url, $headers, $payload, $accessKey, $secretKey, $region) {
    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'];
    $path = $parsedUrl['path'] ?? '/';
    $query = $parsedUrl['query'] ?? '';

    $timestamp = gmdate('Ymd\THis\Z');
    $datestamp = gmdate('Ymd');

    $headers['host'] = $host;
    $headers['x-amz-date'] = $timestamp;
    $headers['x-amz-content-sha256'] = hash('sha256', $payload);

    $canonicalHeaders = '';
    $signedHeaders = [];
    ksort($headers);
    foreach ($headers as $key => $value) {
        $canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
        $signedHeaders[] = strtolower($key);
    }
    $signedHeadersStr = implode(';', $signedHeaders);

    $canonicalRequest = implode("\n", [
        $method, $path, $query, $canonicalHeaders, $signedHeadersStr, hash('sha256', $payload)
    ]);

    $credentialScope = "$datestamp/$region/s3/aws4_request";
    $stringToSign = implode("\n", [
        'AWS4-HMAC-SHA256', $timestamp, $credentialScope, hash('sha256', $canonicalRequest)
    ]);

    $kDate = hash_hmac('sha256', $datestamp, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeadersStr, Signature=$signature";
    return $headers;
}

function uploadFile($localPath, $remoteName, $endpoint, $bucket, $accessKey, $secretKey, $region) {
    $content = file_get_contents($localPath);
    $contentType = 'video/mp4';
    $contentLength = strlen($content);

    $url = "$endpoint/$bucket/" . rawurlencode($remoteName);

    $headers = [
        'Content-Type' => $contentType,
        'Content-Length' => $contentLength
    ];

    $signedHeaders = signRequest('PUT', $url, $headers, $content, $accessKey, $secretKey, $region);

    $headerStr = [];
    foreach ($signedHeaders as $k => $v) {
        $headerStr[] = "$k: $v";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $content,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headerStr,
        CURLOPT_TIMEOUT => 3600,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => "CURL: $error"];
    }

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'response' => $response
    ];
}

function getVideoMetadata($path) {
    // Try ffprobe first
    $cmd = "ffprobe -v quiet -print_format json -show_streams " . escapeshellarg($path) . " 2>/dev/null";
    $output = shell_exec($cmd);

    if ($output) {
        $data = json_decode($output, true);
        if (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    return [
                        'width' => $stream['width'] ?? null,
                        'height' => $stream['height'] ?? null
                    ];
                }
            }
        }
    }

    return ['width' => null, 'height' => null];
}

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Process uploads
$uploaded = 0;
$failed = 0;
$skipped = 0;
$manifest = ['videos' => [], 'cdn_base_url' => $cdnBaseUrl];

foreach ($files as $index => $file) {
    $filename = basename($file);
    $fileSize = filesize($file);

    $num = $index + 1;
    echo "[$num/$totalFiles] $filename (" . formatSize($fileSize) . ")... ";

    // Upload file
    $result = uploadFile($file, $filename, $endpoint, $bucket, $accessKey, $secretKey, $region);

    if ($result['success']) {
        $uploaded++;
        echo "OK\n";

        // Get video metadata
        $meta = getVideoMetadata($file);

        $manifest['videos'][] = [
            'filename' => $filename,
            'width' => $meta['width'],
            'height' => $meta['height'],
            'size_bytes' => $fileSize,
            'on_cdn' => true
        ];
    } else {
        $failed++;
        echo "FAILED (HTTP {$result['http_code']})\n";

        // Still add to manifest but mark as not on CDN
        $meta = getVideoMetadata($file);
        $manifest['videos'][] = [
            'filename' => $filename,
            'width' => $meta['width'],
            'height' => $meta['height'],
            'size_bytes' => $fileSize,
            'on_cdn' => false,
            'error' => "Upload failed: HTTP {$result['http_code']}"
        ];
    }
}

// Update manifest
$manifest['generated'] = date('c');
$manifest['count'] = count($manifest['videos']);
$manifest['notes'] = 'Auto-generated by batch-upload-videos.php';

file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\n=== Upload Complete ===\n";
echo "Uploaded: $uploaded\n";
echo "Failed: $failed\n";
echo "Total: $totalFiles\n";
echo "Manifest updated: $manifestFile\n";
