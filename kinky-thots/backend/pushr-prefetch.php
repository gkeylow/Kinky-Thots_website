<?php
/**
 * PushrCDN Content Prefetch Script
 * 
 * Pushes local content to PushrCDN edge cache for faster delivery.
 * 
 * Usage (CLI):
 *   php pushr-prefetch.php <command> [options]
 * 
 * Commands:
 *   prefetch <url>              Prefetch a single URL
 *   prefetch-file <path>        Prefetch a local file (converts to URL)
 *   prefetch-dir <dir> [ext]    Prefetch all files in directory
 *   prefetch-videos             Prefetch all videos in /porn/
 *   prefetch-uploads            Prefetch all gallery uploads
 *   list-zones                  List available CDN zones
 *   status                      Show prefetch statistics
 */

// Load configuration
$configPath = '/var/www/kinky-thots/config/pushr-cdn.json';

if (!file_exists($configPath)) {
    die("Error: Config file not found at $configPath\n");
}

$config = json_decode(file_get_contents($configPath), true);

if (!$config || empty($config['api_key'])) {
    die("Error: Invalid config or missing API key\n");
}

$apiKey = $config['api_key'];
$apiUrl = $config['api_url'] ?? 'https://www.pushrcdn.com/api/v3';
$zones = $config['zones'] ?? [];
$defaultZone = $config['default_zone'] ?? 'videos';
$logFile = $config['prefetch_log'] ?? '/var/www/kinky-thots/logs/pushr-prefetch.log';

// Ensure log directory exists
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Log a message
 */
function logMessage($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
    echo $message . "\n";
}

/**
 * Prefetch a URL to CDN edge cache
 */
function prefetchUrl($apiUrl, $apiKey, $zoneId, $url) {
    // Remove trailing slash
    $url = rtrim($url, '/');
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '/prefetch',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'zone_id' => $zoneId,
            'url' => $url
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'APIKEY: ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'error', 'description' => 'cURL error: ' . $error];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['status']) && $data['status'] === 'success') {
        return ['status' => 'success', 'url' => $url];
    }
    
    return [
        'status' => 'error',
        'description' => $data['description'] ?? "HTTP $httpCode: $response",
        'url' => $url
    ];
}

/**
 * Convert local file path to public URL
 */
function fileToUrl($filePath, $baseUrl) {
    // Map local paths to web paths
    $mappings = [
        '/media/porn/' => '/porn/',
        '/var/www/kinky-thots/porn/' => '/porn/',
        '/var/www/kinky-thots/uploads/' => '/uploads/',
        '/var/www/kinky-thots/' => '/'
    ];
    
    foreach ($mappings as $localPath => $webPath) {
        if (strpos($filePath, $localPath) === 0) {
            $relativePath = str_replace($localPath, $webPath, $filePath);
            return rtrim($baseUrl, '/') . $relativePath;
        }
    }
    
    // If no mapping found, assume it's already a relative web path
    if (strpos($filePath, '/') === 0) {
        return rtrim($baseUrl, '/') . $filePath;
    }
    
    return $baseUrl . '/' . $filePath;
}

/**
 * Scan directory for files
 */
function scanDirectory($dir, $extensions = null) {
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            if ($extensions === null) {
                $files[] = $file->getPathname();
            } else {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, (array)$extensions)) {
                    $files[] = $file->getPathname();
                }
            }
        }
    }
    
    return $files;
}

/**
 * List zones
 */
