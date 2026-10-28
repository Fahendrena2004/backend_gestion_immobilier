<?php

namespace App\Shared\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Mampiakatra sary na rakitra ao amin'ny public disk
     */
    public function upload(UploadedFile $file, string $folder = 'photos'): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');
        return $path;
    }

    /**
     * Mamafa rakitra ao amin'ny storage
     */
    public function delete(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Uploade une image, la redimensionne (max 1600px, JPEG 82) et génère une miniature (max 600px, JPEG 78).
     * En cas d'échec ou format non pris en charge, retombe silencieusement sur upload() standard.
     */
    public function uploadImage(UploadedFile $file, string $folder = 'photos'): string
    {
        if (!extension_loaded('gd')) {
            return $this->upload($file, $folder);
        }

        try {
            $realPath = $file->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                return $this->upload($file, $folder);
            }

            $image = $this->loadAndNormalizeImage($realPath);
            if (!$image) {
                return $this->upload($file, $folder);
            }

            // Redimensionnement de l'image principale (max 1600px, sans agrandir)
            $mainImage = $this->resizeGdImage($image, 1600);
            $mainJpeg = $this->encodeToJpeg($mainImage, 82);
            imagedestroy($mainImage);

            // Génération de la miniature (max 600px, sans agrandir)
            $thumbImage = $this->resizeGdImage($image, 600);
            $thumbJpeg = $this->encodeToJpeg($thumbImage, 78);
            imagedestroy($thumbImage);

            imagedestroy($image);

            $filename = Str::uuid() . '.jpg';
            $mainPath = trim($folder, '/') . '/' . $filename;
            $thumbPath = self::thumbnailPath($mainPath);

            Storage::disk('public')->put($mainPath, $mainJpeg);
            Storage::disk('public')->put($thumbPath, $thumbJpeg);

            return $mainPath;
        } catch (\Throwable) {
            return $this->upload($file, $folder);
        }
    }

    /**
     * Supprime l'image principale et sa miniature éventuelle ({dirname}/thumbs/{basename}).
     */
    public function deleteImage(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        $deleted = false;
        if (Storage::disk('public')->exists($path)) {
            $deleted = Storage::disk('public')->delete($path);
        }

        $thumb = self::thumbnailPath($path);
        if (Storage::disk('public')->exists($thumb)) {
            Storage::disk('public')->delete($thumb);
        }

        return $deleted;
    }

    /**
     * Retourne le chemin relatif de la miniature ({dirname}/thumbs/{basename}) sans accès disque.
     */
    public static function thumbnailPath(string $path): string
    {
        $dir = dirname($path);
        $base = basename($path);

        if ($dir === '.' || $dir === '') {
            return 'thumbs/' . $base;
        }

        return str_replace('\\', '/', $dir) . '/thumbs/' . $base;
    }

    /**
     * Génère une miniature (max 600px, JPEG 78) pour une image existante sur le disque public.
     */
    public function createThumbnail(string $sourcePath, ?string $thumbPath = null, int $maxDimension = 600, int $quality = 78): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($sourcePath)) {
            return false;
        }

        $thumbPath = $thumbPath ?: self::thumbnailPath($sourcePath);

        try {
            $realPath = $disk->path($sourcePath);
            $tempFile = null;

            if (!file_exists($realPath)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'thumb_');
                file_put_contents($tempFile, $disk->get($sourcePath));
                $filePathToLoad = $tempFile;
            } else {
                $filePathToLoad = $realPath;
            }

            $image = $this->loadAndNormalizeImage($filePathToLoad);

            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }

            if (!$image) {
                return false;
            }

            $thumb = $this->resizeGdImage($image, $maxDimension);
            imagedestroy($image);

            $jpegData = $this->encodeToJpeg($thumb, $quality);
            imagedestroy($thumb);

            return $disk->put($thumbPath, $jpegData);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Redimensionne une GdImage proportionnellement pour que le plus grand côté soit <= $maxDimension.
     * N'agrandit jamais l'image si elle est plus petite.
     */
    public function resizeGdImage(GdImage $image, int $maxDimension): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= $maxDimension && $h <= $maxDimension) {
            $copy = imagecreatetruecolor($w, $h);
            imagecopy($copy, $image, 0, 0, 0, 0, $w, $h);
            return $copy;
        }

        if ($w >= $h) {
            $dstW = $maxDimension;
            $dstH = (int) round(($h * $maxDimension) / $w);
        } else {
            $dstH = $maxDimension;
            $dstW = (int) round(($w * $maxDimension) / $h);
        }

        $dstW = max(1, $dstW);
        $dstH = max(1, $dstH);

        $resized = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $dstW, $dstH, $w, $h);

        return $resized;
    }

    /**
     * Encode une image GD en JPEG vers une chaîne binaire.
     */
    public function encodeToJpeg(GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);
        return (string) ob_get_clean();
    }

    /**
     * Charge une image avec GD, vérifie les limites mémoire et types autorisés,
     * corrige l'orientation EXIF pour JPEG et aplatit la transparence sur fond blanc pour PNG/WebP.
     */
    public function loadAndNormalizeImage(string $filePath): ?GdImage
    {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return null;
        }

        $info = @getimagesize($filePath);
        if (!$info) {
            return null;
        }

        [$width, $height, $type] = [$info[0], $info[1], $info[2]];

        // Protection mémoire : largeur x hauteur > 30 000 000 pixels
        if (($width * $height) > 30000000) {
            return null;
        }

        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($filePath),
            default        => null,
        };

        if (!$image) {
            return null;
        }

        // Pour JPEG : corriger l'orientation EXIF si disponible
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            if (!empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
                $rotated = match ($orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => null,
                };
                if ($rotated) {
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
        }

        // Pour PNG et WebP : aplatir la transparence sur fond blanc
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $w = imagesx($image);
            $h = imagesy($image);
            $flattened = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($flattened, 255, 255, 255);
            imagefilledrectangle($flattened, 0, 0, $w, $h, $white);
            imagecopy($flattened, $image, 0, 0, 0, 0, $w, $h);
            imagedestroy($image);
            $image = $flattened;
        }

        return $image;
    }
}
