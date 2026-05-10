<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de plata #{{ $order->order_number }}</title>
    <style>
        @page { size: 80mm 297mm; margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 72mm; 
            margin: 0 auto; 
            padding: 10mm 2mm;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
        }
        .text-center { text-center: center !important; }
        .flex { display: flex; justify-content: space-between; }
        .border-b { border-bottom: 1px dashed #000; margin: 5px 0; }
        .bold { font-weight: bold; }
        .large { font-size: 16px; }
        .mt-4 { margin-top: 15px; }
        .mb-2 { margin-bottom: 5px; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #fdf6e3; padding: 10px; margin-bottom: 20px; border: 1px solid #eee; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #ea580c; color: #white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">PRINTEAZĂ NOTA</button>
        <p style="font-size: 10px; margin-top: 5px;">Se va deschide automat fereastra de printare.</p>
    </div>

    <div style="text-align: center;">
        <div class="bold large">{{ $settings->site_name ?? 'Daser Restaurant' }}</div>
        <div>{{ $settings->address ?? '' }}</div>
        <div>CIF: {{ $settings->company_vat_id ?? '' }}</div>
        <div class="border-b"></div>
    </div>

    <div class="mb-2">
        <div class="flex">
            <span>Data:</span>
            <span>{{ now()->format('d.m.Y H:i') }}</span>
        </div>
        <div class="flex">
            <span>Masa:</span>
            <span class="bold">{{ $order->table_number }}</span>
        </div>
        <div class="flex">
            <span>Ospatar:</span>
            <span>{{ Session::get('staff_name') }}</span>
        </div>
        <div class="flex">
            <span>Comanda:</span>
            <span>#{{ $order->order_number }}</span>
        </div>
    </div>

    <div class="border-b"></div>
    <div class="bold mb-2">PRODUSE</div>
    
    @foreach($order->items as $item)
        <div class="mb-2">
            <div>{{ $item->name }} {{ $item->variation ? '('.$item->variation->name.')' : '' }}</div>
            <div class="flex">
                <span>{{ $item->quantity }} x {{ number_format($item->price, 2) }}</span>
                <span class="bold">{{ number_format($item->price * $item->quantity, 2) }}</span>
            </div>
        </div>
    @endforeach

    <div class="border-b"></div>
    
    <div class="flex large bold mt-4">
        <span>TOTAL:</span>
        <span>{{ number_format($order->total, 2) }} {{ $settings->currency ?? 'RON' }}</span>
    </div>

    <div class="mt-4" style="text-align: center; font-style: italic;">
        Va multumim pentru vizita!
        <br>
        ACESTA NU ESTE BON FISCAL
    </div>

    <div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center; color: #888; font-size: 8px;">
        Powered by RestaurantOS
    </div>
</body>
</html>
