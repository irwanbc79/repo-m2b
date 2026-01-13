<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Shipment;
use App\Models\FieldPhoto;

class ImageProcessingService
{
    protected int $maxWidth = 1920;
    protected int $maxHeight = 1920;
    protected int $thumbnailSize = 400;
    protected int $quality = 85;
    
    // Path logo M2B
    protected string $logoPath = 'images/m2b-logo.png';
    
    /**
     * Process upload dengan watermark profesional + Logo M2B
     */
    public function processUpload($file, $shipmentId, $userId, ?string $description = null): array
    {
        $shipment = Shipment::find($shipmentId);
        $shipmentNumber = $shipment?->awb_number ?? $shipment?->bl_number ?? 'SHP-' . $shipmentId;
        
        $user = \App\Models\User::find($userId);
        $uploaderName = $user?->name ?? 'Unknown';
        
        $year = date('Y');
        $month = date('m');
        $directory = "field-photos/{$year}/{$month}/{$shipmentId}";
        $thumbnailDir = "{$directory}/thumbnails";
        
        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->makeDirectory($thumbnailDir);
        
        $filename = time() . '_' . Str::random(8) . '.jpg';
        
        try {
            $sourcePath = $file->getRealPath();
            $imageInfo = @getimagesize($sourcePath);
            
            if (!$imageInfo) {
                throw new \Exception('Invalid image file');
            }
            
            list($origWidth, $origHeight, $imageType) = $imageInfo;
            
            $sourceImage = $this->createImageFromFile($sourcePath, $imageType);
            
            if (!$sourceImage) {
                throw new \Exception('Could not create image from file');
            }
            
            $sourceImage = $this->fixOrientation($sourceImage, $sourcePath);
            
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            
            // Resize if needed
            if ($origWidth > $this->maxWidth || $origHeight > $this->maxHeight) {
                $ratio = min($this->maxWidth / $origWidth, $this->maxHeight / $origHeight);
                $newWidth = (int)($origWidth * $ratio);
                $newHeight = (int)($origHeight * $ratio);
                
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($sourceImage);
                $sourceImage = $resizedImage;
            }
            
            $finalWidth = imagesx($sourceImage);
            $finalHeight = imagesy($sourceImage);
            
            // WATERMARK DENGAN LOGO M2B
            $this->addWatermarkWithLogo($sourceImage, $shipmentNumber, $uploaderName, $description);
            
            // Save main image
            $mainPath = $directory . '/' . $filename;
            $tempMain = tempnam(sys_get_temp_dir(), 'img_main_');
            imagejpeg($sourceImage, $tempMain, $this->quality);
            Storage::disk('public')->put($mainPath, file_get_contents($tempMain));
            @unlink($tempMain);
            
            // Create thumbnail (tanpa watermark)
            $thumbRatio = min($this->thumbnailSize / $finalWidth, $this->thumbnailSize / $finalHeight);
            $thumbWidth = (int)($finalWidth * $thumbRatio);
            $thumbHeight = (int)($finalHeight * $thumbRatio);
            
            $thumbSource = $this->createImageFromFile($sourcePath, $imageType);
            $thumbSource = $this->fixOrientation($thumbSource, $sourcePath);
            
            $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumbImage, $thumbSource, 0, 0, 0, 0, $thumbWidth, $thumbHeight, imagesx($thumbSource), imagesy($thumbSource));
            
            $thumbPath = $thumbnailDir . '/' . $filename;
            $tempThumb = tempnam(sys_get_temp_dir(), 'img_thumb_');
            imagejpeg($thumbImage, $tempThumb, 80);
            Storage::disk('public')->put($thumbPath, file_get_contents($tempThumb));
            @unlink($tempThumb);
            
            imagedestroy($sourceImage);
            imagedestroy($thumbSource);
            imagedestroy($thumbImage);
            
            return [
                'path' => $mainPath,
                'thumbnail_path' => $thumbPath,
                'width' => $finalWidth,
                'height' => $finalHeight,
                'size' => Storage::disk('public')->size($mainPath),
                'mime_type' => 'image/jpeg',
            ];
            
        } catch (\Exception $e) {
            Log::error('Image processing error: ' . $e->getMessage());
            
            $fallbackPath = $directory . '/' . $filename;
            $file->storeAs($directory, $filename, 'public');
            
            return [
                'path' => $fallbackPath,
                'thumbnail_path' => null,
                'width' => null,
                'height' => null,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        }
    }
    
