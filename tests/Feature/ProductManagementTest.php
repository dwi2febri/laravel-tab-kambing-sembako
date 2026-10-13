<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductImages\ProductImageResource;
use App\Filament\Resources\ProductItems\ProductItemResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\SavingPlans\SavingPlanResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Storage::fake('public');
    }

    public function test_product_forms_are_pages_and_related_menus_are_hidden(): void
    {
        $product = Product::factory()->create();
        $this->get('/admin/products')->assertOk()->assertSee('/admin/products/create')
            ->assertSee('/admin/products/'.$product->id.'/edit')
            ->assertDontSee('href="http://localhost/admin/product-images"', false);
        $this->get('/admin/products/create')->assertOk()->assertSee('Foto produk')->assertSee('Tenor tabungan');
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
        $this->assertFalse(ProductImageResource::shouldRegisterNavigation());
        $this->assertFalse(ProductItemResource::shouldRegisterNavigation());
        $this->assertFalse(SavingPlanResource::shouldRegisterNavigation());
    }

    public function test_grocery_product_saves_multiple_photos_items_and_tenors(): void
    {
        $category = Category::factory()->create(['type' => 'grocery', 'name' => 'Sembako']);
        Livewire::test(CreateProduct::class)->fillForm([
            'category_id' => $category->id,
            'name' => 'Paket keluarga', 'slug' => 'paket-keluarga',
            'description' => 'Paket kebutuhan keluarga', 'price' => 300000, 'stock' => 10,
            'gallery_images' => [UploadedFile::fake()->image('utama.jpg'), UploadedFile::fake()->image('foto-1.jpg'), UploadedFile::fake()->image('foto-2.jpg')],
            'cover_photo' => 1,
            'items' => [['item_name' => 'Beras', 'qty' => 5, 'unit' => 'kg']],
            'plans' => [['name' => '6 bulan', 'months' => 6, 'monthly_amount' => null, 'down_payment' => 0, 'admin_fee' => 0, 'is_active' => true]],
        ])->call('create')->assertHasNoFormErrors();

        $product = Product::where('slug', 'paket-keluarga')->firstOrFail();
        $this->assertCount(2, $product->images);
        Storage::disk('public')->assertExists($product->thumbnail);
        $this->assertStringEndsWith('.webp', $product->thumbnail);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($product->thumbnail));
        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertSame('image/webp', Storage::disk('public')->mimeType($image->path));
        }
        $this->assertSame([0, 1], $product->images->pluck('sort_order')->all());
        $this->assertSame('Beras', $product->items->sole()->item_name);
        $this->assertSame(50000, $product->plans->sole()->monthlyAmount());
    }

    public function test_edit_preserves_existing_photos_items_and_used_tenor(): void
    {
        $category = Category::factory()->create(['type' => 'grocery']);
        $product = Product::factory()->create(['category_id' => $category->id]);
        Storage::disk('public')->put('products/gallery/old.jpg', UploadedFile::fake()->image('old.jpg')->getContent());
        Storage::disk('public')->put('products/cover.jpg', UploadedFile::fake()->image('cover.jpg')->getContent());
        $product->update(['thumbnail' => 'products/cover.jpg']);
        $image = $product->images()->create(['path' => 'products/gallery/old.jpg', 'sort_order' => 0]);
        $item = $product->items()->create(['item_name' => 'Beras', 'qty' => 5, 'unit' => 'kg']);
        $saving = UserSaving::factory()->create(['product_id' => $product->id]);
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['name' => 'Paket diperbarui'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Paket diperbarui', $product->fresh()->name);
        $this->assertDatabaseHas('product_images', ['id' => $image->id, 'path' => $image->path]);
        $this->assertDatabaseHas('product_items', ['id' => $item->id]);
        $this->assertDatabaseHas('saving_plans', ['id' => $saving->saving_plan_id]);
    }

    public function test_edit_updates_gallery_order_package_and_disables_used_plan(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create(['type' => 'grocery'])->id]);
        foreach (['first.jpg', 'second.jpg', 'third.jpg'] as $order => $filename) {
            Storage::disk('public')->put('products/gallery/'.$filename, UploadedFile::fake()->image($filename)->getContent());
            $product->images()->create(['path' => 'products/gallery/'.$filename, 'sort_order' => $order]);
        }
        $item = $product->items()->create(['item_name' => 'Beras', 'qty' => 5, 'unit' => 'kg']);
        $saving = UserSaving::factory()->create(['product_id' => $product->id]);
        $page = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
        $page->set('data.gallery_images', ['products/gallery/third.jpg', 'products/gallery/first.jpg'])
            ->set('data.items.record-'.$item->id.'.qty', 10)
            ->set('data.plans.record-'.$saving->saving_plan_id.'.is_active', false)
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('products/gallery/third.jpg', $product->fresh()->thumbnail);
        $this->assertSame(['products/gallery/first.jpg'], $product->images()->pluck('path')->all());
        $this->assertSame(10.0, (float) $item->fresh()->qty);
        $this->assertDatabaseHas('saving_plans', ['id' => $saving->saving_plan_id, 'is_active' => false]);
        $this->assertDatabaseHas('user_savings', ['id' => $saving->id, 'saving_plan_id' => $saving->saving_plan_id]);
    }

    public function test_package_fields_follow_selected_category_and_validate_tenor(): void
    {
        $goat = Category::factory()->create();
        $grocery = Category::factory()->create(['type' => 'grocery']);
        Livewire::test(CreateProduct::class)->fillForm(['category_id' => $goat->id])
            ->assertFormFieldIsHidden('items')->assertFormFieldIsVisible('breed')
            ->fillForm(['category_id' => $grocery->id])
            ->assertFormFieldIsVisible('items')->assertFormFieldIsHidden('breed')
            ->fillForm([
                'name' => 'Paket', 'slug' => 'paket', 'description' => 'Paket', 'price' => 1000, 'stock' => 1,
                'plans' => ['invalid' => ['name' => 'Tidak valid', 'months' => 0, 'down_payment' => 0, 'admin_fee' => 0]],
            ])->call('create')->assertHasFormErrors(['plans.invalid.months' => 'min']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_rejects_more_than_five_photos(): void
    {
        Livewire::test(CreateProduct::class)->fillForm([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Kambing', 'slug' => 'kambing', 'description' => 'Kambing sehat', 'price' => 1000000, 'stock' => 1,
            'gallery_images' => array_map(fn (int $index) => UploadedFile::fake()->image('foto-'.$index.'.jpg'), range(1, 6)),
        ])->call('create')->assertHasFormErrors(['gallery_images']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_cover_can_be_changed_and_all_photos_can_be_removed(): void
    {
        $product = Product::factory()->create(['thumbnail' => 'products/cover.jpg']);
        foreach (['cover', 'second'] as $name) {
            Storage::disk('public')->put('products/'.$name.'.jpg', UploadedFile::fake()->image($name.'.jpg')->getContent());
        }
        $product->images()->create(['path' => 'products/second.jpg', 'sort_order' => 0]);
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['cover_photo' => 1])->call('save')->assertHasNoFormErrors();
        $this->assertSame('products/second.jpg', $product->fresh()->thumbnail);
        $this->assertSame(['products/cover.jpg'], $product->images()->pluck('path')->all());
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.gallery_images', [])->call('save')->assertHasNoFormErrors();
        $this->assertNull($product->fresh()->thumbnail);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_slug_is_generated_and_rupiah_separators_are_removed(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['slug' => 'kambing-pilihan']);
        Livewire::test(CreateProduct::class)->fillForm([
            'category_id' => $category->id, 'name' => 'Kambing Pilihan', 'slug' => 'manipulasi',
            'description' => 'Sehat', 'price' => '2.500.000', 'stock' => 1,
            'plans' => [['name' => '12 bulan', 'months' => 12, 'monthly_amount' => '200.000', 'down_payment' => '100.000', 'admin_fee' => '15.000', 'is_active' => true]],
        ])->assertFormFieldIsDisabled('slug')->call('create')->assertHasNoFormErrors();
        $product = Product::where('slug', 'kambing-pilihan-2')->firstOrFail();
        $this->assertEquals(2500000, $product->price);
        $this->assertNull($product->plans->sole()->monthly_amount);
        $this->assertSame(209584, $product->plans->sole()->monthlyAmount());
        $this->assertEquals(0, $product->plans->sole()->down_payment);
        $this->assertEquals(15000, $product->plans->sole()->admin_fee);
        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->fillForm(['name' => 'Kambing Baru', 'slug' => 'manual', 'price' => '3.000.000'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('kambing-baru', $product->fresh()->slug);
        $this->assertEquals(3000000, $product->fresh()->price);
    }

    public function test_rupiah_mask_is_rendered_as_a_complete_html_attribute(): void
    {
        $response = $this->get('/admin/products/create')->assertOk();
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $price = (new \DOMXPath($document))->query("//input[@placeholder='250.000']")->item(0);
        $this->assertNotNull($price);
        $this->assertSame("\$money(\$input, ',', '.', 0)", $price->getAttribute('x-mask:dynamic'));
        $this->assertSame('text', $price->getAttribute('type'));
    }

    public function test_tenor_editor_adds_edits_and_validates_rows(): void
    {
        $page = Livewire::test(CreateProduct::class)->fillForm([
            'category_id' => Category::factory()->create()->id, 'name' => 'Produk tenor',
            'description' => 'Produk', 'price' => 1000000, 'stock' => 1,
        ])->set('tenorDraft.months', 0)->call('saveTenor')->assertHasErrors(['tenorDraft.months'])
            ->set('tenorDraft.months', 3)->set('tenorDraft.admin_fee', '20.000')
            ->call('saveTenor')->assertHasNoErrors();
        $key = array_key_first($page->get('data.plans'));
        $page->call('editTenor', $key)->set('tenorDraft.months', 6)->call('saveTenor')
            ->call('create')->assertHasNoFormErrors();
        $plan = Product::where('slug', 'produk-tenor')->firstOrFail()->plans->sole();
        $this->assertEquals(6, $plan->months);
        $this->assertEquals(20000, $plan->admin_fee);
        $this->assertEquals(0, $plan->down_payment);
        $this->assertTrue((bool) $plan->financed_admin_fee);
        $this->assertSame(170000, $plan->monthlyAmount());
    }

    public function test_members_cannot_access_product_input_pages(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->get('/admin/products/create')->assertForbidden();
        $this->get('/admin/products/'.$product->id.'/edit')->assertForbidden();
    }
}
