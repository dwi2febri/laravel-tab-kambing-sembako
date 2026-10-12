<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use UnitEnum;

class Reports extends Page
{
    use WithPagination;

    public string $type = 'payments';

    public string $from = '';

    public string $to = '';

    public string $status = '';

    public string $userId = '';

    public string $productId = '';

    #[Locked]
    public array $appliedFilters = ['type' => 'payments'];

    public function statusOptions(): array
    {
        return match ($this->type) {
            'products', 'users' => ['active' => 'Aktif', 'inactive' => 'Tidak aktif'],
            'savings' => ['active' => 'Belum lunas', 'paid' => 'Lunas', 'handover_requested' => 'Pengajuan serah terima', 'handover_scheduled' => 'Serah terima dijadwalkan', 'completed' => 'Selesai'],
            'handovers' => ['requested' => 'Diajukan', 'scheduled' => 'Dijadwalkan', 'completed' => 'Selesai'],
            default => ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'],
        };
    }

    public function updated(string $property): void
    {
        if (! in_array($property, ['type', 'from', 'to', 'status', 'userId', 'productId'])) {
            return;
        }
        if ($property === 'type') {
            $this->status = '';
            $this->userId = '';
            $this->productId = '';
        }
        $this->applyFilters();
    }

    public function applyFilters(): void
    {
        Gate::authorize('admin');
        $this->resetValidation();
        $this->validate([
            'type' => ['required', Rule::in(['payments', 'savings', 'products', 'handovers', 'users'])],
            'from' => ['nullable', 'date'],
            'to' => array_filter(['nullable', 'date', $this->from !== '' ? 'after_or_equal:from' : null]),
            'status' => ['nullable', Rule::in(array_keys($this->statusOptions()))],
            'userId' => ['nullable', 'integer', 'exists:users,id'],
            'productId' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $this->appliedFilters = array_filter([
            'type' => $this->type, 'from' => $this->from, 'to' => $this->to, 'status' => $this->status,
            'user_id' => $this->type !== 'products' ? $this->userId : '',
            'product_id' => $this->type !== 'users' ? $this->productId : '',
        ], fn (string $value): bool => $value !== '');
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('type', 'from', 'to', 'status', 'userId', 'productId');
        $this->applyFilters();
    }

    protected function getViewData(): array
    {
        Gate::authorize('admin');
        $report = app(ReportService::class)->data(Request::create('/', 'GET', $this->appliedFilters));
        $rows = new LengthAwarePaginator(array_slice($report['rows'], ($this->getPage() - 1) * 25, 25), count($report['rows']), 25, $this->getPage());

        return [
            'report' => $report, 'rows' => $rows,
            'members' => User::where('role', 'user')->orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ];
    }

    protected string $view = 'filament.pages.reports';

    protected static ?string $navigationLabel = 'Laporan & Ekspor';

    protected static ?string $title = 'Laporan & Ekspor';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function canAccess(): bool
    {
        return Gate::allows('admin');
    }
}
