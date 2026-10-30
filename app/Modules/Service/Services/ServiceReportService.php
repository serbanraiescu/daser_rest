<?php

namespace App\Modules\Service\Services;

use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceReportService
{
    public function timezone(): string
    {
        return config('service_reporting.timezone', 'Europe/Bucharest');
    }

    public function range(string $period, ?string $date = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $day = CarbonImmutable::parse($date ?: 'now', $this->timezone());
        [$start, $end] = match ($period) {
            'weekly' => [$day->startOfWeek(), $day->endOfWeek()],
            'monthly' => [$day->startOfMonth(), $day->endOfMonth()],
            'custom' => [CarbonImmutable::parse($startDate ?: 'now', $this->timezone())->startOfDay(), CarbonImmutable::parse($endDate ?: 'now', $this->timezone())->endOfDay()],
            default => [$day->startOfDay(), $day->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            throw ValidationException::withMessages(['startDate' => 'Data de început trebuie să fie anterioară datei de sfârșit.']);
        }

        return compact('start', 'end');
    }

    public function ordersForPeriod(CarbonInterface $start, CarbonInterface $end, ?int $staffId = null): Builder
    {
        $bounds = $this->storageBounds($start, $end);

        return ServiceOrder::query()
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff_member_id', $staffId))
            ->where(function (Builder $query) use ($bounds) {
                $query->where(function (Builder $completed) use ($bounds) {
                    $completed->where('status', 'completed')
                        ->whereRaw('COALESCE(completed_at, created_at) BETWEEN ? AND ?', $bounds);
                })->orWhere(function (Builder $other) use ($bounds) {
                    $other->where('status', '!=', 'completed')->whereBetween('created_at', $bounds);
                });
            });
    }

    public function build(CarbonInterface $start, CarbonInterface $end, ?int $staffId = null, bool $includeHistory = true): array
    {
        $orders = $this->ordersForPeriod($start, $end, $staffId);
        $completed = (clone $orders)->where('status', 'completed');
        $completedCount = (clone $completed)->count();
        $completedValue = $this->money((clone $completed)->sum('total'));
        $paymentAmounts = [];
        $paymentCounts = [];
        foreach (['cash', 'card', 'mixed', 'protocol', 'client_account'] as $method) {
            $query = (clone $completed)->where('payment_method', $method);
            if (in_array($method, ['cash', 'card', 'mixed'], true)) {
                $query->where('payment_status', 'paid');
            }
            $paymentAmounts[$method] = $this->money((clone $query)->sum('total'));
            $paymentCounts[$method] = $query->count();
        }
        $collected = $this->money($paymentAmounts['cash'] + $paymentAmounts['card'] + $paymentAmounts['mixed']);

        $services = DB::table('service_order_items')
            ->whereIn('service_order_id', (clone $completed)->select('id'))
            ->select('name', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(line_total) as revenue'))
            ->groupBy('name')->orderByDesc('quantity_sold')->get();

        $staff = (clone $completed)->with('staff:id,name')
            ->select('staff_member_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_sales'))
            ->groupBy('staff_member_id')->orderByDesc('total_sales')->get()
            ->map(fn ($row) => (object) [
                'staff_name' => $row->staff?->name ?? 'Nespecificat',
                'orders_count' => (int) $row->orders_count,
                'total_sales' => $this->money($row->total_sales),
            ])->all();

        $pending = ServiceOrder::uninvoiced()
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff_member_id', $staffId));
        $periodPending = (clone $completed)->where('payment_method', 'client_account')->whereNull('service_invoice_id');
        $clients = (clone $pending)->with('customer:id,name')
            ->select('service_customer_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as amount'))
            ->groupBy('service_customer_id')->orderBy('service_customer_id')->get()
            ->map(fn ($row) => [
                'id' => $row->service_customer_id,
                'name' => $row->customer?->name ?? 'Client indisponibil',
                'orders_count' => (int) $row->orders_count,
                'amount' => $this->money($row->amount),
            ])->all();

        // A multi-order invoice is counted once. Staff reports do not allocate invoices between operators.
        $invoices = $staffId === null
            ? ServiceInvoice::whereBetween('issued_on', [$start->format('Y-m-d'), $end->format('Y-m-d')])->orderBy('issued_on')->orderBy('id')->get()
            : collect();
        $settings = CompanySetting::first();

        return [
            'meta' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'timezone' => $this->timezone(),
                'currency' => $settings?->currency ?? 'RON',
                'generated_at' => CarbonImmutable::now($this->timezone())->toIso8601String(),
                'uninvoiced_basis' => 'current_state',
            ],
            'kpis' => [
                'total_orders' => (clone $orders)->count(),
                'successful_orders' => $completedCount,
                'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
                'open_orders' => (clone $orders)->where('status', 'open')->count(),
                'completed_value' => $completedValue,
                'total_revenue' => $collected,
                'average_value' => $completedCount ? $this->money($completedValue / $completedCount) : 0.0,
                'cash_revenue' => $paymentAmounts['cash'],
                'card_revenue' => $paymentAmounts['card'],
                'mixed_revenue' => $paymentAmounts['mixed'],
                'protocol_revenue' => $paymentAmounts['protocol'],
                'client_account_revenue' => $paymentAmounts['client_account'],
                'unpaid_revenue' => $this->money($completedValue - $collected - $paymentAmounts['protocol'] - $paymentAmounts['client_account']),
                'payment_counts' => $paymentCounts,
            ],
            'uninvoiced' => [
                'period' => ['orders_count' => (clone $periodPending)->count(), 'amount' => $this->money($periodPending->sum('total'))],
                'current' => ['orders_count' => (clone $pending)->count(), 'amount' => $this->money($pending->sum('total'))],
                'clients' => $clients,
            ],
            'invoices' => [
                'count' => $invoices->count(),
                'amount' => $this->money($invoices->sum('amount')),
                'records' => $invoices->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer_id' => $invoice->service_customer_id,
                    'customer_name' => $invoice->customer_name,
                    'issued_on' => $invoice->issued_on->format('Y-m-d'),
                    'amount' => $this->money($invoice->amount),
                    'orders_total' => $this->money($invoice->orders_total),
                ])->all(),
            ],
            'services' => $services->all(),
            'staff' => $staff,
            'history' => $includeHistory ? (clone $orders)->with(['staff', 'invoice'])->orderByRaw('COALESCE(completed_at, created_at) DESC')->orderByDesc('id')->get() : collect(),
            'range_title' => $start->isSameDay($end) ? 'Ziua: '.$start->format('d.m.Y') : 'Perioada: '.$start->format('d.m.Y').' - '.$end->format('d.m.Y'),
            'currency' => $settings?->currency ?? 'RON',
            'site_name' => $settings?->site_name ?? 'Vulcanizare',
            'settings' => $settings ?? new CompanySetting(),
        ];
    }

    public function dailyForStaff(int $staffId, ?string $staffName = null): array
    {
        $range = $this->range('daily');
        $data = $this->build($range['start'], $range['end'], $staffId, false);

        return array_merge($data['kpis'], [
            'staff_name' => $staffName,
            'date' => $range['start']->format('d.m.Y'),
            'total_orders_count' => $data['kpis']['total_orders'],
            'completed_orders_count' => $data['kpis']['successful_orders'],
            'uninvoiced_revenue' => $data['uninvoiced']['period']['amount'],
            'services_sold' => collect($data['services'])->map(fn ($item) => (object) [
                'name' => $item->name,
                'total_qty' => $item->quantity_sold,
                'total_value' => $item->revenue,
            ]),
        ]);
    }

    private function storageBounds(CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            $start->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $end->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ];
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
