<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Zilnic Servicii - {{ $report['staff_name'] }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 72mm;
            margin: 0 auto;
            padding: 8mm 2mm;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .large { font-size: 14px; }
        .xlarge { font-size: 16px; }
        .border-b { border-bottom: 1px dashed #000; margin: 6px 0; }
        .border-double { border-bottom: 3px double #000; margin: 6px 0; }
        .mt-4 { margin-top: 15px; }
        .mb-2 { margin-bottom: 5px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        th, td {
            padding: 3px 0;
            font-size: 11px;
            text-align: left;
        }
        
        .no-print {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #ea580c;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            margin: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .btn-secondary {
            background: #475569;
        }
        .btn:hover {
            opacity: 0.9;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0 auto; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" class="btn">PRINTEAZĂ RAPORT</button>
        <a href="{{ route('service.pdf-daily-report') }}" class="btn btn-secondary">DESCARCĂ PDF</a>
        <p style="font-size: 11px; margin-top: 6px; color: #64748b;">Se va deschide automat fereastra de printare.</p>
    </div>

    <div class="text-center">
        <div class="bold xlarge">{{ $settings->site_name ?? 'Daser Platform' }}</div>
        <div>{{ $settings->address ?? '' }}</div>
        <div>CIF: {{ $settings->company_vat_id ?? '' }}</div>
        <div class="border-double"></div>
        <div class="bold large">RAPORT ZILNIC SERVICII</div>
        <div class="bold" style="background: #000; color: #fff; display: inline-block; padding: 2px 8px; margin: 4px 0;">
            TURA SERVICIU
        </div>
        <div class="border-b"></div>
    </div>

    <table>
        <tr>
            <td class="bold">Data Raport:</td>
            <td class="text-right">{{ $report['date'] }}</td>
        </tr>
        <tr>
            <td class="bold">Operator:</td>
            <td class="text-right bold">{{ $report['staff_name'] }}</td>
        </tr>
        <tr>
            <td class="bold">Status:</td>
            <td class="text-right">Activ / Finalizat</td>
        </tr>
    </table>

    <div class="border-b"></div>
    <div class="bold">SUMAR FINANCIAR</div>
    <div class="border-b"></div>

    <table>
        <tr>
            <td>Total Comenzi în Tură:</td>
            <td class="text-right bold">{{ $report['total_orders_count'] }}</td>
        </tr>
        <tr>
            <td>Comenzi Încasate:</td>
            <td class="text-right bold">{{ $report['completed_orders_count'] }}</td>
        </tr>
        <tr class="bold">
            <td>TOTAL ÎNCASĂRI:</td>
            <td class="text-right large">{{ number_format($report['total_revenue'], 2) }} {{ $settings->currency ?? 'RON' }}</td>
        </tr>
    </table>

    <div class="border-b"></div>
    <div class="bold">METODE DE PLATĂ</div>
    <div class="border-b"></div>

    <table>
        <tr>
            <td>Încasări CASH:</td>
            <td class="text-right bold">{{ number_format($report['cash_revenue'], 2) }} {{ $settings->currency ?? 'RON' }}</td>
        </tr>
        <tr>
            <td>Încasări CARD:</td>
            <td class="text-right bold">{{ number_format($report['card_revenue'], 2) }} {{ $settings->currency ?? 'RON' }}</td>
        </tr>
        <tr>
            <td>Încasări MIXTE:</td>
            <td class="text-right bold">{{ number_format($report['mixed_revenue'], 2) }} {{ $settings->currency ?? 'RON' }}</td>
        </tr>
        <tr>
            <td>Încasări PROTOCOL:</td>
            <td class="text-right bold">{{ number_format($report['protocol_revenue'], 2) }} {{ $settings->currency ?? 'RON' }}</td>
        </tr>
    </table>

    <div class="border-b"></div>
    <div class="bold">SERVICII PRESTATE</div>
    <div class="border-b"></div>

    <table>
        <thead>
            <tr class="bold" style="border-bottom: 1px solid #000;">
                <th style="width: 55%;">Denumire Serviciu</th>
                <th style="width: 15%; text-align: center;">Cant</th>
                <th style="width: 30%; text-align: right;">Valoare</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['services_sold'] as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td style="text-align: center;">{{ $service->total_qty }}</td>
                    <td class="text-right">{{ number_format($service->total_value, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 10px 0;">Niciun serviciu prestat în această tură.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="border-double"></div>

    <div class="text-center mt-4" style="font-style: italic;">
        Sfârșit de tură. Vă mulțumim!
        <br>
        Generat la: {{ now()->format('d.m.Y H:i:s') }}
    </div>

    <div style="margin-top: 25px; border-top: 1px dashed #000; padding-top: 8px; text-align: center; color: #000; font-size: 8px;">
        Powered by Daser Platform OS
    </div>

</body>
</html>
