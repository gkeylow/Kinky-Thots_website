<?php
/**
 * Sonic S3 CDN Upload Handler
 * 
 * This script handles file uploads to the Sonic S3 CDN using AWS S3-compatible API.
 * 
 * Usage:
 *   php sonic-s3-upload.php upload <local_file> [remote_path]
 *   php sonic-s3-upload.php delete <remote_path>
 *   php sonic-s3-upload.php list [prefix]
 *   php sonic-s3-upload.php test
 *   php sonic-s3-upload.php info <remote_path>
 */

// Load configuration
$configPath = __DIR__ . '/../config/sonic-s3-cdn.json';
if (!file_exists($configPath)) {
    die("Error: Configuration file not found at $configPath\n");
}

$config = json_decode(file_get_contents($configPath), true);
if (!$config) {
    die("Error: Invalid JSON in configuration file\n");
}

// S3 Configuration
define('S3_ENDPOINT', $config['s3']['endpoint']);
define('S3_ACCESS_KEY', $config['s3']['access_key']);
define('S3_SECRET_KEY', $config['s3']['secret_key']);
define('S3_BUCKET', $config['s3']['bucket']);
define('S3_REGION', $config['s3']['region']);
define('CDN_BASE_URL', $config['cdn']['base_url']);

/**
 * Generate AWS Signature Version 4
 */
class S3Signer {
    private $accessKey;
    private $secretKey;
    private $region;
    private $service = 's3';
    
    public function __construct($accessKey, $secretKey, $region) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;
    }
    
    public function signRequest($method, $url, $headers = [], $payload = '') {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '/';
        $query = $parsedUrl['query'] ?? '';
        
        $timestamp = gmdate('Ymd\THis\Z');
        $datestamp = gmdate('Ymd');
        
        // Add required headers
        $headers['host'] = $host;
        $headers['x-amz-date'] = $timestamp;
        $headers['x-amz-content-sha256'] = hash('sha256', $payload);
        
        // Create canonical request
        $canonicalHeaders = '';
        $signedHeaders = [];
        ksort($headers);
        foreach ($headers as $key => $value) {
            $canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
            $signedHeaders[] = strtolower($key);
        }
        $signedHeadersStr = implode(';', $signedHeaders);
        
        $canonicalRequest = implode("\n", [
            $method,
            $path,
            $query,
            $canonicalHeaders,
            $signedHeadersStr,
            hash('sha256', $payload)
        ]);
        
        // Create string to sign
        $credentialScope = "$datestamp/{$this->region}/{$this->service}/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $timestamp,
            $credentialScope,
            hash('sha256', $canonicalRequest)
        ]);
        
        // Calculate signature
        $kDate = hash_hmac('sha256', $datestamp, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        // Create authorization header
        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeadersStr, Signature=$signature";
        
        $headers['Authorization'] = $authorization;
        
        return $headers;
    }
}

/**
 * S3 Client for Sonic CDN
 */
class SonicS3Client {
    private $endpoint;
    private $bucket;
    private $signer;
    private $cdnBaseUrl;
    
    public function __construct() {
        $this->endpoint = S3_ENDPOINT;
        $this->bucket = S3_BUCKET;
        $this->cdnBaseUrl = CDN_BASE_URL;
        $this->signer = new S3Signer(S3_ACCESS_KEY, S3_SECRET_KEY, S3_REGION);
    }
    
    /**
     * Upload a file to S3
     */
    public function uploadFile($localPath, $remotePath = null) {
        if (!file_exists($localPath)) {
            return ['success' => false, 'error' => "File not found: $localPath"];
        }
        
        $filename = basename($localPath);
        $remotePath = $remotePath ?? $filename;
        $remotePath = ltrim($remotePath, '/');
        
        $content = file_get_contents($localPath);
        $contentType = $this->getMimeType($localPath);
        $contentLength = strlen($content);
        
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath";
        
        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => $contentLength
        ];
        
