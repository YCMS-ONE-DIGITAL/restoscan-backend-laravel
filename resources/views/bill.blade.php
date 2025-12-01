<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bill #{{ $order->id }}</title>

    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        body {
            width: 58mm;
            margin: 0;
            padding: 5px;
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            font-size: 10.5px;
            line-height: 1.3;
            color: #000;
        }

        * {
            page-break-inside: avoid;
        }

        img {
            max-width: 100%;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .big {
            font-size: 14px;
        }

        .small {
            font-size: 9.5px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .item-name {
            width: 68%;
        }

        .item-price {
            width: 32%;
            text-align: right;
        }

        .footer {
            margin-top: 10px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <div class="center">
        <img    src="{{ asset('storage/' . $order->restaurant->logo_url) }}" 
  style="max-width: 100px; display:block; margin:0 auto;"
  onerror="this.style.display='none'" >
    </div>

    <div class="center bold big">
        {{ optional($order->restaurant)->restaurant_name ?? 'My Restaurant' }}
    </div>

    <div class="center small">
        @if ($order->restaurant?->address)
            {{ $order->restaurant->address }}<br>
        @endif
        @if ($order->restaurant?->contact_number)
            Mob: {{ $order->restaurant->contact_number }}
        @endif
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td>Bill No</td>
            <td class="right bold">#{{ $order->id }}</td>
        </tr>
        <tr>
            <td>Date</td>
            <td class="right">{{ $order->created_at->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Time</td>
            <td class="right">{{ $order->created_at->format('h:i A') }}</td>
        </tr>
        <tr>
            <td>Type</td>
            <td class="right bold">{{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        @foreach ($order->items as $item)
            @php
                $name = $item->menuItem->name ?? $item->menu_item->name ?? $item->name;
                $qty = $item->quantity;
                $price = $item->price;
                $total = $qty * $price;
            @endphp
            <tr>
                <td class="item-name">{{ $name }} x{{ $qty }}</td>
                <td class="item-price bold">₹{{ number_format($total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">₹{{ number_format($order->subtotal ?? $order->total_amount, 2) }}</td>
        </tr>

        <tr class="bold big">
            <td>Grand Total</td>
            <td class="right">₹{{ number_format($order->final_amount ?? $order->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center footer small">
        <strong>Thank You! Visit Again</strong><br>
        Have a nice day
    </div>

</body>

<script>
    window.onload = () => window.print();
</script>

</html>
