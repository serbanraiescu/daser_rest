<?php

namespace App\Modules\Service\Services;

use App\Modules\Service\Models\ServiceCustomer;
use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServiceBillingService
{
    /** Record an invoice issued outside this application, without recording a payment. */
    public function recordInvoice(array $orderIds, array $data, ?int $userId = null): ServiceInvoice
    {
        abort_unless(CompanySetting::first()?->enable_service_module, 404);

        $data['number'] = trim((string) ($data['number'] ?? ''));
        $validated = Validator::make(array_merge($data, ['order_ids' => $orderIds]), [
            'order_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'order_ids.*' => ['required', 'integer', 'distinct'],
            'number' => ['required', 'string', 'max:100', 'unique:service_invoices,number'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'issued_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now(config('service_reporting.timezone', 'Europe/Bucharest'))->toDateString()],
        ])->validate();

        try {
            return DB::transaction(function () use ($validated, $userId) {
                $orders = ServiceOrder::whereIn('id', $validated['order_ids'])
                    ->orderBy('id')->lockForUpdate()->get();

                if ($orders->count() !== count($validated['order_ids'])
                    || $orders->contains(fn ($order) => $order->status !== 'completed'
                        || $order->payment_method !== 'client_account'
                        || $order->service_invoice_id !== null
                        || $order->service_customer_id === null)) {
                    throw ValidationException::withMessages(['order_ids' => 'Selectați numai fișe finalizate pe cont client, încă nefacturate. Lista poate fi modificată de alt administrator; reîncărcați pagina.']);
                }

                if ($orders->pluck('service_customer_id')->unique()->count() !== 1) {
                    throw ValidationException::withMessages(['order_ids' => 'O factură poate include numai lucrările aceluiași client.']);
                }

                $customer = ServiceCustomer::findOrFail($orders->first()->service_customer_id);
                $totalCents = $orders->sum(fn ($order) => (int) round((float) $order->total * 100));
                if ($totalCents > 999999999999) {
                    throw ValidationException::withMessages(['order_ids' => 'Valoarea selecției depășește limita unei facturi. Selectați mai puține fișe.']);
                }

                $invoice = ServiceInvoice::create([
                    'service_customer_id' => $customer->id,
                    'number' => $validated['number'],
                    'amount' => $validated['amount'],
                    'orders_total' => number_format($totalCents / 100, 2, '.', ''),
                    'issued_on' => $validated['issued_on'],
                    'recorded_at' => now(),
                    'created_by' => $userId,
                    'customer_name' => $customer->name,
                    'customer_fiscal_code' => $customer->fiscal_code,
                ]);

                // The locked, validated selection is updated atomically. Payment remains unpaid.
                ServiceOrder::whereIn('id', $orders->modelKeys())->update(['service_invoice_id' => $invoice->id]);

                return $invoice;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['number' => 'Acest număr de factură este deja înregistrat.']);
        }
    }
}
