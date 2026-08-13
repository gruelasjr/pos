<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 34px 36px; }
        body { font-family: "DejaVu Sans", sans-serif; color:#0b1526; font-size:10px; }
        h1 { margin:0; font-size:22px; } h2 { margin:20px 0 8px; font-size:14px; }
        .header { border-bottom:2px solid #1f65f5; padding-bottom:12px; margin-bottom:16px; }
        .muted { color:#65748b; } .metrics { width:100%; border-collapse:collapse; margin:12px 0; }
        .metrics td { width:33%; border:1px solid #dbe2ea; padding:12px; }
        .metric { display:block; font-size:18px; font-weight:bold; margin-top:4px; }
        table.data { width:100%; border-collapse:collapse; } table.data th { background:#eef4ff; text-align:left; }
        table.data th, table.data td { border-bottom:1px solid #dbe2ea; padding:6px; vertical-align:top; }
        .right { text-align:right; } .page-footer { position:fixed; bottom:-24px; left:0; right:0; color:#65748b; font-size:8px; }
        .bar { display:inline-block; height:7px; background:#1f65f5; }
        .chart { width:100%; height:160px; margin:8px 0 14px; }
    </style>
</head>
<body>
<div class="header">
    <h1>POS Faro - {{ $report === 'overview' ? 'Resumen de ventas' : 'Más vendidos' }}</h1>
    <div class="muted">{{ $payload['period']['from'] }} a {{ $payload['period']['to'] }} · Zona horaria {{ $payload['timezone'] }}</div>
</div>

@if($report === 'overview')
    <table class="metrics"><tr>
        <td>Ventas netas<span class="metric">${{ number_format($payload['summary']['net_sales'], 2) }}</span></td>
        <td>Tickets<span class="metric">{{ number_format($payload['summary']['tickets']) }}</span></td>
        <td>Ticket promedio<span class="metric">${{ number_format($payload['summary']['average_ticket'], 2) }}</span></td>
    </tr></table>
    @php
        $points = $payload['series']['current']; $max = max(1, collect($points)->max('total'));
        $step = 720 / max(1, count($points)); $barWidth = $step * .62;
    @endphp
    @php
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 760 170"><line x1="20" y1="145" x2="740" y2="145" stroke="#b9c4d3" stroke-width="1"/>';
        foreach ($points as $i => $point) {
            $height = ($point['total']/$max)*105; $x = 20 + $i*$step + (($step-$barWidth)/2); $center = $x + $barWidth/2;
            $svg .= '<rect x="'.$x.'" y="'.(145-$height).'" width="'.$barWidth.'" height="'.$height.'" rx="2" fill="#1f65f5"/>';
            if ($point['total'] > 0) $svg .= '<text x="'.$center.'" y="'.(140-$height).'" font-family="DejaVu Sans" font-size="8" text-anchor="middle">$'.number_format($point['total'],0).'</text>';
            $svg .= '<text x="'.$center.'" y="162" font-family="DejaVu Sans" font-size="8" text-anchor="middle">'.substr($point['date'],5).'</text>';
        }
        $svg .= '</svg>';
    @endphp
    <h2>Ventas por día</h2>
    <img class="chart" src="data:image/svg+xml;base64,{{ base64_encode($svg) }}" alt="Serie de ventas netas por día">
    <h2>Rendimiento por vendedor</h2>
    <table class="data"><thead><tr><th>Vendedor</th><th class="right">Ventas</th><th class="right">Tickets</th><th class="right">Ticket promedio</th><th class="right">Variación</th></tr></thead><tbody>
    @foreach($payload['sellers'] as $seller)<tr><td>{{ $seller['seller_name'] }}</td><td class="right">${{ number_format($seller['total'],2) }}</td><td class="right">{{ $seller['sales'] }}</td><td class="right">${{ number_format($seller['average_ticket'],2) }}</td><td class="right">{{ number_format($seller['delta'],1) }}%</td></tr>@endforeach
    </tbody></table>
@else
    <table class="metrics"><tr>
        <td>Unidades vendidas<span class="metric">{{ number_format($payload['summary']['units']) }}</span></td>
        <td>Ventas netas<span class="metric">${{ number_format($payload['summary']['net_sales'], 2) }}</span></td>
        <td>Productos con venta<span class="metric">{{ number_format($payload['summary']['products']) }}</span></td>
    </tr></table>
    @if($payload['non_additive_tag_totals'])<p class="muted">Un producto puede aparecer en más de un tag; los subtotales por tag no son aditivos.</p>@endif
    <h2>Jerarquía: {{ implode(' > ', $payload['group_by']) }}</h2>
    <table class="data"><thead><tr><th>Grupo / producto</th><th>SKU</th><th class="right">Unidades</th><th class="right">Ventas netas</th><th class="right">Tickets</th><th class="right">Existencias</th></tr></thead><tbody>
    @php($renderNodes = function($nodes,$depth=0) use (&$renderNodes) { foreach($nodes as $node) { echo '<tr><td style="padding-left:'.(6+$depth*14).'px">'.e($node['label'] ?? $node['name']).'</td><td>'.e($node['sku'] ?? '').'</td><td class="right">'.number_format($node['units']).'</td><td class="right">$'.number_format($node['net_sales'],2).'</td><td class="right">'.number_format($node['tickets']).'</td><td class="right">'.number_format($node['stock']).'</td></tr>'; if(isset($node['children'])) $renderNodes($node['children'],$depth+1); } })
    @php($renderNodes($payload['tree']))
    </tbody></table>
@endif
<div class="page-footer">Fuente: Ventas POS · Actualizado {{ $payload['updated_at'] }}</div>
</body>
</html>
