<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PrepareAssets extends Command
{
    protected $signature = 'app:prepare-assets';

    protected $description = 'Compress website illustrations and prepare PWA icons';

    public function handle(): int
    {
        foreach (['hero', 'goat', 'grocery'] as $name) {
            $source = imagecreatefrompng(public_path('images/'.$name.'.png'));
            $w = imagesx($source);
            $h = imagesy($source);
            $max = $name === 'hero' ? 1440 : 760;
            $nw = min($w, $max);
            $nh = (int) round($h * $nw / $w);
            $output = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($output, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagewebp($output, public_path('images/'.$name.'.webp'), 85);
            imagedestroy($source);
            imagedestroy($output);
        }
        foreach ([192, 512] as $size) {
            $im = imagecreatetruecolor($size, $size);
            $green = imagecolorallocate($im, 8, 127, 82);
            $cream = imagecolorallocate($im, 231, 244, 222);
            $gold = imagecolorallocate($im, 240, 199, 115);
            imagefill($im, 0, 0, $green);
            imagefilledellipse($im, (int) ($size * .5), (int) ($size * .55), (int) ($size * .48), (int) ($size * .42), $cream);
            imagefilledpolygon($im, [(int) ($size * .33), (int) ($size * .36), (int) ($size * .27), (int) ($size * .23), (int) ($size * .46), (int) ($size * .33)], $gold);
            imagefilledpolygon($im, [(int) ($size * .67), (int) ($size * .36), (int) ($size * .73), (int) ($size * .23), (int) ($size * .54), (int) ($size * .33)], $gold);
            imagefilledellipse($im, (int) ($size * .39), (int) ($size * .52), (int) ($size * .04), (int) ($size * .04), $green);
            imagefilledellipse($im, (int) ($size * .61), (int) ($size * .52), (int) ($size * .04), (int) ($size * .04), $green);
            imagewebp($im, public_path('images/pwa-'.$size.'.webp'), 88);
            imagedestroy($im);
        }
        $this->info('Assets WebP dan ikon PWA siap.');

        return self::SUCCESS;
    }
}
