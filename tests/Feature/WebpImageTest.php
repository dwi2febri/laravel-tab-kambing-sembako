<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\WebpImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WebpImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploads_are_real_webp_images_with_original_dimensions(): void
    {
        Storage::fake('public');
        foreach (['photo.jpg', 'photo.png', 'photo.webp'] as $name) {
            $path = app(WebpImageService::class)->store(UploadedFile::fake()->image($name, 120, 80));
            $this->assertStringEndsWith('.webp', $path);
            $size = getimagesizefromstring(Storage::disk('public')->get($path));
            $this->assertSame('image/webp', $size['mime']);
            $this->assertSame([120, 80], [$size[0], $size[1]]);
        }
    }

    public function test_transparency_is_preserved(): void
    {
        $image = imagecreatetruecolor(10, 10);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        $webp = imagecreatefromstring(app(WebpImageService::class)->encode($png));
        $this->assertSame(127, imagecolorsforindex($webp, imagecolorat($webp, 0, 0))['alpha']);
        imagedestroy($webp);
    }

    public function test_invalid_images_are_rejected_without_saving_a_file(): void
    {
        Storage::fake('public');
        try {
            app(WebpImageService::class)->store(UploadedFile::fake()->createWithContent('fake.png', 'not an image'));
            $this->fail('Invalid image accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_command_updates_existing_product_and_gallery_references_idempotently(): void
    {
        Storage::fake('public');
        $path = 'products/old.png';
        Storage::disk('public')->put($path, UploadedFile::fake()->image('old.png')->getContent());
        $product = Product::factory()->create(['thumbnail' => $path]);
        $gallery = $product->images()->create(['path' => $path, 'sort_order' => 0]);
        $this->artisan('app:convert-images-to-webp')->assertSuccessful();
        $converted = $product->fresh()->thumbnail;
        $this->assertStringEndsWith('.webp', $converted);
        $this->assertSame($converted, $gallery->fresh()->path);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($converted));
        Storage::disk('public')->assertExists($path);
        $this->artisan('app:convert-images-to-webp')->assertSuccessful();
        $this->assertSame($converted, $product->fresh()->thumbnail);
    }
}
