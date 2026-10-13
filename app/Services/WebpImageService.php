<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WebpImageService
{
    public function encode(string $contents): string
    {
        $size = @getimagesizefromstring($contents);
        if (! $size || $size[0] * $size[1] > 30000000) {
            throw ValidationException::withMessages(['image' => 'Gambar tidak valid atau resolusinya melebihi 30 megapiksel.']);
        }
        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'Format gambar tidak dapat dikonversi.']);
        }
        try {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            ob_start();
            try {
                $success = imagewebp($image, null, 88);
                $result = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            if (! $success || ! $result) {
                throw new RuntimeException('Konversi WebP gagal.');
            }

            return $result;
        } finally {
            imagedestroy($image);
        }
    }

    public function store(UploadedFile $file, string $disk = 'public', string $directory = 'products/gallery'): string
    {
        $path = $directory.'/'.Str::uuid().'.webp';
        if (! Storage::disk($disk)->put($path, $this->encode($file->getContent()))) {
            throw new RuntimeException('Gambar gagal disimpan.');
        }

        return $path;
    }

    public function convertStored(string $path, string $disk = 'public'): string
    {
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'avif'])) {
            return $path;
        }
        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new RuntimeException('Gambar tidak ditemukan: '.$path);
        }
        $target = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME).'-'.substr(sha1($path), 0, 8).'.webp';
        if (! $storage->exists($target) && ! $storage->put($target, $this->encode($storage->get($path)))) {
            throw new RuntimeException('Gambar gagal disimpan: '.$target);
        }

        return $target;
    }
}
