<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlataformaResource;
use App\Models\Plataforma;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlataformasOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $hoy = Carbon::today();
        $limitePorVencer = Carbon::today()->addDays(5);
        $platformImagesDisk = (string) config('filesystems.platform_images_disk', 'public');

        $plataformas = Plataforma::query()
            ->withCount([
                'perfiles as clientes_count' => fn ($query) => $query
                    ->whereNotNull('cliente_nombre')
                    ->where('cliente_nombre', '!=', ''),
                'perfiles as por_vencer_count' => fn ($query) => $query
                    ->whereDate('fecha_caducidad_cuenta', '>=', $hoy)
                    ->whereDate('fecha_caducidad_cuenta', '<=', $limitePorVencer),
            ])
            ->orderByDesc('clientes_count')
            ->orderBy('nombre')
            ->get();

        $stats = [];

        foreach ($plataformas as $plataforma) {
            $color = 'success';

            if ($plataforma->por_vencer_count > 0) {
                $color = 'warning';
            }

            if ($plataforma->clientes_count === 0) {
                $color = 'gray';
            }

            $imagenUrl = filled($plataforma->imagen)
                ? Storage::disk($platformImagesDisk)->url((string) $plataforma->imagen)
                : asset('images/platform-placeholder.svg');

            $badgeVisual = '<img src="' . e($imagenUrl) . '" alt="' . e($plataforma->nombre) . '" class="h-9 w-9 rounded-md object-cover ring-1 ring-gray-300 dark:ring-white/20" />';

            $stats[] = Stat::make(
                "Clientes: {$plataforma->clientes_count}",
                new HtmlString('<span class="inline-flex items-center gap-2">' . $badgeVisual . '<span>' . e(Str::upper($plataforma->nombre)) . '</span></span>')
            )
                ->description("Por vencer (≤5 días): {$plataforma->por_vencer_count}")
                ->url(PlataformaResource::getUrl('clientes', ['record' => $plataforma]))
                ->color($color);
        }

        return $stats;
    }
}
