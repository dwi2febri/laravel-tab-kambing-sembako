<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CompletedHandovers;
use App\Filament\Widgets\IncomeChart;
use App\Filament\Widgets\PaymentStatusChart;
use App\Filament\Widgets\PendingPayments;
use App\Filament\Widgets\PopularProductsChart;
use App\Filament\Widgets\SavingsStatusChart;
use App\Models\Handover;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSaving;
use Filament\Widgets\ChartWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_dashboard_renders_charts_and_payment_queue_even_without_data(): void
    {
        $this->get('/admin')->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Produk Paling Diminati')
            ->assertSee('Ringkasan Operasional')
            ->assertSee('Tren Pemasukan')
            ->assertSee('Status Pembayaran')
            ->assertSee('Status Tabungan')
            ->assertSee('Antrean Verifikasi Pembayaran');
        $this->assertTrue(Livewire::test(PaymentStatusChart::class)->instance()->isEmpty());
    }

    public function test_income_chart_uses_approval_month_and_excludes_pending_and_old_payments(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        Payment::factory()->create(['status' => 'approved', 'amount' => 1200, 'approved_at' => '2026-10-01 00:00:00']);
        Payment::factory()->create(['status' => 'approved', 'amount' => 500, 'created_at' => '2026-10-02 12:00:00', 'approved_at' => '2026-09-30 23:59:59']);
        Payment::factory()->create(['status' => 'approved', 'amount' => 900, 'approved_at' => '2026-04-30 23:59:59']);
        Payment::factory()->create(['status' => 'pending', 'amount' => 700]);

        $data = $this->chartData(IncomeChart::class);

        $this->assertCount(6, $data['labels']);
        $this->assertSame([0, 0, 0, 0, 500, 1200], $data['datasets'][0]['data']);
    }

    public function test_payment_chart_counts_records_in_the_correct_categories(): void
    {
        Payment::factory()->count(2)->create(['status' => 'approved']);
        Payment::factory()->create(['status' => 'pending']);
        Payment::factory()->create(['status' => 'rejected']);

        $this->assertSame([2, 1, 1], $this->chartData(PaymentStatusChart::class)['datasets'][0]['data']);
    }

    public function test_savings_chart_includes_each_handover_stage(): void
    {
        foreach (['active', 'active', 'paid', 'handover_requested', 'handover_scheduled', 'completed'] as $status) {
            UserSaving::factory()->create(['status' => $status]);
        }

        $this->assertSame([2, 1, 1, 1, 1], $this->chartData(SavingsStatusChart::class)['datasets'][0]['data']);
    }

    public function test_payment_queue_shows_pending_payments_only(): void
    {
        $pending = Payment::factory()->create(['status' => 'pending']);
        $approved = Payment::factory()->create(['status' => 'approved']);

        Livewire::test(PendingPayments::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved])
            ->assertCountTableRecords(1);
    }

    public function test_date_filter_includes_entire_last_day_and_excludes_other_dates(): void
    {
        $inside = Payment::factory()->create(['status' => 'pending', 'created_at' => '2026-10-10 23:59:59']);
        $outside = Payment::factory()->create(['status' => 'pending', 'created_at' => '2026-10-11 00:00:00']);
        Livewire::test(PendingPayments::class, ['pageFilters' => ['from' => '2026-10-10', 'to' => '2026-10-10']])
            ->assertCanSeeTableRecords([$inside])->assertCanNotSeeTableRecords([$outside]);
    }

    public function test_completed_handovers_are_filtered_by_completion_date(): void
    {
        $completed = Handover::factory()->create(['status' => 'completed', 'completed_at' => '2026-10-10 23:59:59']);
        $old = Handover::factory()->create(['status' => 'completed', 'completed_at' => '2026-10-09 23:59:59']);
        $scheduled = Handover::factory()->create(['status' => 'scheduled', 'scheduled_at' => '2026-10-10']);
        Livewire::test(CompletedHandovers::class, ['pageFilters' => ['from' => '2026-10-10', 'to' => '2026-10-10']])
            ->assertCanSeeTableRecords([$completed])->assertCanNotSeeTableRecords([$old, $scheduled])->assertCountTableRecords(1);
    }

    public function test_income_filter_uses_approval_date(): void
    {
        Payment::factory()->create(['status' => 'approved', 'amount' => 1000, 'approved_at' => '2026-10-10 23:59:59']);
        Payment::factory()->create(['status' => 'approved', 'amount' => 500, 'approved_at' => '2026-10-09 23:59:59']);
        $widget = Livewire::test(IncomeChart::class, ['pageFilters' => ['from' => '2026-10-10', 'to' => '2026-10-10']])->instance();
        $data = (new ReflectionMethod(IncomeChart::class, 'getData'))->invoke($widget);
        $this->assertSame([1000], $data['datasets'][0]['data']);
    }

    public function test_dashboard_filter_validates_date_order_and_can_reset(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSee('Dari tanggal')
            ->set('dateFrom', '2026-10-11')->set('dateTo', '2026-10-10')
            ->call('applyDates')->assertHasErrors(['dateTo'])
            ->set('dateFrom', '2026-10-01')
            ->call('applyDates')->assertHasNoErrors()
            ->assertSet('filters.from', '2026-10-01')
            ->call('resetDates')->assertSet('filters', [])
            ->assertSet('dateFrom', null)->assertSet('dateTo', null);
    }

    public function test_popular_products_counts_only_savings_in_selected_period(): void
    {
        $saving = UserSaving::factory()->create(['product_name_snapshot' => 'Kambing pilihan', 'created_at' => '2026-10-10 12:00:00']);
        UserSaving::factory()->create(['product_id' => $saving->product_id, 'product_name_snapshot' => 'Kambing pilihan', 'created_at' => '2026-10-10 23:59:59']);
        UserSaving::factory()->create(['product_name_snapshot' => 'Di luar periode', 'created_at' => '2026-10-09 12:00:00']);
        $widget = Livewire::test(PopularProductsChart::class, ['pageFilters' => ['from' => '2026-10-10', 'to' => '2026-10-10']])->instance();
        $data = (new ReflectionMethod(PopularProductsChart::class, 'getData'))->invoke($widget);
        $this->assertSame(['Kambing pilihan'], $data['labels']);
        $this->assertSame([2], $data['datasets'][0]['data']);
    }

    /**
     * @param  class-string<ChartWidget>  $widgetClass
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    private function chartData(string $widgetClass): array
    {
        $widget = Livewire::test($widgetClass)->instance();

        return (new ReflectionMethod($widgetClass, 'getData'))->invoke($widget);
    }
}
