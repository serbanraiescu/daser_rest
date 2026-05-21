<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Financiar - {{ $settings->site_name ?? 'Restaurant OS' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }
        
        /* Typography */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .text-muted { color: #64748b; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-info { color: #2563eb; }
        
        .large { font-size: 13px; }
        .xlarge { font-size: 16px; }
        .title { font-size: 22px; font-weight: 900; letter-spacing: -0.5px; color: #0f172a; margin: 0; text-transform: uppercase; }
        
        /* Dividers & Borders */
        .border-b { border-bottom: 1px solid #e2e8f0; }
        .border-double { border-bottom: 3px double #cbd5e1; }
        .my-4 { margin-top: 16px; margin-bottom: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-6 { margin-top: 24px; }
        
        /* Table Layouts */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
            padding: 8px 10px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        /* KPI Cards Grid using Cross-compatible Tables */
        .kpi-table {
            width: 100%;
            border: 0;
            margin-bottom: 24px;
        }
        .kpi-table td {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: 14px;
            width: 25%;
            vertical-align: top;
        }
        .kpi-title {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .kpi-val {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
        }
        .kpi-sub {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* Standalone header info layout */
        .header-table {
            width: 100%;
            border: 0;
            margin-bottom: 20px;
        }
        .header-table td {
            border: 0;
            padding: 0;
            background: none !important;
        }
        
        /* Interactive Panel - Hidden during Print */
        .no-print-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 30px;
            text-align: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: #f59e0b;
            color: #ffffff;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            margin: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .btn-secondary {
            background: #475569;
        }
        .btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-info { background-color: #dbeafe; color: #1d4ed8; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-gray { background-color: #f1f5f9; color: #475569; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; color: #000; }
            .kpi-table td { border: 1px solid #cbd5e1 !important; }
            td, th { border-color: #cbd5e1 !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Control Buttons Panel -->
    <div class="no-print no-print-panel">
        <button onclick="window.print()" class="btn">PRINTEAZĂ RAPORT (A4)</button>
        <a href="{{ route('admin.reports.pdf', ['period' => $period, 'selectedDate' => $selectedDate, 'startDate' => $startDate, 'endDate' => $endDate]) }}" class="btn btn-secondary">DESCARCĂ FORMAT PDF</a>
        <p style="font-size: 12px; margin-top: 8px; color: #64748b; margin-bottom: 0;">Caseta de imprimare s-a deschis automat. Puteți salva documentul ca PDF sau îl puteți trimite direct către imprimanta A4.</p>
    </div>

    <!-- Company Header Details -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="title">{{ $settings->site_name ?? 'Daser Restaurant OS' }}</div>
                <div class="text-muted" style="margin-top: 4px; font-size: 11px;">
                    @if($settings->address) {{ $settings->address }}<br> @endif
                    @if($settings->contact_phone) Tel: {{ $settings->contact_phone }}<br> @endif
                    @if($settings->fiscal_code) CIF: {{ $settings->fiscal_code }} @endif
                    @if($settings->trade_register) | Reg. Com: {{ $settings->trade_register }} @endif
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div class="bold large" style="color: #475569; text-transform: uppercase;">Raport Financiar & Performanță</div>
                <div class="bold" style="font-size: 12px; color: #d97706; margin-top: 4px;">{{ $range_title }}</div>
                <div class="text-muted" style="font-size: 9px; margin-top: 4px;">Generat la: {{ now()->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="border-double mb-6"></div>

    <!-- General KPIs Row (Table grid style for PDF safety) -->
    <table class="kpi-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="kpi-title">Vânzări Totale</div>
                <div class="kpi-val text-success">{{ number_format($kpis['total_revenue'], 2) }} {{ $currency }}</div>
                <div class="kpi-sub">Exclusiv comenzi anulate</div>
            </td>
            <td>
                <div class="kpi-title">Total Comenzi</div>
                <div class="kpi-val">{{ $kpis['total_orders'] }}</div>
                <div class="kpi-sub">
                    <span class="text-success bold">{{ $kpis['successful_orders'] }} finalizate</span> | 
                    <span class="text-danger bold">{{ $kpis['cancelled_orders'] }} anulate</span>
                </div>
            </td>
            <td>
                <div class="kpi-title">Valoare Medie</div>
                <div class="kpi-val text-info">{{ number_format($kpis['average_value'], 2) }} {{ $currency }}</div>
                <div class="kpi-sub">Per comandă finalizată</div>
            </td>
            <td>
                <div class="kpi-title">Rată Finalizare</div>
                <div class="kpi-val">
                    @php 
                        $rate = $kpis['total_orders'] > 0 ? ($kpis['successful_orders'] / $kpis['total_orders']) * 100 : 0;
                    @endphp
                    {{ number_format($rate, 1) }}%
                </div>
                <div class="kpi-sub">Comenzi reușite vs total</div>
            </td>
        </tr>
    </table>

    <!-- Payment Methods & Revenues breakdown -->
    <div class="bold large mb-2" style="text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">Distribuție metode de plată</div>
    <table style="width: 100%; margin-bottom: 24px;">
        <thead>
            <tr>
                <th style="width: 40%;" class="text-left">Metodă Plată</th>
                <th style="width: 30%; text-align: center;">Comenzi încasate</th>
                <th style="width: 30%;" class="text-right">Valoare Totală Încăsări</th>
            </tr>
        </thead>
        <tbody>
            @php
                $cashCount = $history->where('status', 'paid')->where('payment_method', 'cash')->count() + $history->where('status', 'delivered')->where('payment_method', 'cash')->count();
                $cardCount = $history->where('status', 'paid')->where('payment_method', 'card')->count() + $history->where('status', 'delivered')->where('payment_method', 'card')->count();
                $onlineCount = $kpis['successful_orders'] - ($cashCount + $cardCount);
            @endphp
            <tr>
                <td class="bold">Încasări CASH</td>
                <td class="text-center font-bold">{{ $cashCount }}</td>
                <td class="text-right font-bold text-success">{{ number_format($kpis['cash_revenue'], 2) }} {{ $currency }}</td>
            </tr>
            <tr>
                <td class="bold">Încasări CARD (la POS)</td>
                <td class="text-center font-bold">{{ $cardCount }}</td>
                <td class="text-right font-bold text-success">{{ number_format($kpis['card_revenue'], 2) }} {{ $currency }}</td>
            </tr>
            <tr>
                <td class="bold">Încasări ONLINE / Alte metode</td>
                <td class="text-center font-bold">{{ $onlineCount }}</td>
                <td class="text-right font-bold text-success">{{ number_format($kpis['online_revenue'], 2) }} {{ $currency }}</td>
            </tr>
            <tr style="background-color: #f1f5f9 !important;">
                <td class="bold large">TOTAL GENERAL VENITURI</td>
                <td class="text-center font-bold large">{{ $kpis['successful_orders'] }}</td>
                <td class="text-right font-bold large text-success" style="font-size: 13px;">{{ number_format($kpis['total_revenue'], 2) }} {{ $currency }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Side by Side Tables: Top Products and Waiters -->
    <table style="width: 100%; border: 0; margin-bottom: 24px;" cellpadding="0" cellspacing="0">
        <tr>
            <!-- Left Side: Top Products -->
            <td style="width: 48%; border: 0; padding: 0; vertical-align: top; background: transparent !important;">
                <div class="bold large mb-2" style="text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">Top Produse Vândute</div>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="text-left">Produs</th>
                            <th style="width: 25%; text-align: center;">Cantitate</th>
                            <th style="width: 35%;" class="text-right">Venit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($products, 0, 8) as $prod)
                            <tr>
                                <td class="bold">{{ $prod->name }}</td>
                                <td class="text-center font-bold">{{ $prod->quantity_sold }}</td>
                                <td class="text-right text-success bold">{{ number_format($prod->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Niciun produs vândut.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            
            <!-- Spacer column -->
            <td style="width: 4%; border: 0; padding: 0; background: transparent !important;"></td>

            <!-- Right Side: Waiter Performance -->
            <td style="width: 48%; border: 0; padding: 0; vertical-align: top; background: transparent !important;">
                <div class="bold large mb-2" style="text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">Performanță Ospătari</div>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="text-left">Ospătar</th>
                            <th style="width: 25%; text-align: center;">Comenzi</th>
                            <th style="width: 35%;" class="text-right">Vânzări</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waiters as $waiter)
                            <tr>
                                <td class="bold">{{ $waiter->waiter_name }}</td>
                                <td class="text-center font-bold">{{ $waiter->orders_count }}</td>
                                <td class="text-right text-success bold">{{ number_format($waiter->total_sales, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Nicio vânzare înregistrată.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Detailed Order History -->
    <div class="bold large mb-2 mt-6" style="text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; page-break-before: auto;">Istoric Detaliat Comenzi</div>
    <table style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 15%;" class="text-left">Nr. Comandă</th>
                <th style="width: 10%; text-align: center;">Masă</th>
                <th style="width: 15%; text-align: center;">Metodă Plată</th>
                <th style="width: 20%; text-align: center;">Dată & Oră</th>
                <th style="width: 20%;" class="text-right">Valoare</th>
                <th style="width: 20%; text-align: center;">Stare</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $order)
                <tr>
                    <td class="bold">{{ $order->order_number }}</td>
                    <td class="text-center">Masa {{ $order->table_number ?? '-' }}</td>
                    <td class="text-center uppercase" style="font-size: 9px; font-weight: bold; color: #475569;">
                        {{ $order->payment_method === 'cash' ? 'Cash' : ($order->payment_method === 'card' ? 'Card' : 'Online') }}
                    </td>
                    <td class="text-center text-muted" style="font-size: 10px;">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    <td class="text-right bold">{{ number_format($order->total, 2) }} {{ $currency }}</td>
                    <td class="text-center">
                        @php
                            $badgeClass = [
                                'paid' => 'badge-success',
                                'delivered' => 'badge-info',
                                'pending' => 'badge-warning',
                                'preparing' => 'badge-info',
                                'ready' => 'badge-warning',
                                'cancelled' => 'badge-danger',
                            ][$order->status] ?? 'badge-gray';
                            
                            $badgeLabel = [
                                'paid' => 'Achitată',
                                'delivered' => 'Terminată',
                                'pending' => 'În Așteptare',
                                'preparing' => 'În Pregătire',
                                'ready' => 'Pregătită',
                                'cancelled' => 'Anulată',
                            ][$order->status] ?? $order->status;
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 20px 0;">Nicio comandă înregistrată în intervalul selectat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer of A4 Report -->
    <div style="margin-top: 40px; border-top: 1px dashed #cbd5e1; padding-top: 12px; text-align: center; color: #64748b; font-size: 9px; font-style: italic;">
        Generat automat de Daser Restaurant OS. Raport destinat uzului intern.
        <br>
        © {{ date('Y') }} {{ $settings->site_name ?? 'Restaurant OS' }}. Toate drepturile rezervate.
    </div>

</body>
</html>
