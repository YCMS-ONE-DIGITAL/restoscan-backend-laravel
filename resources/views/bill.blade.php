<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bill #{{ $order->id }}</title>

    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
            padding: 10px 8px;
            width: 100%;
            box-sizing: border-box;
            color: #000;
        }
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        .big    { font-size: 14px; }
        .small  { font-size: 9.5px; }
        .line {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .item-name { width: 68%; word-wrap: break-word; }
        .item-price { width: 32%; text-align: right; }
        .footer { margin-top: 15px; line-height: 1.5; }
    </style>
</head>
<body>

    <!-- Restaurant Header -->
    <div class="center bold big">
        {{ optional($order->restaurant)->restaurant_name ?? 'My Restaurant' }}
    </div>

    <div class="center small">
        @if($order->restaurant?->address)
            {{ $order->restaurant->address }}<br>
        @endif
        @if($order->restaurant?->contact_number)
            Mob: {{ $order->restaurant->contact_number }}
        @endif
    </div>

    <div class="line"></div>

    <!-- Bill Info -->
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
            <td class="right bold">
                {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}
                @if($order->order_type === 'dine_in' && $order->table)
                    <span class="bold">• Table {{ $order->table->table_no }}</span>
                @endif
            </td>
        </tr>

        @if($order->customer?->name)
        <tr>
            <td>Customer</td>
            <td class="right">{{ $order->customer->name }}</td>
        </tr>
        @endif

        @if($order->captain?->name ?? $order->staff?->name ?? $order->waiter?->name)
        <tr>
            <td>Captain</td>
            <td class="right bold">
                {{ $order->captain?->name ?? $order->staff?->name ?? $order->waiter?->name }}
            </td>
        </tr>
        @endif
    </table>

    <div class="line"></div>

    <!-- Items List -->
    <table>
        @foreach($order->items as $item)
            @php
                $name = $item->menuItem->name
                    ?? $item->menu_item->name
                    ?? $item->name
                    ?? 'Item';

                $qty   = $item->quantity;
                $price = $item->price;
                $total = $qty * $price;
            @endphp
            <tr>
                <td class="item-name">
                    {{ $name }} <strong>x{{ $qty }}</strong>
                    @if($item->variant ?? $item->size)
                        ({{ $item->variant ?? $item->size }})
                    @endif
                </td>
                <td class="item-price bold">₹{{ number_format($total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <!-- Totals -->
    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">₹{{ number_format($order->subtotal ?? $order->total_amount, 2) }}</td>
        </tr>

        @if(isset($order->discount) && $order->discount > 0)
        <tr>
            <td>Discount</td>
            <td class="right">-₹{{ number_format($order->discount, 2) }}</td>
        </tr>
        @endif

        @if(isset($order->gst_amount) && $order->gst_amount > 0)
        <tr>
            <td>GST</td>
            <td class="right">₹{{ number_format($order->gst_amount, 2) }}</td>
        </tr>
        @endif

        @if(isset($order->service_charge) && $order->service_charge > 0)
        <tr>
            <td>Service Charge</td>
            <td class="right">₹{{ number_format($order->service_charge, 2) }}</td>
        </tr>
        @endif

        <tr class="bold big">
            <td>Grand Total</td>
            <td class="right">₹{{ number_format($order->final_amount ?? $order->total_amount, 2) }}</td>
        </tr>
    </table>

    @if($order->payment_mode)
    <div class="line"></div>
    <div class="center bold">
        Paid via {{ ucwords(str_replace('_', ' ', $order->payment_mode)) }}
    </div>
    @endif

    <div class="line"></div>

    <!-- Footer -->
    <div class="center footer small">
        <strong>Thank You! Visit Again</strong><br>
        @if($order->restaurant?->slogan)
            {{ $order->restaurant->slogan }}<br>
        @endif
        Have a nice day
    </div>

    <!-- Paper cut space -->
    <div style="height: 30px;"></div>

</body>
</html>