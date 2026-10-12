<?php

namespace App\Services;

use App\Models\Handover;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Http\Request;

class ReportService
{
    public function data(Request $request): array
    {
        $f = $request->validate(['type' => 'nullable|in:payments,savings,products,handovers,users', 'status' => 'nullable|string|max:40', 'from' => 'nullable|date', 'to' => ['nullable', 'date', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'user_id' => 'nullable|integer|exists:users,id', 'product_id' => 'nullable|integer|exists:products,id']);
        $type = $f['type'] ?? 'payments';
        [$query,$headers,$title,$mapper] = match ($type) {
            'savings' => [UserSaving::with('user'), ['NO', 'Anggota', 'Produk', 'Tenor', 'Target', 'Disetujui', 'Sisa', 'Status'], 'Laporan Tabungan', fn ($r) => [$r->id, $r->user->name, $r->product_name_snapshot, $r->tenor_snapshot, $r->product_price_snapshot, $r->total_paid, $r->remaining_amount, $r->status]],
            'products' => [Product::with('category')->withCount('savings'), ['NO', 'Produk', 'Kategori', 'Harga', 'Stok', 'Program', 'Aktif'], 'Laporan Produk', fn ($r) => [$r->id, $r->name, $r->category->name, $r->price, $r->stock, $r->savings_count, $r->is_active ? 'Ya' : 'Tidak']],
            'handovers' => [Handover::with('userSaving.user'), ['NO', 'Anggota', 'Produk', 'Penerima', 'Status', 'Jadwal', 'Lokasi'], 'Laporan Serah Terima', fn ($r) => [$r->id, $r->userSaving->user->name, $r->userSaving->product_name_snapshot, $r->recipient_name, $r->status, $r->scheduled_at?->format('d-m-Y H:i') ?? '-', $r->location ?? '-']],
            'users' => [User::query()->where('role', 'user'), ['NO', 'Anggota', 'Email', 'Telepon', 'Aktif', 'Terdaftar'], 'Laporan Anggota', fn ($r) => [$r->id, $r->name, $r->email, $r->phone ?? '-', $r->is_active ? 'Ya' : 'Tidak', $r->created_at->format('d-m-Y')]],
            default => [Payment::with(['user', 'userSaving', 'installment']), ['NO', 'Tanggal', 'Anggota', 'Produk', 'Cicilan', 'Nominal', 'Status'], 'Laporan Pembayaran', fn ($r) => [$r->id, $r->created_at->format('d-m-Y'), $r->user->name, $r->userSaving->product_name_snapshot, $r->installment->installment_number, $r->amount, $r->status]]
        };
        if (! empty($f['status'])) {
            if (in_array($type, ['products', 'users'])) {
                $query->where('is_active', $f['status'] === 'active');
            } else {
                $query->where('status', $f['status']);
            }
        }
        if (! empty($f['from'])) {
            $query->whereDate('created_at', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $query->whereDate('created_at', '<=', $f['to']);
        }
        if (! empty($f['user_id'])) {
            match ($type) {
                'payments','savings' => $query->where('user_id', $f['user_id']),'handovers' => $query->whereHas('userSaving', fn ($q) => $q->where('user_id', $f['user_id'])),'users' => $query->where('id', $f['user_id']),default => null
            };
        }
        if (! empty($f['product_id'])) {
            match ($type) {
                'savings' => $query->where('product_id', $f['product_id']),'payments','handovers' => $query->whereHas('userSaving', fn ($q) => $q->where('product_id', $f['product_id'])),'products' => $query->where('id', $f['product_id']),default => null
            };
        }
        $rows = $query->orderByDesc('id')->get()->map($mapper)->map(function (array $row, int $index): array {
            $row[0] = $index + 1;

            return $row;
        })->all();

        return compact('headers', 'title', 'rows', 'type');
    }
}