    /**
     * Watermark dengan Logo M2B - Design Profesional
     */
    protected function addWatermarkWithLogo($image, string $shipmentNumber, string $uploaderName, ?string $description = null): void
    {
        $imgWidth = imagesx($image);
        $imgHeight = imagesy($image);
        
        // ============================================
        // UKURAN STAMP RESPONSIF
        // ============================================
        $stampW = max(420, min(550, (int)($imgWidth * 0.32)));
        $hasDescription = !empty($description) && strlen(trim($description)) > 0;
        
        // Hitung tinggi berdasarkan konten
        $headerH = 45;
        $contentH = 75;
        $descH = $hasDescription ? 35 : 0;
        $stampH = $headerH + $contentH + $descH + 15;
        
        $margin = max(20, (int)($imgWidth * 0.018));
        
        // Posisi di pojok kanan bawah
        $stampX = $imgWidth - $stampW - $margin;
        $stampY = $imgHeight - $stampH - $margin;
        
        // ===== WARNA =====
        $bgHeader = imagecolorallocatealpha($image, 0, 45, 90, 15);       // Biru tua header
        $bgContent = imagecolorallocatealpha($image, 0, 30, 60, 25);      // Biru content
        $bgDesc = imagecolorallocatealpha($image, 255, 255, 255, 85);     // Putih transparan untuk desc
        $borderColor = imagecolorallocate($image, 255, 255, 255);          // Border putih solid
        $accentYellow = imagecolorallocate($image, 255, 210, 0);           // Kuning untuk shipment
        $textWhite = imagecolorallocate($image, 255, 255, 255);
        $textLight = imagecolorallocate($image, 220, 220, 220);
        $textDark = imagecolorallocate($image, 30, 30, 30);
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
        
        // ===== 1. SHADOW =====
        imagefilledrectangle($image, $stampX + 4, $stampY + 4, $stampX + $stampW + 4, $stampY + $stampH + 4, $shadowColor);
        
        // ===== 2. BACKGROUND HEADER (dengan logo) =====
        imagefilledrectangle($image, $stampX, $stampY, $stampX + $stampW, $stampY + $headerH, $bgHeader);
        
        // ===== 3. BACKGROUND CONTENT =====
        imagefilledrectangle($image, $stampX, $stampY + $headerH, $stampX + $stampW, $stampY + $stampH, $bgContent);
        
        // ===== 4. BORDER LUAR =====
        imagerectangle($image, $stampX, $stampY, $stampX + $stampW, $stampY + $stampH, $borderColor);
        
        // Garis pemisah header
        imageline($image, $stampX, $stampY + $headerH, $stampX + $stampW, $stampY + $headerH, $borderColor);
        
        // ===== 5. LOGO M2B =====
        $this->addLogoToImage($image, $stampX + 12, $stampY + 8, 30);
        
        // ===== 6. FONT SETUP =====
        $fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $useTTF = file_exists($fontBold);
        
        $fontLarge = max(13, (int)($stampW * 0.032));
        $fontMedium = max(11, (int)($stampW * 0.026));
        $fontSmall = max(10, (int)($stampW * 0.023));
        
        // ===== 7. HEADER TEXT =====
        $headerTextX = $stampX + 50; // Setelah logo
        $headerTextY = $stampY + 18;
        
        if ($useTTF) {
            imagettftext($image, $fontLarge, 0, $headerTextX, $headerTextY + $fontLarge, $textWhite, $fontBold, "M2B PORTAL");
            imagettftext($image, $fontSmall - 1, 0, $headerTextX, $headerTextY + $fontLarge + 15, $textLight, $fontRegular, "DOKUMEN PERUSAHAAN");
        } else {
            imagestring($image, 5, $headerTextX, $headerTextY, "M2B PORTAL", $textWhite);
            imagestring($image, 2, $headerTextX, $headerTextY + 18, "DOKUMEN PERUSAHAAN", $textLight);
        }
        
        // ===== 8. CONTENT AREA =====
        $contentY = $stampY + $headerH + 12;
        $contentX = $stampX + 15;
        
        // Nomor Shipment (Kuning)
        if ($useTTF) {
            imagettftext($image, $fontMedium + 1, 0, $contentX, $contentY + $fontMedium, $accentYellow, $fontBold, "No: " . $shipmentNumber);
        } else {
            imagestring($image, 4, $contentX, $contentY, "No: " . $shipmentNumber, $accentYellow);
        }
        
        $contentY += $fontMedium + 12;
        
        // Tanggal & Waktu
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dayName = $days[(int)date('w')];
        $dateText = $dayName . ', ' . date('d/m/Y') . ' - ' . date('H:i:s') . ' WIB';
        
        if ($useTTF) {
            imagettftext($image, $fontSmall, 0, $contentX, $contentY + $fontSmall, $textLight, $fontRegular, $dateText);
        } else {
            imagestring($image, 2, $contentX, $contentY, $dateText, $textLight);
        }
        
        $contentY += $fontSmall + 10;
        
        // Surveyor
        $uploaderText = "Surveyor: " . mb_substr($uploaderName, 0, 35);
        if ($useTTF) {
            imagettftext($image, $fontSmall, 0, $contentX, $contentY + $fontSmall, $textLight, $fontRegular, $uploaderText);
        } else {
            imagestring($image, 2, $contentX, $contentY, $uploaderText, $textLight);
        }
        
        // ===== 9. KETERANGAN (jika ada) =====
        if ($hasDescription) {
            $contentY += $fontSmall + 12;
            
            // Garis pemisah
            imageline($image, $stampX + 10, $contentY, $stampX + $stampW - 10, $contentY, $borderColor);
            
            $contentY += 8;
            
            // Background keterangan (putih transparan)
            imagefilledrectangle($image, $stampX + 10, $contentY, $stampX + $stampW - 10, $contentY + 22, $bgDesc);
            
            // Potong teks jika terlalu panjang
            $description = trim($description);
            $maxLen = (int)($stampW / 8);
            if (mb_strlen($description) > $maxLen) {
                $description = mb_substr($description, 0, $maxLen - 3) . '...';
            }
            
            if ($useTTF) {
                imagettftext($image, $fontSmall, 0, $stampX + 15, $contentY + 15, $textDark, $fontRegular, "📝 " . $description);
            } else {
                imagestring($image, 2, $stampX + 15, $contentY + 4, $description, $textDark);
            }
        }
        
        // ===== 10. WATERMARK DIAGONAL (opsional, sangat transparan) =====
        $diagColor = imagecolorallocatealpha($image, 255, 255, 255, 110);
        if ($useTTF && $imgWidth > 800) {
            $diagSize = max(16, (int)($imgWidth * 0.018));
            $diagText = "DOKUMEN PERUSAHAAN";
            
            $bbox = imagettfbbox($diagSize, -18, $fontBold, $diagText);
            $diagX = ($imgWidth - abs($bbox[2] - $bbox[0])) / 2;
            $diagY = $imgHeight / 2;
            
            imagettftext($image, $diagSize, -18, (int)$diagX, (int)$diagY, $diagColor, $fontBold, $diagText);
        }
        
        // ===== 11. LOGO KECIL DI POJOK KIRI BAWAH =====
        $logoSmallX = $margin;
        $logoSmallY = $imgHeight - $margin - 30;
        
        // Background untuk area logo
        $logoBgColor = imagecolorallocatealpha($image, 0, 0, 0, 70);
        imagefilledrectangle($image, $logoSmallX - 5, $logoSmallY - 5, $logoSmallX + 140, $logoSmallY + 25, $logoBgColor);
        
        // Logo kecil
        $this->addLogoToImage($image, $logoSmallX, $logoSmallY, 20);
        
        // URL
        if ($useTTF) {
            imagettftext($image, $fontSmall - 1, 0, $logoSmallX + 28, $logoSmallY + 14, $textWhite, $fontRegular, "portal.m2b.co.id");
        } else {
            imagestring($image, 2, $logoSmallX + 28, $logoSmallY + 2, "portal.m2b.co.id", $textWhite);
        }
    }
    
