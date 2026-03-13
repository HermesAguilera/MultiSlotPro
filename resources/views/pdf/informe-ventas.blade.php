<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Ventas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            background: #ffffff;
            padding: 30px 32px;
            font-size: 11px;
            line-height: 1.55;
        }

        /* ── HEADER ─────────────────────────────────────────────────────── */
        .page-header {
            border-bottom: 3px solid #1e293b;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .page-header table { width: 100%; border-collapse: collapse; }

        .page-header h1 {
            font-size: 21px;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.4px;
            margin-bottom: 3px;
        }

        .page-header .subtitle {
            font-size: 10px;
            color: #64748b;
        }

        .meta-right {
            text-align: right;
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.7;
        }

        .badge {
            display: inline-block;
            background: #1e293b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            border-radius: 4px;
            padding: 2px 9px;
            margin-bottom: 5px;
        }

        .meta-range {
            font-size: 10px;
            font-weight: 600;
            color: #334155;
            display: block;
            margin-bottom: 1px;
        }

        /* ── SECTION LABELS ──────────────────────────────────────────────── */
        .section-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .9px;
            color: #94a3b8;
            margin-top: 20px;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }

        /* ── KPI CARDS ───────────────────────────────────────────────────── */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px 13px;
            text-align: center;
            vertical-align: top;
        }

        .kpi-card.success { border-color: #86efac; background: #f0fdf4; }
        .kpi-card.danger  { border-color: #fca5a5; background: #fef2f2; }
        .kpi-card.neutral { border-color: #e2e8f0; background: #f8fafc; }

        .kpi-label {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .kpi-card.success .kpi-label { color: #16a34a; }
        .kpi-card.danger  .kpi-label { color: #dc2626; }

        .kpi-value {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.1;
        }

        .kpi-card.success .kpi-value { color: #15803d; }
        .kpi-card.danger  .kpi-value { color: #b91c1c; }

        .kpi-sub {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* retention bar */
        .ret-bar-wrap {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 99px;
            margin-top: 5px;
            overflow: hidden;
        }

        .ret-bar { height: 4px; border-radius: 99px; }

        /* ── DETAIL TABLE ────────────────────────────────────────────────── */
        .table-container {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 6px;
        }

        table.detail { width: 100%; border-collapse: collapse; }

        table.detail thead tr { background: #f1f5f9; }

        table.detail thead th {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            padding: 9px 11px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        table.detail thead th.tc { text-align: center; }
        table.detail thead th.ts { color: #15803d; }
        table.detail thead th.td { color: #b91c1c; }

        table.detail tbody td {
            padding: 8px 11px;
            font-size: 10.5px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        table.detail tbody td.tc { text-align: center; }

        table.detail tbody tr:nth-child(even) td { background: #f8fafc; }
        table.detail tbody tr:last-child td      { border-bottom: none; }

        table.detail tfoot td {
            padding: 9px 11px;
            font-size: 10.5px;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
            background: #f1f5f9;
        }

        table.detail tfoot td.tc { text-align: center; }

        .fw  { font-weight: 700; }
        .ts  { color: #15803d; }
        .td  { color: #b91c1c; }
        .tc  { text-align: center; }
        .dim { color: #94a3b8; font-weight: 400; }

        /* ── FOOTER ──────────────────────────────────────────────────────── */
        .page-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }

        .page-footer p {
            font-size: 8px;
            color: #94a3b8;
            line-height: 1.65;
        }

        .page-footer .fl { font-weight: 700; color: #64748b; }
    </style>
</head>
<body>

@php
    $resumen      = $report['resumen']    ?? [];
    $rows         = $report['plataformas'] ?? [];
    $moneda       = $report['moneda']     ?? 'L';
    $retencion    = (float) ($resumen['retencion']       ?? 0);
    $neto         = (int)   ($resumen['neto']            ?? 0);
    $ingresosNetos = (float)($resumen['ingresos_netos']  ?? 0);

    $totalVendidos  = collect($rows)->sum('vendidos');
    $totalDejados   = collect($rows)->sum('dejados');
    $totalNeto      = $totalVendidos - $totalDejados;
    $totalIngV      = collect($rows)->sum('ingresos_vendidos');
    $totalIngP      = collect($rows)->sum('ingresos_perdidos');
    $totalIngN      = $totalIngV - $totalIngP;

    $retColor = $retencion >= 70 ? '#15803d' : ($retencion >= 40 ? '#d97706' : '#b91c1c');
    $netoIngClass  = $ingresosNetos >= 0 ? 'success' : 'danger';
    $netoClass     = $neto >= 0 ? 'success' : 'danger';
@endphp

{{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
<div class="page-header">
    <table>
        <tr>
            <td style="width:58%;vertical-align:top;">
                <h1>Informe de Ventas</h1>
                <span class="subtitle">Análisis de rendimiento comercial y proyección de ingresos</span>
            </td>
            <td style="vertical-align:top;" class="meta-right">
                <span class="badge">{{ $report['periodo_label'] ?? 'Período' }}</span><br>
                <span class="meta-range">{{ $report['start'] ?? '-' }} — {{ $report['end'] ?? '-' }}</span>
                Generado: {{ $report['generated_at'] ?? '-' }}<br>
                Usuario: {{ $report['generated_by'] ?? 'Sistema' }}
            </td>
        </tr>
    </table>
</div>

{{-- ── SECCIÓN: Clientes ────────────────────────────────────────────────── --}}
<div class="section-label">Resumen de clientes</div>
<table class="kpi-table">
    <tr>
        <td class="kpi-card neutral" style="width:20%;">
            <div class="kpi-label">Vendidos</div>
            <div class="kpi-value">{{ $resumen['vendidos'] ?? 0 }}</div>
            <div class="kpi-sub">nuevos en el período</div>
        </td>
        <td class="kpi-card danger" style="width:20%;">
            <div class="kpi-label">Perdidos</div>
            <div class="kpi-value">{{ $resumen['dejados'] ?? 0 }}</div>
            <div class="kpi-sub">no renovaron</div>
        </td>
        <td class="kpi-card {{ $netoClass }}" style="width:20%;">
            <div class="kpi-label">Balance neto</div>
            <div class="kpi-value">{{ $neto >= 0 ? '+' : '' }}{{ $neto }}</div>
            <div class="kpi-sub">diferencia del período</div>
        </td>
        <td class="kpi-card neutral" style="width:20%;">
            <div class="kpi-label">Clientes activos</div>
            <div class="kpi-value">{{ $resumen['activos'] ?? 0 }}</div>
            <div class="kpi-sub">con acceso vigente</div>
        </td>
        <td class="kpi-card neutral" style="width:20%;">
            <div class="kpi-label">Retención</div>
            <div class="kpi-value" style="color:{{ $retColor }};">{{ $retencion }}%</div>
            <div class="ret-bar-wrap">
                <div class="ret-bar" style="width:{{ min(abs($retencion), 100) }}%;background:{{ $retColor }};"></div>
            </div>
        </td>
    </tr>
</table>

{{-- ── SECCIÓN: Financiero ─────────────────────────────────────────────── --}}
<div class="section-label" style="margin-top:16px;">Análisis financiero estimado</div>
<table class="kpi-table">
    <tr>
        <td class="kpi-card success" style="width:33.33%;">
            <div class="kpi-label">Ingreso generado</div>
            <div class="kpi-value" style="font-size:16px;">{{ $moneda }} {{ number_format((float) ($resumen['ingresos_vendidos'] ?? 0), 2) }}</div>
            <div class="kpi-sub">por clientes vendidos</div>
        </td>
        <td class="kpi-card danger" style="width:33.33%;">
            <div class="kpi-label">Ingreso perdido</div>
            <div class="kpi-value" style="font-size:16px;">{{ $moneda }} {{ number_format((float) ($resumen['ingresos_perdidos'] ?? 0), 2) }}</div>
            <div class="kpi-sub">por clientes no renovados</div>
        </td>
        <td class="kpi-card {{ $netoIngClass }}" style="width:33.33%;">
            <div class="kpi-label">Balance económico neto</div>
            <div class="kpi-value" style="font-size:16px;">
                {{ $ingresosNetos >= 0 ? '+' : '' }}{{ $moneda }} {{ number_format($ingresosNetos, 2) }}
            </div>
            <div class="kpi-sub">resultado del período</div>
        </td>
    </tr>
</table>

{{-- ── SECCIÓN: Detalle por plataforma ────────────────────────────────── --}}
<div class="section-label" style="margin-top:16px;">Detalle por plataforma</div>
<div class="table-container">
    <table class="detail">
        <thead>
            <tr>
                <th>Plataforma</th>
                <th class="tc">Vendidos</th>
                <th class="tc">Perdidos</th>
                <th class="tc">Neto</th>
                <th class="tc">Ticket prom.</th>
                <th class="tc ts">Ing. generado</th>
                <th class="tc td">Ing. perdido</th>
                <th class="tc">Ing. neto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td class="fw">{{ $row['plataforma'] }}</td>
                <td class="tc">{{ $row['vendidos'] }}</td>
                <td class="tc td">{{ $row['dejados'] }}</td>
                <td class="tc fw {{ $row['neto'] >= 0 ? 'ts' : 'td' }}">
                    {{ $row['neto'] >= 0 ? '+' : '' }}{{ $row['neto'] }}
                </td>
                <td class="tc">{{ $moneda }} {{ number_format((float) ($row['ticket_promedio'] ?? 0), 2) }}</td>
                <td class="tc ts">{{ $moneda }} {{ number_format((float) ($row['ingresos_vendidos'] ?? 0), 2) }}</td>
                <td class="tc td">{{ $moneda }} {{ number_format((float) ($row['ingresos_perdidos'] ?? 0), 2) }}</td>
                <td class="tc fw {{ ((float) ($row['ingresos_netos'] ?? 0)) >= 0 ? 'ts' : 'td' }}">
                    {{ ((float) ($row['ingresos_netos'] ?? 0)) >= 0 ? '+' : '' }}{{ $moneda }} {{ number_format((float) ($row['ingresos_netos'] ?? 0), 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="tc dim" style="padding:16px;">Sin datos disponibles para este período.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 1)
        <tfoot>
            <tr>
                <td style="font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Total</td>
                <td class="tc">{{ $totalVendidos }}</td>
                <td class="tc td">{{ $totalDejados }}</td>
                <td class="tc fw {{ $totalNeto >= 0 ? 'ts' : 'td' }}">
                    {{ $totalNeto >= 0 ? '+' : '' }}{{ $totalNeto }}
                </td>
                <td class="tc dim">—</td>
                <td class="tc ts">{{ $moneda }} {{ number_format($totalIngV, 2) }}</td>
                <td class="tc td">{{ $moneda }} {{ number_format($totalIngP, 2) }}</td>
                <td class="tc fw {{ $totalIngN >= 0 ? 'ts' : 'td' }}">
                    {{ $totalIngN >= 0 ? '+' : '' }}{{ $moneda }} {{ number_format($totalIngN, 2) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- ── FOOTER ───────────────────────────────────────────────────────────── --}}
<div class="page-footer">
    <p>
        <span class="fl">Ticket base de referencia:</span>
        {{ $moneda }} {{ number_format((float) ($report['ticket_promedio'] ?? 0), 2) }}
        (aplica cuando no hay ticket específico configurado por plataforma).
        &nbsp;·&nbsp;
        <span class="fl">Metodología:</span>
        <em>"Vendidos"</em> considera clientes creados dentro del período.
        <em>"Perdidos"</em> considera clientes cuya fecha de caducidad cae dentro del período y ya venció a la fecha de generación.
        Los montos son estimados basados en el ticket promedio configurable para análisis comercial.
    </p>
</div>

</body>
</html>

