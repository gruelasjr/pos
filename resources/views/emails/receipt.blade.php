<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        table{width:100%;border-collapse:collapse;margin-top:8px}
        td,th{border:1px solid #e5e7eb;padding:8px;text-align:left}
        h1{font-size:20px;margin:0 0 8px}
        p{margin:4px 0}
    </style>
</head>
<body>
    <h1>Recibo {{ $sale->folio }}</h1>
    <p>Almacén: {{ $sale->warehouse->name ?? '' }}</p>
    <p>Vendedor: {{ $sale->seller->name ?? '' }}</p>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ (int) $item->quantity }}</td>
                    <td>${{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>${{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Total: ${{ number_format((float) $sale->total_net, 2) }}</p>
</body>
</html>
