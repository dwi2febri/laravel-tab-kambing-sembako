<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_update_inline_table_and_export_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $active = Product::factory()->create(['name' => 'Produk Aktif Uji', 'is_active' => true]);
        Product::factory()->create(['name' => 'Produk Nonaktif Uji', 'is_active' => false]);
        Livewire::test(Reports::class)->set('type', 'products')->assertHasNoErrors()
            ->assertViewHas('report', fn (array $report): bool => count($report['rows']) === 2)
            ->set('status', 'active')->assertHasNoErrors()
            ->assertViewHas('report', fn (array $report): bool => count($report['rows']) === 1 && $report['rows'][0][1] === $active->name)
            ->assertSee(route('reports.export', ['format' => 'xlsx', 'type' => 'products', 'status' => 'active']))
            ->set('type', 'users')->assertSet('status', '')->assertSet('productId', '')
            ->assertSee('Laporan Anggota')->call('resetFilters')->assertSet('type', 'payments');
    }

    public function test_invalid_date_range_preserves_last_valid_preview(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Reports::class)->set('from', '2026-10-10')->set('to', '2026-10-01')
            ->assertHasErrors(['to'])->assertSet('appliedFilters', ['type' => 'payments', 'from' => '2026-10-10']);
    }

    public function test_end_date_only_is_supported_by_preview_and_export(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Reports::class)->set('to', '2026-10-10')->assertHasNoErrors()
            ->assertSet('appliedFilters', ['type' => 'payments', 'to' => '2026-10-10']);
        $this->get(route('reports.preview', ['to' => '2026-10-10']))->assertOk();
    }

    public function test_members_cannot_open_report_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/reports')->assertForbidden();
    }
}
