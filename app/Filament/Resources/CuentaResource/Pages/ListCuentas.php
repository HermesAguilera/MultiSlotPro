<?php

namespace App\Filament\Resources\CuentaResource\Pages;

use App\Filament\Resources\CuentaResource;
use Filament\Resources\Pages\ListRecords;

class ListCuentas extends ListRecords
{
    protected static string $resource = CuentaResource::class;

    public function getSubheading(): string
    {
        return 'Gestiona aquí tus cuentas y su configuración de perfiles';
    }
}
