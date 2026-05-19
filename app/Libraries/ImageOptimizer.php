<?php

namespace App\Libraries;

class ImageOptimizer
{
    /**
     * Resizes and converts an uploaded image to WebP format.
     *
     * @param string $sourcePath The absolute path to the temporary uploaded file.
     * @param int $maxWidth The maximum allowed width (in pixels).
     * @param int $quality The WebP compression quality (0-100). 80 is optimal.
     * @return string|false Returns the path to the newly optimized file, or false on failure.
     */
    public function optimizeToWebp(string $sourcePath, int $maxWidth = 1200, int $quality = 80)
    {
        // 1. Get original dimensions and mime type
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        list($origWidth, $origHeight) = $info;
        $mime = $info['mime'];

        // 2. Calculate new dimensions (maintain aspect ratio)
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($origHeight * ($maxWidth / $origWidth));
        }

        // 3. Create a blank canvas for the new image
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        // Safely handle transparency for PNGs
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);

        // 4. Load the original image into memory
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                imagedestroy($canvas);
                return false; // Unsupported file type (e.g., GIF, SVG)
        }

        // 5. Resample (Resize) the image onto the canvas
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // 6. Save the new image as a WebP temporary file
        $optimizedPath = $sourcePath . '_optimized.webp';
        
        // imagewebp compresses the image natively
        if (!imagewebp($canvas, $optimizedPath, $quality)) {
            $optimizedPath = false;
        }

        // 7. Free up server RAM immediately
        imagedestroy($canvas);
        imagedestroy($source);

        return $optimizedPath;
    }
}