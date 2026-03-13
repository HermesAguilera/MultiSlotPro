<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Formulario de parámetros --}}
        <form wire:submit.prevent="generateReport" class="space-y-4">
            {{ $this->form }}
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <x-filament::button
                    type="submit"
                    icon="heroicon-o-arrow-path"
                    wire:loading.attr="disabled"
                    wire:target="generateReport"
                >
                    <span wire:loading.remove wire:target="generateReport">Generar informe</span>
                    <span wire:loading wire:target="generateReport">Generando...</span>
                </x-filament::button>
                <x-filament::button
                    color="success"
                    icon="heroicon-o-document-arrow-down"
                    wire:click="downloadPdf"
                    wire:loading.attr="disabled"
                    wire:target="downloadPdf"
                >
                    <span wire:loading.remove wire:target="downloadPdf">Descargar PDF</span>
                    <span wire:loading wire:target="downloadPdf">Preparando PDF...</span>
                </x-filament::button>
            </div>
        </form>

        @php
            $report  = $this->report;
            $resumen = $report['resumen'] ?? [];
            $rows    = $report['plataformas'] ?? [];
            $moneda  = $report['moneda'] ?? 'L';
            $neto         = (int)   ($resumen['neto']            ?? 0);
            $retencion    = (float) ($resumen['retencion']       ?? 0);
            $ingresosNetos = (float)($resumen['ingresos_netos']  ?? 0);
        @endphp

        @if(!empty($report))

        {{-- Barra de período --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800">
            <span class="font-bold text-gray-800 dark:text-gray-100">{{ $report['periodo_label'] ?? '-' }}</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $report['start'] ?? '-' }} — {{ $report['end'] ?? '-' }}</span>
            <span class="hidden sm:inline text-gray-300 dark:text-gray-600">|</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $report['generated_at'] ?? '-' }}</span>
            <span class="hidden sm:inline text-gray-300 dark:text-gray-600">|</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $report['generated_by'] ?? 'Sistema' }}</span>
        </div>

        {{-- ── Sección: Clientes ───────────────────────────────────────────── --}}
        <div>
            <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                Resumen de clientes
            </p>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">

                {{-- Vendidos --}}
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Vendidos</p>
                        <span class="ml-2 shrink-0 rounded-full bg-primary-50 p-1 dark:bg-primary-900/30">
                            <x-heroicon-o-user-plus class="h-3 w-3 text-primary-600 dark:text-primary-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-gray-900 dark:text-white">{{ $resumen['vendidos'] ?? 0 }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">nuevos en el período</p>
                </div>

                {{-- Perdidos --}}
                <div class="rounded-lg border border-danger-200 bg-danger-50/30 px-3 py-2.5 shadow-sm dark:border-danger-800/40 dark:bg-danger-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-danger-500 dark:text-danger-400">Perdidos</p>
                        <span class="ml-2 shrink-0 rounded-full bg-danger-100 p-1 dark:bg-danger-900/40">
                            <x-heroicon-o-user-minus class="h-3 w-3 text-danger-600 dark:text-danger-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-danger-600 dark:text-danger-400">{{ $resumen['dejados'] ?? 0 }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">no renovaron</p>
                </div>

                {{-- Balance neto --}}
                @if($neto >= 0)
                <div class="rounded-lg border border-success-200 bg-success-50/30 px-3 py-2.5 shadow-sm dark:border-success-800/40 dark:bg-success-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-success-600 dark:text-success-400">Balance neto</p>
                        <span class="ml-2 shrink-0 rounded-full bg-success-100 p-1 dark:bg-success-900/40">
                            <x-heroicon-o-arrow-trending-up class="h-3 w-3 text-success-600 dark:text-success-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-success-600 dark:text-success-400">+{{ $neto }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">diferencia del período</p>
                </div>
                @else
                <div class="rounded-lg border border-danger-200 bg-danger-50/30 px-3 py-2.5 shadow-sm dark:border-danger-800/40 dark:bg-danger-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-danger-500 dark:text-danger-400">Balance neto</p>
                        <span class="ml-2 shrink-0 rounded-full bg-danger-100 p-1 dark:bg-danger-900/40">
                            <x-heroicon-o-arrow-trending-down class="h-3 w-3 text-danger-600 dark:text-danger-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-danger-600 dark:text-danger-400">{{ $neto }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">diferencia del período</p>
                </div>
                @endif

                {{-- Activos --}}
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Clientes activos</p>
                        <span class="ml-2 shrink-0 rounded-full bg-success-50 p-1 dark:bg-success-900/30">
                            <x-heroicon-o-users class="h-3 w-3 text-success-600 dark:text-success-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-gray-900 dark:text-white">{{ $resumen['activos'] ?? 0 }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">con acceso vigente</p>
                </div>

                {{-- Retención --}}
                @php
                    $retClass = $retencion >= 70 ? 'text-success-600 dark:text-success-400' : ($retencion >= 40 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400');
                    $retBg    = $retencion >= 70 ? 'bg-success-50 dark:bg-success-900/30' : ($retencion >= 40 ? 'bg-warning-50 dark:bg-warning-900/30' : 'bg-danger-50 dark:bg-danger-900/30');
                    $retBar   = $retencion >= 70 ? 'bg-success-500' : ($retencion >= 40 ? 'bg-warning-500' : 'bg-danger-500');
                    $retLbl   = $retencion >= 70 ? 'text-success-500 dark:text-success-400' : ($retencion >= 40 ? 'text-warning-500 dark:text-warning-400' : 'text-danger-500 dark:text-danger-400');
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider {{ $retLbl }}">Retención</p>
                        <span class="ml-2 shrink-0 rounded-full p-1 {{ $retBg }}">
                            <x-heroicon-o-chart-bar class="h-3 w-3 {{ $retClass }}" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold {{ $retClass }}">{{ $retencion }}%</p>
                    <div class="mt-1 h-1 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-1 rounded-full {{ $retBar }}" style="width:{{ min(abs($retencion),100) }}%"></div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Sección: Ingresos ───────────────────────────────────────────── --}}
        <div>
            <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                Análisis financiero estimado
            </p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                {{-- Ingreso generado --}}
                <div class="rounded-lg border border-success-200 bg-success-50/30 px-3 py-2.5 shadow-sm dark:border-success-800/40 dark:bg-success-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-success-600 dark:text-success-400">Ingreso generado</p>
                        <span class="ml-2 shrink-0 rounded-full bg-success-100 p-1 dark:bg-success-900/40">
                            <x-heroicon-o-arrow-up-circle class="h-3 w-3 text-success-600 dark:text-success-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-success-700 dark:text-success-400">{{ $moneda }} {{ number_format((float) ($resumen['ingresos_vendidos'] ?? 0), 2) }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">por clientes vendidos</p>
                </div>

                {{-- Ingreso perdido --}}
                <div class="rounded-lg border border-danger-200 bg-danger-50/30 px-3 py-2.5 shadow-sm dark:border-danger-800/40 dark:bg-danger-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-danger-500 dark:text-danger-400">Ingreso perdido</p>
                        <span class="ml-2 shrink-0 rounded-full bg-danger-100 p-1 dark:bg-danger-900/40">
                            <x-heroicon-o-arrow-down-circle class="h-3 w-3 text-danger-600 dark:text-danger-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-danger-700 dark:text-danger-400">{{ $moneda }} {{ number_format((float) ($resumen['ingresos_perdidos'] ?? 0), 2) }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">por clientes no renovados</p>
                </div>

                {{-- Neto financiero --}}
                @if($ingresosNetos >= 0)
                <div class="rounded-lg border border-success-200 bg-success-50/30 px-3 py-2.5 shadow-sm dark:border-success-800/40 dark:bg-success-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-success-600 dark:text-success-400">Balance económico</p>
                        <span class="ml-2 shrink-0 rounded-full bg-success-100 p-1 dark:bg-success-900/40">
                            <x-heroicon-o-banknotes class="h-3 w-3 text-success-600 dark:text-success-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-success-700 dark:text-success-400">+{{ $moneda }} {{ number_format($ingresosNetos, 2) }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">resultado del período</p>
                </div>
                @else
                <div class="rounded-lg border border-danger-200 bg-danger-50/30 px-3 py-2.5 shadow-sm dark:border-danger-800/40 dark:bg-danger-900/10">
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-danger-500 dark:text-danger-400">Balance económico</p>
                        <span class="ml-2 shrink-0 rounded-full bg-danger-100 p-1 dark:bg-danger-900/40">
                            <x-heroicon-o-banknotes class="h-3 w-3 text-danger-600 dark:text-danger-400" />
                        </span>
                    </div>
                    <p class="mt-0.5 text-xl font-extrabold text-danger-700 dark:text-danger-400">{{ $moneda }} {{ number_format($ingresosNetos, 2) }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">resultado del período</p>
                </div>
                @endif

            </div>
        </div>

        {{-- ── Sección: Tabla de detalle ───────────────────────────────────── --}}
        @php
            $totalVendidos  = collect($rows)->sum('vendidos');
            $totalDejados   = collect($rows)->sum('dejados');
            $totalNeto      = $totalVendidos - $totalDejados;
            $totalIngresosV = collect($rows)->sum('ingresos_vendidos');
            $totalIngresosP = collect($rows)->sum('ingresos_perdidos');
            $totalIngresosN = $totalIngresosV - $totalIngresosP;
        @endphp

        <x-filament::section>
            <x-slot name="heading">Detalle por plataforma</x-slot>
            <x-slot name="description">
                Período <strong>{{ $report['periodo_label'] ?? '-' }}</strong>:
                {{ $report['start'] ?? '-' }} — {{ $report['end'] ?? '-' }}
                &nbsp;·&nbsp;
                Ticket base de referencia: <strong>{{ $moneda }} {{ number_format((float) ($report['ticket_promedio'] ?? 0), 2) }}</strong>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 pl-1 pr-4 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Plataforma</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vendidos</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Perdidos</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Neto</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ticket prom.</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-success-600 dark:text-success-400">Ing. generado</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-danger-500 dark:text-danger-400">Ing. perdido</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ing. neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        <tr class="border-b border-gray-100 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/40 {{ $i % 2 === 1 ? 'bg-gray-50/60 dark:bg-gray-900/20' : '' }}">
                            <td class="py-3 pl-1 pr-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['plataforma'] }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">{{ $row['vendidos'] }}</td>
                            <td class="px-4 py-3 text-center text-sm font-medium text-danger-600 dark:text-danger-400">{{ $row['dejados'] }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold {{ $row['neto'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                {{ $row['neto'] >= 0 ? '+' : '' }}{{ $row['neto'] }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{{ $moneda }} {{ number_format((float) ($row['ticket_promedio'] ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-center text-sm font-medium text-success-600 dark:text-success-400">{{ $moneda }} {{ number_format((float) ($row['ingresos_vendidos'] ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-center text-sm font-medium text-danger-600 dark:text-danger-400">{{ $moneda }} {{ number_format((float) ($row['ingresos_perdidos'] ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold {{ ((float) ($row['ingresos_netos'] ?? 0)) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                {{ ((float) ($row['ingresos_netos'] ?? 0)) >= 0 ? '+' : '' }}{{ $moneda }} {{ number_format((float) ($row['ingresos_netos'] ?? 0), 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                No hay datos disponibles para el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 1)
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800/60">
                            <td class="py-3 pl-1 pr-4 text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-gray-800 dark:text-gray-100">{{ $totalVendidos }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-danger-600 dark:text-danger-400">{{ $totalDejados }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold {{ $totalNeto >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                {{ $totalNeto >= 0 ? '+' : '' }}{{ $totalNeto }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-400">—</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-success-600 dark:text-success-400">{{ $moneda }} {{ number_format($totalIngresosV, 2) }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-danger-600 dark:text-danger-400">{{ $moneda }} {{ number_format($totalIngresosP, 2) }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold {{ $totalIngresosN >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                {{ $totalIngresosN >= 0 ? '+' : '' }}{{ $moneda }} {{ number_format($totalIngresosN, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- Nota metodológica --}}
            <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/40">
                <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-600 dark:text-gray-300">Nota metodológica —</span>
                    <em>"Vendidos"</em> considera clientes creados dentro del período.
                    <em>"Perdidos"</em> considera clientes cuya fecha de caducidad cae dentro del período y ya venció a la fecha de generación.
                    Los montos son <strong>estimados</strong> y se calculan con base al ticket promedio configurable por plataforma.
                </p>
            </div>
        </x-filament::section>

        @endif
    </div>
</x-filament-panels::page>