function listZones($apiUrl, $apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '/zones/list',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'APIKEY: ' . $apiKey
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

/**
 * Batch prefetch with progress
 */
function batchPrefetch($files, $apiUrl, $apiKey, $zoneId, $baseUrl, $logFile) {
    $total = count($files);
    $success = 0;
    $failed = 0;
    
    echo "Starting prefetch of $total files...\n";
    echo "Zone ID: $zoneId\n";
    echo "Base URL: $baseUrl\n\n";
    
    logMessage("Starting batch prefetch: $total files, zone $zoneId", $logFile);
    
    foreach ($files as $index => $file) {
        $url = fileToUrl($file, $baseUrl);
        $num = $index + 1;
        
        echo "[$num/$total] Prefetching: " . basename($file) . "... ";
        
        $result = prefetchUrl($apiUrl, $apiKey, $zoneId, $url);
        
        if ($result['status'] === 'success') {
            echo "✓\n";
            $success++;
            logMessage("SUCCESS: $url", $logFile);
        } else {
            echo "✗ " . ($result['description'] ?? 'Unknown error') . "\n";
            $failed++;
            logMessage("FAILED: $url - " . ($result['description'] ?? 'Unknown error'), $logFile);
        }
        
        // Small delay to avoid rate limiting
        usleep(100000); // 100ms
    }
    
    echo "\n=== Prefetch Complete ===\n";
    echo "Success: $success\n";
    echo "Failed: $failed\n";
    echo "Total: $total\n";
    
    logMessage("Batch complete: $success success, $failed failed, $total total", $logFile);
    
    return ['success' => $success, 'failed' => $failed, 'total' => $total];
}

// ============ MAIN EXECUTION ============

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CLI only']);
    exit;
}

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'list-zones':
        echo "=== PushrCDN Zones ===\n";
        $result = listZones($apiUrl, $apiKey);
        if (isset($result['zone'])) {
            foreach ($result['zone'] as $zone) {
                echo "  [{$zone['zone_id']}] {$zone['zone_name']}\n";
            }
        } else {
            echo "Error: " . ($result['description'] ?? 'Unknown error') . "\n";
        }
        break;
        
    case 'prefetch':
        $url = $argv[2] ?? null;
        $zoneName = $argv[3] ?? $defaultZone;
        
        if (!$url) {
            echo "Usage: php pushr-prefetch.php prefetch <url> [zone]\n";
            exit(1);
        }
        
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        echo "Prefetching: $url\n";
        echo "Zone: $zoneName (ID: $zoneId)\n";
        
        $result = prefetchUrl($apiUrl, $apiKey, $zoneId, $url);
        
        if ($result['status'] === 'success') {
            echo "✓ Prefetch started successfully\n";
            logMessage("Prefetch: $url", $logFile);
        } else {
            echo "✗ Failed: " . ($result['description'] ?? 'Unknown error') . "\n";
            exit(1);
        }
        break;
        
    case 'prefetch-file':
        $filePath = $argv[2] ?? null;
        $zoneName = $argv[3] ?? $defaultZone;
        
        if (!$filePath || !file_exists($filePath)) {
            echo "Usage: php pushr-prefetch.php prefetch-file <path> [zone]\n";
            echo "Error: File not found\n";
            exit(1);
        }
        
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        $baseUrl = $zones[$zoneName]['base_url'] ?? 'https://kinky-thots.com';
        
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        $url = fileToUrl($filePath, $baseUrl);
        
        echo "File: $filePath\n";
        echo "URL: $url\n";
        echo "Zone: $zoneName (ID: $zoneId)\n";
        
        $result = prefetchUrl($apiUrl, $apiKey, $zoneId, $url);
        
        if ($result['status'] === 'success') {
            echo "✓ Prefetch started successfully\n";
            logMessage("Prefetch file: $url", $logFile);
        } else {
            echo "✗ Failed: " . ($result['description'] ?? 'Unknown error') . "\n";
            exit(1);
        }
        break;
        
    case 'prefetch-dir':
        $dir = $argv[2] ?? null;
        $extensions = isset($argv[3]) ? explode(',', $argv[3]) : null;
        $zoneName = $argv[4] ?? $defaultZone;
        
        if (!$dir || !is_dir($dir)) {
            echo "Usage: php pushr-prefetch.php prefetch-dir <directory> [extensions] [zone]\n";
            echo "Example: php pushr-prefetch.php prefetch-dir /media/porn mp4,webm videos\n";
            exit(1);
        }
        
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        $baseUrl = $zones[$zoneName]['base_url'] ?? 'https://kinky-thots.com';
        
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        $files = scanDirectory($dir, $extensions);
        
        if (empty($files)) {
            echo "No files found in $dir\n";
            exit(0);
        }
        
        batchPrefetch($files, $apiUrl, $apiKey, $zoneId, $baseUrl, $logFile);
        break;
        
    case 'prefetch-videos':
        $zoneName = $argv[2] ?? 'videos';
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        $baseUrl = $zones[$zoneName]['base_url'] ?? 'https://kinky-thots.com';
        
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        echo "=== Prefetching All Videos ===\n";
        
        $files = scanDirectory('/media/porn', ['mp4', 'webm', 'mkv']);
        
        if (empty($files)) {
            echo "No video files found\n";
            exit(0);
        }
        
        batchPrefetch($files, $apiUrl, $apiKey, $zoneId, $baseUrl, $logFile);
        break;
        
    case 'prefetch-uploads':
        $zoneName = $argv[2] ?? 'images';
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        $baseUrl = $zones[$zoneName]['base_url'] ?? 'https://kinky-thots.com';
        
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        echo "=== Prefetching Gallery Uploads ===\n";
        
        $files = scanDirectory('/var/www/kinky-thots/uploads', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4']);
        
        if (empty($files)) {
            echo "No upload files found\n";
            exit(0);
        }
        
        batchPrefetch($files, $apiUrl, $apiKey, $zoneId, $baseUrl, $logFile);
        break;
        
    case 'prefetch-images':
        $zoneName = $argv[2] ?? 'images';
        $zoneId = $zones[$zoneName]['zone_id'] ?? null;
        $baseUrl = $zones[$zoneName]['base_url'] ?? 'https://kinky-thots.com';
        
        if (!$zoneId) {
            echo "Error: Zone '$zoneName' not found\n";
            exit(1);
        }
        
        echo "=== Prefetching All Images ===\n";
        
        $files = scanDirectory('/media/porn', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        
        if (empty($files)) {
            echo "No image files found\n";
            exit(0);
        }
        
        batchPrefetch($files, $apiUrl, $apiKey, $zoneId, $baseUrl, $logFile);
        break;
        
    case 'status':
        echo "=== PushrCDN Prefetch Status ===\n\n";
        
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recent = array_slice($lines, -20);
            
            echo "Recent activity (last 20 entries):\n";
            foreach ($recent as $line) {
                echo "  $line\n";
            }
            
            // Count stats
            $successCount = 0;
            $failCount = 0;
            foreach ($lines as $line) {
                if (strpos($line, 'SUCCESS:') !== false) $successCount++;
                if (strpos($line, 'FAILED:') !== false) $failCount++;
            }
            
            echo "\nTotal Statistics:\n";
            echo "  Successful prefetches: $successCount\n";
            echo "  Failed prefetches: $failCount\n";
        } else {
            echo "No prefetch activity logged yet.\n";
        }
        break;
        
    case 'help':
    default:
        echo "PushrCDN Content Prefetch Tool\n";
        echo "==============================\n\n";
        echo "Usage:\n";
        echo "  php pushr-prefetch.php <command> [options]\n\n";
        echo "Commands:\n";
        echo "  list-zones                    List available CDN zones\n";
        echo "  prefetch <url> [zone]         Prefetch a single URL\n";
        echo "  prefetch-file <path> [zone]   Prefetch a local file\n";
        echo "  prefetch-dir <dir> [ext] [zone]  Prefetch directory\n";
        echo "  prefetch-videos [zone]        Prefetch all videos in /porn/\n";
        echo "  prefetch-uploads [zone]       Prefetch gallery uploads\n";
        echo "  prefetch-images [zone]        Prefetch all images\n";
        echo "  status                        Show prefetch statistics\n";
        echo "  help                          Show this help\n\n";
        echo "Examples:\n";
        echo "  php pushr-prefetch.php list-zones\n";
        echo "  php pushr-prefetch.php prefetch https://kinky-thots.com/porn/video.mp4\n";
        echo "  php pushr-prefetch.php prefetch-file /media/porn/kinky-thots-shorts/video.mp4\n";
        echo "  php pushr-prefetch.php prefetch-dir /media/porn mp4,webm videos\n";
        echo "  php pushr-prefetch.php prefetch-videos\n";
        echo "  php pushr-prefetch.php prefetch-uploads images\n\n";
        echo "Available zones: " . implode(', ', array_keys($zones)) . "\n";
        echo "Default zone: $defaultZone\n";
        break;
}
