<?php
// enhanced_cache_manager.php - Enhanced cache management with size controls
class EnhancedCacheManager {
    private $cacheDir;
    private $defaultExpiry;
    private $maxCacheSize; // Maximum cache size in MB
    private $maxFileSize;  // Maximum individual file size in MB
    private $compressionEnabled;
    
    public function __construct($cacheDir = 'cache/', $defaultExpiry = 30, $maxCacheSizeMB = 100, $maxFileSizeMB = 10) {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->defaultExpiry = $defaultExpiry;
        $this->maxCacheSize = $maxCacheSizeMB * 1024 * 1024; // Convert to bytes
        $this->maxFileSize = $maxFileSizeMB * 1024 * 1024;   // Convert to bytes
        $this->compressionEnabled = function_exists('gzcompress');
        $this->ensureCacheDirectory();
    }
    
    /**
     * Ensure cache directory exists with proper permissions
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("Cannot create cache directory: " . $this->cacheDir);
            }
        }
        
        // Create .htaccess to protect cache directory
        $htaccessPath = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
        
        // Create index.php to prevent directory browsing
        $indexPath = $this->cacheDir . 'index.php';
        if (!file_exists($indexPath)) {
            file_put_contents($indexPath, "<?php\n// Access denied\nheader('HTTP/1.0 403 Forbidden');\nexit();\n");
        }
    }
    
    /**
     * Generate cache key from parameters
     */
    public function generateKey($prefix, $params = []) {
        $keyData = $prefix . '_' . serialize($params);
        return md5($keyData);
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function exists($key, $expiry = null) {
        $expiry = $expiry ?? $this->defaultExpiry;
        $filePath = $this->getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Check if cache has expired
        if ((time() - filemtime($filePath)) > $expiry) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get cached data with memory-efficient reading
     */
    public function get($key, $expiry = null) {
        if (!$this->exists($key, $expiry)) {
            return null;
        }
        
        $filePath = $this->getCacheFilePath($key);
        
        try {
            // Check file size before reading
            $fileSize = filesize($filePath);
            if ($fileSize > $this->maxFileSize) {
                error_log("Cache file too large: $filePath ($fileSize bytes)");
                $this->delete($key);
                return null;
            }
            
            $data = file_get_contents($filePath);
            if ($data === false) {
                return null;
            }
            
            // Handle compression
            if ($this->compressionEnabled && substr($data, 0, 10) === 'COMPRESSED') {
                $data = gzuncompress(substr($data, 10));
                if ($data === false) {
                    error_log("Failed to decompress cache data for key: $key");
                    $this->delete($key);
                    return null;
                }
            }
            
            $result = unserialize($data);
            if ($result === false && $data !== serialize(false)) {
                error_log("Failed to unserialize cache data for key: $key");
                $this->delete($key);
                return null;
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Cache read error for key $key: " . $e->getMessage());
            $this->delete($key);
            return null;
        }
    }
    
    /**
     * Store data in cache with size management
     */
    public function set($key, $data) {
        try {
            $serializedData = serialize($data);
            $dataSize = strlen($serializedData);
            
            // Check if individual data is too large
            if ($dataSize > $this->maxFileSize) {
                error_log("Data too large for cache ({$dataSize} bytes): $key");
                return false;
            }
            
            // Compress if enabled and beneficial
            if ($this->compressionEnabled && $dataSize > 1024) { // Only compress if > 1KB
                $compressedData = gzcompress($serializedData, 6);
                if ($compressedData !== false && strlen($compressedData) < $dataSize) {
                    $serializedData = 'COMPRESSED' . $compressedData;
                }
            }
            
            // Ensure we have space
            $this->ensureCacheSpace(strlen($serializedData));
            
            $filePath = $this->getCacheFilePath($key);
            
            // Use atomic write
            $tempFile = $filePath . '.tmp.' . getmypid();
            
            if (file_put_contents($tempFile, $serializedData, LOCK_EX) === false) {
                error_log("Failed to write cache temp file: $tempFile");
                return false;
            }
            
            if (!rename($tempFile, $filePath)) {
                unlink($tempFile);
                error_log("Failed to move cache temp file: $tempFile to $filePath");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Cache write error for key $key: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure we have enough cache space
     */
    private function ensureCacheSpace($requiredSize) {
        $currentSize = $this->getCacheDirectorySize();
        
        if (($currentSize + $requiredSize) > $this->maxCacheSize) {
            $this->cleanupCache($requiredSize);
        }
    }
    
    /**
     * Get total cache directory size
     */
    private function getCacheDirectorySize() {
        $size = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }
        
        return $size;
    }
    
    /**
     * Cleanup cache using LRU strategy
     */
    private function cleanupCache($requiredSpace = 0) {
        $files = glob($this->cacheDir . '*.cache');
        
        // Sort by last access time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $freedSpace = 0;
        $targetSpace = $requiredSpace + ($this->maxCacheSize * 0.1); // Free 10% extra
        
        foreach ($files as $file) {
            if ($freedSpace >= $targetSpace) {
                break;
            }
            
            $fileSize = filesize($file);
            if (unlink($file)) {
                $freedSpace += $fileSize;
            }
        }
        
        error_log("Cache cleanup: freed {$freedSpace} bytes");
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFilePath($key) {
        return $this->cacheDir . $key . '.cache';
    }
    
    /**
     * Delete specific cache entry
     */
    public function delete($key) {
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }
    
    /**
     * Clear all cache entries
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        error_log("Cache cleared: removed {$cleared} files");
        return $cleared;
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpired($expiry = null) {
        $expiry = $expiry ?? $this->defaultExpiry;
        $files = glob($this->cacheDir . '*.cache');
        $cleaned = 0;
        $freedSpace = 0;
        
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > $expiry) {
                $size = filesize($file);
                if (unlink($file)) {
                    $cleaned++;
                    $freedSpace += $size;
                }
            }
        }
        
        if ($cleaned > 0) {
            error_log("Expired cache cleanup: removed {$cleaned} files, freed {$freedSpace} bytes");
        }
        
        return $cleaned;
    }
    
    /**
     * Get comprehensive cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $totalFiles = count($files);
        $expiredFiles = 0;
        $largestFile = 0;
        $oldestFile = time();
        $newestFile = 0;
        
        foreach ($files as $file) {
            $fileSize = filesize($file);
            $fileTime = filemtime($file);
            
            $totalSize += $fileSize;
            $largestFile = max($largestFile, $fileSize);
            $oldestFile = min($oldestFile, $fileTime);
            $newestFile = max($newestFile, $fileTime);
            
            if ((time() - $fileTime) > $this->defaultExpiry) {
                $expiredFiles++;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'max_cache_size_mb' => round($this->maxCacheSize / 1024 / 1024, 2),
            'cache_usage_percent' => $this->maxCacheSize > 0 ? round(($totalSize / $this->maxCacheSize) * 100, 2) : 0,
            'expired_files' => $expiredFiles,
            'largest_file_bytes' => $largestFile,
            'largest_file_mb' => round($largestFile / 1024 / 1024, 2),
            'oldest_file_age' => $totalFiles > 0 ? (time() - $oldestFile) : 0,
            'newest_file_age' => $totalFiles > 0 ? (time() - $newestFile) : 0,
            'cache_dir' => $this->cacheDir,
            'compression_enabled' => $this->compressionEnabled,
            'max_file_size_mb' => round($this->maxFileSize / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Optimize cache - cleanup expired and compress if needed
     */
    public function optimize() {
        $stats = $this->getStats();
        $optimized = [];
        
        // Clear expired files first
        $expiredCleaned = $this->clearExpired();
        $optimized['expired_cleaned'] = $expiredCleaned;
        
        // If still over limit, do general cleanup
        if ($stats['cache_usage_percent'] > 80) {
            $this->cleanupCache(0);
            $optimized['size_cleanup'] = true;
        } else {
            $optimized['size_cleanup'] = false;
        }
        
        return $optimized;
    }
    
    /**
     * Health check for cache system
     */
    public function healthCheck() {
        $issues = [];
        $stats = $this->getStats();
        
        // Check directory permissions
        if (!is_writable($this->cacheDir)) {
            $issues[] = "Cache directory not writable: " . $this->cacheDir;
        }
        
        // Check size limits
        if ($stats['cache_usage_percent'] > 90) {
            $issues[] = "Cache usage over 90%: " . $stats['cache_usage_percent'] . "%";
        }
        
        // Check for too many expired files
        if ($stats['expired_files'] > ($stats['total_files'] * 0.3)) {
            $issues[] = "Too many expired files: " . $stats['expired_files'] . "/" . $stats['total_files'];
        }
        
        // Check for oversized files
        if ($stats['largest_file_mb'] > ($this->maxFileSize / 1024 / 1024 * 0.8)) {
            $issues[] = "Large files detected, max: " . $stats['largest_file_mb'] . "MB";
        }
        
        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'stats' => $stats
        ];
    }
}
?>