<?php
// cache_manager.php - Standalone cache management class
class CacheManager {
    private $cacheDir;
    private $defaultExpiry;
    
    public function __construct($cacheDir = 'cache/', $defaultExpiry = 300) {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->defaultExpiry = $defaultExpiry;
        $this->ensureCacheDirectory();
    }
    
    /**
     * Ensure cache directory exists
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
        $filePath = $this->cacheDir . $key . '.cache';
        
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
     * Get cached data
     */
    public function get($key, $expiry = null) {
        if (!$this->exists($key, $expiry)) {
            return null;
        }
        
        $filePath = $this->cacheDir . $key . '.cache';
        $data = file_get_contents($filePath);
        
        if ($data === false) {
            return null;
        }
        
        return unserialize($data);
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $data) {
        $filePath = $this->cacheDir . $key . '.cache';
        $serializedData = serialize($data);
        
        if (file_put_contents($filePath, $serializedData, LOCK_EX) === false) {
            error_log("Failed to write cache file: $filePath");
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete specific cache entry
     */
    public function delete($key) {
        $filePath = $this->cacheDir . $key . '.cache';
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
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpired($expiry = null) {
        $expiry = $expiry ?? $this->defaultExpiry;
        $files = glob($this->cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > $expiry) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $totalFiles = count($files);
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            if ((time() - filemtime($file)) > $this->defaultExpiry) {
                $expiredFiles++;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'expired_files' => $expiredFiles,
            'cache_dir' => $this->cacheDir
        ];
    }
}
?>