        $signedHeaders = $this->signer->signRequest('PUT', $url, $headers, $content);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => "CURL error: $error"];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'remote_path' => $remotePath,
                'cdn_url' => "{$this->cdnBaseUrl}/$remotePath",
                's3_url' => $url,
                'size' => $contentLength,
                'content_type' => $contentType
            ];
        }
        
        return [
            'success' => false,
            'error' => "HTTP $httpCode",
            'response' => $response
        ];
    }
    
    /**
     * Upload file using multipart upload (for large files)
     */
    public function uploadLargeFile($localPath, $remotePath = null, $partSize = 10485760) {
        if (!file_exists($localPath)) {
            return ['success' => false, 'error' => "File not found: $localPath"];
        }
        
        $fileSize = filesize($localPath);
        
        // Use regular upload for files under 100MB
        if ($fileSize < 104857600) {
            return $this->uploadFile($localPath, $remotePath);
        }
        
        $filename = basename($localPath);
        $remotePath = $remotePath ?? $filename;
        $remotePath = ltrim($remotePath, '/');
        $contentType = $this->getMimeType($localPath);
        
        echo "Starting multipart upload for large file ({$this->formatSize($fileSize)})...\n";
        
        // 1. Initiate multipart upload
        $uploadId = $this->initiateMultipartUpload($remotePath, $contentType);
        if (!$uploadId) {
            return ['success' => false, 'error' => 'Failed to initiate multipart upload'];
        }
        
        echo "Upload ID: $uploadId\n";
        
        // 2. Upload parts
        $parts = [];
        $partNumber = 1;
        $handle = fopen($localPath, 'rb');
        
        while (!feof($handle)) {
            $data = fread($handle, $partSize);
            if (strlen($data) === 0) break;
            
            echo "Uploading part $partNumber (" . $this->formatSize(strlen($data)) . ")...\n";
            
            $etag = $this->uploadPart($remotePath, $uploadId, $partNumber, $data);
            if (!$etag) {
                fclose($handle);
                $this->abortMultipartUpload($remotePath, $uploadId);
                return ['success' => false, 'error' => "Failed to upload part $partNumber"];
            }
            
            $parts[] = ['PartNumber' => $partNumber, 'ETag' => $etag];
            $partNumber++;
        }
        
        fclose($handle);
        
        // 3. Complete multipart upload
        echo "Completing multipart upload...\n";
        $result = $this->completeMultipartUpload($remotePath, $uploadId, $parts);
        
        if ($result) {
            return [
                'success' => true,
                'remote_path' => $remotePath,
                'cdn_url' => "{$this->cdnBaseUrl}/$remotePath",
                's3_url' => "{$this->endpoint}/{$this->bucket}/$remotePath",
                'size' => $fileSize,
                'content_type' => $contentType,
                'parts' => count($parts)
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to complete multipart upload'];
    }
    
    private function initiateMultipartUpload($remotePath, $contentType) {
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath?uploads";
        
        $headers = ['Content-Type' => $contentType];
        $signedHeaders = $this->signer->signRequest('POST', $url, $headers, '');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if (preg_match('/<UploadId>([^<]+)<\/UploadId>/', $response, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function uploadPart($remotePath, $uploadId, $partNumber, $data) {
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath?partNumber=$partNumber&uploadId=$uploadId";
        
        $headers = ['Content-Length' => strlen($data)];
        $signedHeaders = $this->signer->signRequest('PUT', $url, $headers, $data);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_TIMEOUT => 600
        ]);
        
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        curl_close($ch);
        
        if (preg_match('/ETag:\s*"?([^"\r\n]+)"?/i', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function completeMultipartUpload($remotePath, $uploadId, $parts) {
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath?uploadId=$uploadId";
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?><CompleteMultipartUpload>';
        foreach ($parts as $part) {
            $xml .= "<Part><PartNumber>{$part['PartNumber']}</PartNumber><ETag>\"{$part['ETag']}\"</ETag></Part>";
        }
        $xml .= '</CompleteMultipartUpload>';
        
        $headers = [
            'Content-Type' => 'application/xml',
            'Content-Length' => strlen($xml)
        ];
        $signedHeaders = $this->signer->signRequest('POST', $url, $headers, $xml);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    private function abortMultipartUpload($remotePath, $uploadId) {
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath?uploadId=$uploadId";
        
        $signedHeaders = $this->signer->signRequest('DELETE', $url, [], '');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Delete a file from S3
     */
    public function deleteFile($remotePath) {
        $remotePath = ltrim($remotePath, '/');
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath";
        
        $signedHeaders = $this->signer->signRequest('DELETE', $url, [], '');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode
        ];
    }
    
    /**
     * List objects in bucket
     */
    public function listObjects($prefix = '', $maxKeys = 1000) {
        $query = "list-type=2&max-keys=$maxKeys";
        if ($prefix) {
            $query .= "&prefix=" . urlencode($prefix);
        }
        
        $url = "{$this->endpoint}/{$this->bucket}?$query";
        
        $signedHeaders = $this->signer->signRequest('GET', $url, [], '');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP $httpCode", 'response' => $response];
        }
        
        // Parse XML response
        $objects = [];
        if (preg_match_all('/<Key>([^<]+)<\/Key>/', $response, $matches)) {
            foreach ($matches[1] as $key) {
                $objects[] = [
                    'key' => $key,
                    'cdn_url' => "{$this->cdnBaseUrl}/$key"
                ];
            }
        }
        
        return [
            'success' => true,
            'objects' => $objects,
            'count' => count($objects)
        ];
    }
    
    /**
     * Get object info (HEAD request)
     */
    public function getObjectInfo($remotePath) {
        $remotePath = ltrim($remotePath, '/');
        $url = "{$this->endpoint}/{$this->bucket}/$remotePath";
        
        $signedHeaders = $this->signer->signRequest('HEAD', $url, [], '');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP $httpCode (file may not exist)"];
        }
        
        $info = [
            'success' => true,
            'remote_path' => $remotePath,
            'cdn_url' => "{$this->cdnBaseUrl}/$remotePath"
        ];
        
        // Parse headers
        if (preg_match('/Content-Length:\s*(\d+)/i', $response, $m)) {
            $info['size'] = (int)$m[1];
            $info['size_formatted'] = $this->formatSize($info['size']);
        }
        if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $response, $m)) {
            $info['content_type'] = trim($m[1]);
        }
        if (preg_match('/Last-Modified:\s*([^\r\n]+)/i', $response, $m)) {
            $info['last_modified'] = trim($m[1]);
        }
        if (preg_match('/ETag:\s*"?([^"\r\n]+)"?/i', $response, $m)) {
            $info['etag'] = $m[1];
        }
        
        return $info;
    }
    
    /**
     * Test connection to S3
     */
    public function testConnection() {
        echo "Testing Sonic S3 CDN connection...\n";
        echo "Endpoint: " . S3_ENDPOINT . "\n";
        echo "Bucket: " . S3_BUCKET . "\n";
        echo "CDN URL: " . CDN_BASE_URL . "\n\n";
        
        // Try to list objects
        $result = $this->listObjects('', 1);
        
        if ($result['success']) {
            echo "✓ Connection successful!\n";
            echo "Objects in bucket: {$result['count']}\n";
            return true;
        } else {
            echo "✗ Connection failed: {$result['error']}\n";
            if (isset($result['response'])) {
                echo "Response: {$result['response']}\n";
            }
            return false;
        }
    }
    
    private function formatHeaders($headers) {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return $formatted;
    }
    
    private function getMimeType($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'json' => 'application/json',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript'
        ];
        
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// CLI Interface
if (php_sapi_name() === 'cli') {
    $client = new SonicS3Client();
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'test':
            $client->testConnection();
            break;
            
        case 'upload':
            if (!isset($argv[2])) {
                die("Usage: php sonic-s3-upload.php upload <local_file> [remote_path]\n");
            }
            $localFile = $argv[2];
            $remotePath = $argv[3] ?? null;
            
            echo "Uploading: $localFile\n";
            $result = $client->uploadLargeFile($localFile, $remotePath);
            
            if ($result['success']) {
                echo "✓ Upload successful!\n";
                echo "CDN URL: {$result['cdn_url']}\n";
                echo "Size: " . ($result['size'] ? number_format($result['size']) . ' bytes' : 'N/A') . "\n";
            } else {
                echo "✗ Upload failed: {$result['error']}\n";
                if (isset($result['response'])) {
                    echo "Response: {$result['response']}\n";
                }
            }
            break;
            
        case 'delete':
            if (!isset($argv[2])) {
                die("Usage: php sonic-s3-upload.php delete <remote_path>\n");
            }
            $remotePath = $argv[2];
            
            echo "Deleting: $remotePath\n";
            $result = $client->deleteFile($remotePath);
            
            if ($result['success']) {
                echo "✓ Delete successful!\n";
            } else {
                echo "✗ Delete failed (HTTP {$result['http_code']})\n";
            }
            break;
            
        case 'list':
            $prefix = $argv[2] ?? '';
            
            echo "Listing objects" . ($prefix ? " with prefix '$prefix'" : "") . "...\n\n";
            $result = $client->listObjects($prefix);
            
            if ($result['success']) {
                if (empty($result['objects'])) {
                    echo "No objects found.\n";
                } else {
                    foreach ($result['objects'] as $obj) {
                        echo "  {$obj['key']}\n";
                        echo "    CDN: {$obj['cdn_url']}\n";
                    }
                    echo "\nTotal: {$result['count']} objects\n";
                }
            } else {
                echo "✗ List failed: {$result['error']}\n";
            }
            break;
            
        case 'info':
            if (!isset($argv[2])) {
                die("Usage: php sonic-s3-upload.php info <remote_path>\n");
            }
            $remotePath = $argv[2];
            
            echo "Getting info for: $remotePath\n\n";
            $result = $client->getObjectInfo($remotePath);
            
            if ($result['success']) {
                echo "Path: {$result['remote_path']}\n";
                echo "CDN URL: {$result['cdn_url']}\n";
                if (isset($result['size_formatted'])) echo "Size: {$result['size_formatted']}\n";
                if (isset($result['content_type'])) echo "Type: {$result['content_type']}\n";
                if (isset($result['last_modified'])) echo "Modified: {$result['last_modified']}\n";
                if (isset($result['etag'])) echo "ETag: {$result['etag']}\n";
            } else {
                echo "✗ Failed: {$result['error']}\n";
            }
            break;
            
        case 'help':
        default:
            echo "Sonic S3 CDN Upload Tool\n";
            echo "========================\n\n";
            echo "Commands:\n";
            echo "  test                          Test S3 connection\n";
            echo "  upload <file> [remote_path]   Upload a file\n";
            echo "  delete <remote_path>          Delete a file\n";
            echo "  list [prefix]                 List objects\n";
            echo "  info <remote_path>            Get object info\n";
            echo "  help                          Show this help\n\n";
            echo "Examples:\n";
            echo "  php sonic-s3-upload.php test\n";
            echo "  php sonic-s3-upload.php upload /path/to/video.mp4\n";
            echo "  php sonic-s3-upload.php upload /path/to/video.mp4 videos/my-video.mp4\n";
            echo "  php sonic-s3-upload.php list videos/\n";
            echo "  php sonic-s3-upload.php info videos/my-video.mp4\n";
            break;
    }
}

// Export class for use in other scripts
return new SonicS3Client();
