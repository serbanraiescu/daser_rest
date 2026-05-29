<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventar — {{ $snapshot->name }}</title>
    <style>
        /* ===== RESET & BASE ===== */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Arial', 'Helvetica Neue', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ===== PAGE SETUP ===== */
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }

        /* ===== PRINT BUTTON (screen only) ===== */
        .print-btn {
            position: fixed;
            top: 16px; right: 16px;
            background: #1d4ed8;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 999;
        }
        .print-btn:hover { background: #1e40af; }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1a1a1a;
        }

        .header-left h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .header-left p {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
        }

        .header-right .company {
            font-size: 13px;
            font-weight: 700;
        }

        .header-right .date-label {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        thead tr th {
            background: #1e293b;
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: left;
            white-space: nowrap;
        }

        thead tr th.center { text-align: center; }
        thead tr th.right  { text-align: right; }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody td {
            padding: 5px 8px;
            font-size: 10.5px;
            vertical-align: middle;
        }

        tbody td.center { text-align: center; }
        tbody td.right  { text-align: right; font-family: 'Courier New', monospace; }

        /* Coloana stoc scriptic — bold */
        tbody td.stock-value {
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #0f172a;
        }

        /* Coloane goale (pentru completare fizică) */
        tbody td.empty-field {
            border-bottom: 1px solid #94a3b8;
            min-width: 70px;
            color: #cbd5e1;
            font-style: italic;
            font-size: 9px;
        }

        /* Badge unitate */
        .unit-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }

        .footer .sign-box {
            flex: 1;
            max-width: 220px;
        }

        .footer .sign-box p {
            font-size: 9px;
            color: #555;
            margin-bottom: 20px;
        }

        .footer .sign-box .sign-line {
            border-top: 1px solid #1a1a1a;
            padding-top: 3px;
            font-size: 9px;
            color: #555;
        }

        .footer .totals {
            text-align: right;
            font-size: 10px;
            color: #444;
        }

        .footer .totals strong {
            display: block;
            font-size: 11px;
            color: #0f172a;
        }

        /* ===== LEGEND ===== */
        .legend {
            margin-top: 8px;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>

{{-- Buton print (dispare la imprimare) --}}
<button class="print-btn no-print" onclick="window.print()">
    🖨️ Printează A4
</button>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        <h1>📋 {{ $snapshot->name }}</h1>
        <p>Data inventarului: <strong>{{ $snapshot->snapshot_date->format('d.m.Y') }}</strong></p>
        @if($snapshot->notes)
            <p>Observații: {{ $snapshot->notes }}</p>
        @endif
    </div>
    <div class="header-right">
        @if($companyName)
            <p class="company">{{ $companyName }}</p>
        @endif
        <p class="date-label">Generat: {{ now()->format('d.m.Y H:i') }}</p>
        <p class="date-label">Total produse: <strong>{{ $items->count() }}</strong></p>
    </div>
</div>

{{-- TABEL INVENTAR --}}
<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Produs Inventar</th>
            <th class="center" style="width:60px">Unitate</th>
            <th class="right" style="width:90px">Stoc Scriptic</th>
            <th class="right" style="width:90px">Stoc Fizic</th>
            <th class="right" style="width:80px">Diferență</th>
            <th style="width:160px">Observații</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $snapshotItem)
        <tr>
            <td class="center" style="color:#94a3b8">{{ $i + 1 }}</td>
            <td>
                <strong>{{ $snapshotItem->inventoryItem->name }}</strong>
                @if($snapshotItem->inventoryItem->sku)
                    <br><span style="font-size:8.5px;color:#94a3b8">{{ $snapshotItem->inventoryItem->sku }}</span>
                @endif
            </td>
            <td class="center">
                <span class="unit-badge">{{ $snapshotItem->inventoryItem->unit }}</span>
            </td>
            <td class="right stock-value">
                {{ number_format((float)$snapshotItem->system_stock, 3, ',', '.') }}
            </td>
            {{-- Stoc fizic — completat manual sau din sistem dacă există --}}
            <td class="right @if(!$snapshotItem->physical_stock) empty-field @else stock-value @endif">
                @if($snapshotItem->physical_stock !== null)
                    {{ number_format((float)$snapshotItem->physical_stock, 3, ',', '.') }}
                @else
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                @endif
            </td>
            {{-- Diferență --}}
            <td class="right @if(!$snapshotItem->difference) empty-field @endif"
                style="{{ $snapshotItem->difference < 0 ? 'color:#dc2626;font-weight:700' : ($snapshotItem->difference > 0 ? 'color:#16a34a;font-weight:700' : '') }}">
                @if($snapshotItem->difference !== null)
                    {{ $snapshotItem->difference > 0 ? '+' : '' }}{{ number_format((float)$snapshotItem->difference, 3, ',', '.') }}
                @else
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                @endif
            </td>
            {{-- Observații --}}
            <td class="@if(!$snapshotItem->observations) empty-field @endif" style="font-size:9px;">
                {{ $snapshotItem->observations ?? '' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;font-style:italic">
                Niciun produs în acest inventar. Generați stocurile din lista de inventare.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- LEGENDA --}}
<p class="legend">
    * Stoc Scriptic = stoc din sistem la momentul generării inventarului &nbsp;|&nbsp;
    Stoc Fizic = completat manual după numărare &nbsp;|&nbsp;
    Diferență = Fizic – Scriptic (negativ = lipsă, pozitiv = surplus)
</p>

{{-- FOOTER CU SEMNĂTURI --}}
<div class="footer">
    <div class="sign-box">
        <p>Întocmit de:</p>
        <div class="sign-line">Nume și semnătură</div>
    </div>
    <div class="sign-box">
        <p>Verificat de:</p>
        <div class="sign-line">Nume și semnătură</div>
    </div>
    <div class="sign-box">
        <p>Aprobat de:</p>
        <div class="sign-line">Nume și semnătură</div>
    </div>
    <div class="totals">
        <span>Data: {{ $snapshot->snapshot_date->format('d.m.Y') }}</span><br>
        <strong>{{ $companyName }}</strong>
    </div>
</div>

</body>
</html>