    /**
     * Tambah logo M2B ke image
     */
    protected function addLogoToImage($image, int $x, int $y, int $size): void
    {
        // Coba load logo dari public path
        $logoFullPath = public_path($this->logoPath);
        
        if (!file_exists($logoFullPath)) {
            // Fallback: coba dari storage
            $logoFullPath = storage_path('app/public/' . $this->logoPath);
        }
        
        if (!file_exists($logoFullPath)) {
            // Logo tidak ditemukan, gambar placeholder
            $this->drawLogoPlaceholder($image, $x, $y, $size);
            return;
        }
        
        try {
            $logoInfo = @getimagesize($logoFullPath);
            if (!$logoInfo) {
                $this->drawLogoPlaceholder($image, $x, $y, $size);
                return;
            }
            
            $logoType = $logoInfo[2];
            
            switch ($logoType) {
                case IMAGETYPE_PNG:
                    $logo = @imagecreatefrompng($logoFullPath);
                    break;
                case IMAGETYPE_JPEG:
                    $logo = @imagecreatefromjpeg($logoFullPath);
                    break;
                case IMAGETYPE_GIF:
                    $logo = @imagecreatefromgif($logoFullPath);
                    break;
                default:
                    $this->drawLogoPlaceholder($image, $x, $y, $size);
                    return;
            }
            
            if (!$logo) {
                $this->drawLogoPlaceholder($image, $x, $y, $size);
                return;
            }
            
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);
            
            // Hitung ukuran proporsional
            $ratio = min($size / $logoWidth, $size / $logoHeight);
            $newW = (int)($logoWidth * $ratio);
            $newH = (int)($logoHeight * $ratio);
            
            // Resize logo
            $resizedLogo = imagecreatetruecolor($newW, $newH);
            imagealphablending($resizedLogo, false);
            imagesavealpha($resizedLogo, true);
            $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
            imagefill($resizedLogo, 0, 0, $transparent);
            
            imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $newW, $newH, $logoWidth, $logoHeight);
            
