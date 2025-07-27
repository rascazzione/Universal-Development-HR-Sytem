<?php
/**
 * Media Management Class for Growth Evidence System
 * Handles file uploads, validation, and storage for evidence entries
 */

require_once __DIR__ . '/../config/config.php';

class MediaManager {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct() {
        $this->uploadDir = __DIR__ . '/../uploads/evidence/';
        $this->allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'webm'],
            'document' => ['pdf', 'doc', 'docx']
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload a file and store its metadata
     * @param array $file The $_FILES array element
     * @param int $entryId The evidence entry ID to associate with
     * @return array|false
     */
    public function uploadFile($file, $entryId) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = $this->uploadDir . $uniqueName;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            // Determine file type
            $fileType = $this->getFileType($extension);
            
            // Generate thumbnail for images
            $thumbnailPath = null;
            if ($fileType === 'image') {
                $thumbnailPath = $this->generateThumbnail($uploadPath, $uniqueName);
            }
            
            // Store file metadata in database
            $sql = "INSERT INTO evidence_attachments (entry_id, filename, original_name, file_type, file_size, mime_type, storage_path, thumbnail_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $attachmentId = insertRecord($sql, [
                $entryId,
                $uniqueName,
                $file['name'],
                $fileType,
                $file['size'],
                $file['type'],
                $uploadPath,
                $thumbnailPath
            ]);
            
            return [
                'attachment_id' => $attachmentId,
                'filename' => $uniqueName,
                'original_name' => $file['name'],
                'file_type' => $fileType,
                'file_size' => $file['size'],
                'storage_path' => $uploadPath,
                'thumbnail_path' => $thumbnailPath
            ];
        } catch (Exception $e) {
            error_log("Upload file error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Validate uploaded file
     * @param array $file The $_FILES array element
     * @return array
     */
    public function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'message' => "File size exceeds maximum allowed size of " . ($this->maxFileSize / 1024 / 1024) . "MB"
            ];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isValidExtension = false;
        
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $isValidExtension = true;
                break;
            }
        }
        
        if (!$isValidExtension) {
            return [
                'valid' => false,
                'message' => "File type not allowed. Allowed types: " . implode(', ', array_merge(...array_values($this->allowedTypes)))
            ];
        }
        
        return [
            'valid' => true,
            'message' => "File is valid"
        ];
    }
    
    /**
     * Get file type based on extension
     * @param string $extension
     * @return string
     */
    private function getFileType($extension) {
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return 'document'; // default
    }
    
    /**
     * Generate thumbnail for image files
     * @param string $imagePath
     * @param string $filename
     * @return string|null
     */
    public function generateThumbnail($imagePath, $filename) {
        // Only generate thumbnails for image files
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes['image'])) {
            return null;
        }
        
        // Create thumbnail filename
        $thumbnailName = 'thumb_' . $filename;
        $thumbnailPath = $this->uploadDir . $thumbnailName;
        
        try {
            // Create image resource based on file type
            $image = null;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }
            
            if (!$image) {
                return null;
            }
            
            // Get original dimensions
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Calculate new dimensions (max 150x150)
            $newWidth = 150;
            $newHeight = 150;
            
            if ($width > $height) {
                $newHeight = ($height / $width) * $newWidth;
            } else {
                $newWidth = ($width / $height) * $newHeight;
            }
            
            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG and GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }
            
            // Resize image
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save thumbnail
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 80);
                    break;
                case 'png':
                    imagepng($thumbnail, $thumbnailPath);
                    break;
                case 'gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
            }
            
            // Free memory
            imagedestroy($image);
            imagedestroy($thumbnail);
            
            return $thumbnailPath;
        } catch (Exception $e) {
            error_log("Thumbnail generation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get secure URL for file access
     * @param string $filename
     * @return string
     */
    public function getSecureUrl($filename) {
        // In a production environment, this would generate a secure, time-limited URL
        // For now, we'll return a path that can be accessed through a secure handler
        return "/secure-media/" . urlencode($filename);
    }
    
    /**
     * Get upload error message
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error";
        }
    }
    
    /**
     * Get attachments for an evidence entry
     * @param int $entryId
     * @return array
     */
    public function getAttachmentsForEntry($entryId) {
        $sql = "SELECT attachment_id, filename, original_name, file_type, file_size, mime_type, uploaded_at
                FROM evidence_attachments 
                WHERE entry_id = ? 
                ORDER BY uploaded_at ASC";
        
        return fetchAll($sql, [$entryId]);
    }
    
    /**
     * Delete attachment
     * @param int $attachmentId
     * @return bool
     */
    public function deleteAttachment($attachmentId) {
        try {
            // Get attachment details
            $sql = "SELECT filename, storage_path, thumbnail_path FROM evidence_attachments WHERE attachment_id = ?";
            $attachment = fetchOne($sql, [$attachmentId]);
            
            if (!$attachment) {
                return false;
            }
            
            // Delete files from filesystem
            if (file_exists($attachment['storage_path'])) {
                unlink($attachment['storage_path']);
            }
            
            if ($attachment['thumbnail_path'] && file_exists($attachment['thumbnail_path'])) {
                unlink($attachment['thumbnail_path']);
            }
            
            // Delete from database
            $sql = "DELETE FROM evidence_attachments WHERE attachment_id = ?";
            $affected = updateRecord($sql, [$attachmentId]);
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete attachment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get allowed file types for display
     * @return array
     */
    public function getAllowedFileTypes() {
        return $this->allowedTypes;
    }
    
    /**
     * Get maximum file size for display
     * @return int
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }
    
    /**
     * Get formatted file size
     * @param int $bytes
     * @return string
     */
    public function getFormattedFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes > 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
?>
