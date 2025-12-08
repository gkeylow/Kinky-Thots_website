<?php
/**
 * PushrCDN Secure Token Generator
 * 
 * Generates secure tokens to protect CDN content from unauthorized access
 * and hotlinking.
 * 
 * Usage:
 *   require_once 'pushr-secure-tokens.php';
 *   $tokenGen = new PushrSecureTokens();
 *   $secureUrl = $tokenGen->generateFileToken('/path/to/file.jpg');
 */

class PushrSecureTokens {
    private $secret;
    private $config;
    
    /**
     * Constructor - loads configuration
     */
    public function __construct($configPath = null) {
        if ($configPath === null) {
            $configPath = __DIR__ . '/../config/pushr-cdn.json';
        }
        
        if (!file_exists($configPath)) {
            throw new Exception("Config file not found: $configPath");
        }
        
        $this->config = json_decode(file_get_contents($configPath), true);
        
        if (!isset($this->config['secret_token'])) {
            throw new Exception("Secret token not found in configuration");
        }
        
        $this->secret = $this->config['secret_token'];
    }
    
    /**
     * Generate a file token for a single file
     * 
     * @param string $filePath Full path to the file (e.g., /uploads/image.jpg)
     * @param string $zone Zone name (images, my-images, videos, my-videos)
     * @param int $expirationSeconds How long the token is valid (default: 1 hour)
     * @param string $ip Client IP address (default: current visitor)
     * @return string Secure URL with token
     */
    public function generateFileToken($filePath, $zone = 'images', $expirationSeconds = 3600, $ip = null) {
        // Get zone configuration
        if (!isset($this->config['zones'][$zone])) {
            throw new Exception("Zone not found: $zone");
        }
        
        $zoneConfig = $this->config['zones'][$zone];
        $host = $zoneConfig['base_url'];
        
        // Get client IP
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        // Parse file path
        $pathInfo = pathinfo($filePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['basename'];
        
        // Ensure directory ends with /
        if (substr($directory, -1) !== '/') {
            $directory .= '/';
        }
        
        // Calculate expiration timestamp
        $exp = time() + $expirationSeconds;
        
        // Generate MD5 hash
        $md5 = base64_encode(md5($this->secret . $exp . $directory . $filename . $ip, true));
        $md5 = strtr($md5, '+/', '-_');
        $md5 = str_replace('=', '', $md5);
        
        // Build secure URL
        $secureUrl = $host . '/' . $md5 . '/' . $exp . $directory . $filename;
        
        return $secureUrl;
    }
    
    /**
     * Generate a title token for a directory (for HLS or multiple files)
     * 
     * @param string $directoryPath Path to directory (e.g., /uploads/videos/2023/)
     * @param string $filename Main file to access (e.g., playlist.m3u8)
     * @param string $zone Zone name
     * @param int $expirationSeconds How long the token is valid (default: 1 hour)
     * @param string $ip Client IP address (default: current visitor)
     * @return string Secure URL with token
     */
    public function generateTitleToken($directoryPath, $filename, $zone = 'videos', $expirationSeconds = 3600, $ip = null) {
        // Get zone configuration
        if (!isset($this->config['zones'][$zone])) {
            throw new Exception("Zone not found: $zone");
        }
        
        $zoneConfig = $this->config['zones'][$zone];
        $host = $zoneConfig['base_url'];
        
        // Get client IP
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        // Ensure directory ends with /
        if (substr($directoryPath, -1) !== '/') {
            $directoryPath .= '/';
        }
        
        // Calculate expiration timestamp
        $exp = time() + $expirationSeconds;
        
        // Generate MD5 hash (note: filename is NOT included for title tokens)
        $md5 = base64_encode(md5($this->secret . $exp . $directoryPath . $ip, true));
        $md5 = strtr($md5, '+/', '-_');
        $md5 = str_replace('=', '', $md5);
        
        // Build secure URL with pushr query parameter
        $secureUrl = $host . '/' . $md5 . '/' . $exp . $directoryPath . $filename . '?pushr=' . $directoryPath;
        
        return $secureUrl;
    }
    
    /**
     * Generate secure URL for an image
     * 
     * @param string $imagePath Path to image
     * @param int $expirationSeconds Token validity period
     * @return string Secure URL
     */
    public function secureImage($imagePath, $expirationSeconds = 3600) {
        return $this->generateFileToken($imagePath, 'images', $expirationSeconds);
    }
    
    /**
     * Generate secure URL for a video
     * 
     * @param string $videoPath Path to video
     * @param int $expirationSeconds Token validity period
     * @return string Secure URL
     */
    public function secureVideo($videoPath, $expirationSeconds = 3600) {
        return $this->generateFileToken($videoPath, 'videos', $expirationSeconds);
    }
    
    /**
     * Generate secure URL for HLS video
     * 
     * @param string $hlsDirectory Directory containing HLS files
     * @param string $manifestFile Main manifest file (e.g., playlist.m3u8)
     * @param int $expirationSeconds Token validity period
     * @return string Secure URL
     */
    public function secureHLS($hlsDirectory, $manifestFile = 'playlist.m3u8', $expirationSeconds = 3600) {
        return $this->generateTitleToken($hlsDirectory, $manifestFile, 'videos', $expirationSeconds);
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP
     */
    private function getClientIP() {
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get first IP if multiple are present
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
    
    /**
     * Validate if a token is still valid (based on expiration time)
     * 
     * @param string $secureUrl URL with token
     * @return bool True if token is not expired
     */
    public function isTokenValid($secureUrl) {
        // Extract expiration timestamp from URL
        // Format: https://host/TOKEN/TIMESTAMP/path/file
        $parts = parse_url($secureUrl);
        $pathParts = explode('/', trim($parts['path'], '/'));
        
        if (count($pathParts) < 2) {
            return false;
        }
        
        $timestamp = intval($pathParts[1]);
        
        return time() < $timestamp;
    }
    
    /**
     * Get token expiration time
     * 
     * @param string $secureUrl URL with token
     * @return int|null Unix timestamp or null if invalid
     */
    public function getTokenExpiration($secureUrl) {
        $parts = parse_url($secureUrl);
        $pathParts = explode('/', trim($parts['path'], '/'));
        
        if (count($pathParts) < 2) {
            return null;
        }
        
        return intval($pathParts[1]);
    }
}

// Example usage (can be removed in production)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "PushrCDN Secure Token Generator\n";
    echo "================================\n\n";
    
    try {
        $tokenGen = new PushrSecureTokens();
        
        // Example 1: Secure an image
        echo "Example 1: Secure Image Token\n";
        echo "------------------------------\n";
        $imageUrl = $tokenGen->secureImage('/uploads/photo.jpg');
        echo "Secure URL: $imageUrl\n";
        echo "Valid: " . ($tokenGen->isTokenValid($imageUrl) ? 'Yes' : 'No') . "\n";
        echo "Expires: " . date('Y-m-d H:i:s', $tokenGen->getTokenExpiration($imageUrl)) . "\n\n";
        
        // Example 2: Secure a video
        echo "Example 2: Secure Video Token\n";
        echo "------------------------------\n";
        $videoUrl = $tokenGen->secureVideo('/porn/Haley/video.mp4');
        echo "Secure URL: $videoUrl\n\n";
        
        // Example 3: Secure HLS video
        echo "Example 3: Secure HLS Video (Title Token)\n";
        echo "------------------------------------------\n";
        $hlsUrl = $tokenGen->secureHLS('/porn/Haley/hls/', 'playlist.m3u8');
        echo "Secure URL: $hlsUrl\n\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