            // Copy ke image utama dengan alpha blending
            imagealphablending($image, true);
            imagecopy($image, $resizedLogo, $x, $y, 0, 0, $newW, $newH);
            
            imagedestroy($logo);
            imagedestroy($resizedLogo);
            
        } catch (\Exception $e) {
            Log::warning('Failed to add logo: ' . $e->getMessage());
            $this->drawLogoPlaceholder($image, $x, $y, $size);
        }
    }
    
    /**
     * Gambar placeholder jika logo tidak tersedia
     */
    protected function drawLogoPlaceholder($image, int $x, int $y, int $size): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 0, 80, 160);
        
        // Kotak biru dengan border putih
        imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $blue);
        imagerectangle($image, $x, $y, $x + $size, $y + $size, $white);
        
        // Teks M2B
        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        if (file_exists($fontPath) && $size >= 25) {
            $fontSize = max(8, (int)($size * 0.35));
            imagettftext($image, $fontSize, 0, $x + 3, $y + $size - 5, $white, $fontPath, "M2B");
        } else {
            imagestring($image, 1, $x + 2, $y + ($size / 2) - 4, "M2B", $white);
        }
    }
    
    protected function createImageFromFile(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($path);
                if ($img) {
                    imagesavealpha($img, true);
                }
                return $img;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return @imagecreatefromwebp($path);
            default:
                return @imagecreatefromjpeg($path);
        }
    }
    
    protected function fixOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        
        try {
            $exif = @exif_read_data($path);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return $image;
    }
    
    protected function getImageExtension(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['heic', 'heif'])) {
            return 'jpg';
        }
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
    }
    
    public function deletePhoto($photo): bool
    {
        try {
            if ($photo instanceof FieldPhoto) {
                $path = $photo->file_path;
                $thumbnailPath = $photo->thumbnail_path;
            } else {
                $path = $photo;
                $thumbnailPath = null;
            }
            
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting photo files: ' . $e->getMessage());
            return false;
        }
    }
}
