<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\WebpImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertImagesToWebp extends Command
{
    protected $signature = 'app:convert-images-to-webp';

    protected $description = 'Convert raster assets and stored images to WebP, preserving original files';

    public function handle(WebpImageService $images): int
    {
        foreach (File::allFiles(public_path('images')) as $file) {
            if (! in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'bmp'])) {
                continue;
            }
            $target = $file->getPath().'/'.$file->getBasename('.'.$file->getExtension()).'.webp';
            if (! File::exists($target)) {
                File::put($target, $images->encode(File::get($file->getPathname())));
            }
        }
        foreach ([[Product::class, 'thumbnail', 'public'], [ProductImage::class, 'path', 'public'], [Payment::class, 'proof_path', 'local']] as [$model, $column, $disk]) {
            foreach ($model::whereNotNull($column)->lazyById() as $record) {
                $path = $images->convertStored($record->{$column}, $disk);
                if ($path !== $record->{$column}) {
                    $record->updateQuietly([$column => $path]);
                }
            }
        }
        $this->info('Aset dan referensi gambar telah dikonversi ke WebP. File sumber tetap disimpan.');

        return self::SUCCESS;
    }
}
