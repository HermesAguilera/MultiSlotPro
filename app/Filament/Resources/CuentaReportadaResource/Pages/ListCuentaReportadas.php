<?php

namespace App\Filament\Resources\CuentaReportadaResource\Pages;

use App\Filament\Resources\CuentaReportadaResource;
use Filament\Resources\Pages\ListRecords;

class ListCuentaReportadas extends ListRecords
{
    protected static string $resource = CuentaReportadaResource::class;

    public function getTitle(): string
    {
        return 'Cuentas Reportadas';
    }

    public function getSubheading(): string
    {
        return 'Revisa y da seguimiento a los reportes de cuentas';
    }
}
