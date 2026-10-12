<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentUploadRequest;
use App\Http\Requests\StartSavingRequest;
use App\Models\Installment;
use App\Models\MemberNotification;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SavingPlan;
use App\Models\UserSaving;
use App\Services\HandoverService;
use App\Services\PaymentService;
use App\Services\SavingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalController extends Controller
{
    public function home(): View
    {
        return view('home', ['products' => Product::with(['category', 'plans'])->where('is_active', true)->where('is_featured', true)->limit(4)->get()]);
    }

    public function catalog(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|in:goat,grocery', 'sort' => 'nullable|in:popular,low,high']);
        $products = Product::with(['category', 'plans'])->where('is_active', true)->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))->when($request->filled('type'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('type', $request->query('type'))));
        match ($request->query('sort')) {
            'low' => $products->orderBy('price'), 'high' => $products->orderByDesc('price'), default => $products->orderByDesc('is_featured')->orderBy('id')
        };

        return view('catalog', ['products' => $products->paginate(9)->withQueryString()]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load(['category', 'plans' => fn ($q) => $q->where('is_active', true), 'items', 'images']);

        return view('product', compact('product'));
    }

    public function dashboard(Request $request): View
    {
        $savings = $request->user()->savings()->with('product.category')->latest()->get();

        return view('dashboard', ['savings' => $savings, 'products' => Product::with(['category', 'plans'])->where('is_active', true)->where('is_featured', true)->limit(3)->get(), 'paymentCount' => Payment::where('user_id', $request->user()->id)->count(), 'next' => Installment::with('userSaving')->whereHas('userSaving', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', 'active'))->whereIn('status', ['unpaid', 'late'])->orderBy('due_date')->first()]);
    }

    public function savings(Request $request): View
    {
        return view('savings', ['savings' => $request->user()->savings()->with(['product.category', 'handover'])->latest()->paginate(9)]);
    }

    public function saving(UserSaving $saving): View
    {
        Gate::authorize('view', $saving);
        $saving->load(['product.category', 'installments', 'handover']);

        return view('saving', compact('saving'));
    }

    public function start(StartSavingRequest $request, Product $product, SavingService $service): RedirectResponse
    {
        $saving = $service->start($request->user(), $product, SavingPlan::findOrFail($request->integer('saving_plan_id')));

        return redirect()->route('savings.show', $saving)->with('success', 'Tabungan berhasil dibuat. Yuk, mulai cicilan pertama!');
    }

    public function pay(Installment $installment): View
    {
        Gate::authorize('view', $installment->userSaving);
        abort_unless(in_array($installment->status, ['unpaid', 'late']), 422);

        return view('pay', ['installment' => $installment, 'methods' => PaymentMethod::where('is_active', true)->get()]);
    }

    public function upload(PaymentUploadRequest $request, Installment $installment, PaymentService $service): RedirectResponse
    {
        $service->submit($request->user(), $installment, $request->integer('payment_method_id'), $request->file('proof'), $request->input('note'));

        return redirect()->route('savings.show', $installment->userSaving)->with('success', 'Bukti berhasil dikirim. Menunggu verifikasi admin.');
    }

    public function history(Request $request): View
    {
        return view('history', ['payments' => Payment::with(['userSaving', 'installment', 'method'])->where('user_id', $request->user()->id)->latest()->paginate(12)]);
    }

    public function proof(Payment $payment): BinaryFileResponse
    {
        Gate::authorize('view', $payment);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function notifications(Request $request): View
    {
        return view('notifications', ['notifications' => $request->user()->memberNotifications()->latest()->paginate(15)]);
    }

    public function read(Request $request, MemberNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function profile(): View
    {
        return view('profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'phone' => 'required|string|max:25', 'address' => 'nullable|string|max:1000', 'current_password' => 'required_with:password|nullable|current_password', 'password' => ['nullable', 'confirmed', Password::min(8)]]);
        $request->user()->update(collect($data)->except('current_password')->filter(fn ($v, $k) => $k !== 'password' || filled($v))->all());

        return back()->with('success', 'Profil berhasil disimpan.');
    }

    public function handover(Request $request, UserSaving $saving, HandoverService $service): RedirectResponse
    {
        $data = $request->validate(['recipient_name' => 'required|string|max:100', 'note' => 'nullable|string|max:1000']);
        $service->request($request->user(), $saving, $data['recipient_name'], $data['note'] ?? null);

        return back()->with('success','Pengajuan serah terima dikirim. Admin akan mengatur jadwal.');
    }

    public function terms(): View
    {
        return view('terms');
    }
}
