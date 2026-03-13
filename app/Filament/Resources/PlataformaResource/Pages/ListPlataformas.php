<?php

namespace App\Filament\Resources\PlataformaResource\Pages;

use App\Filament\Resources\PlataformaResource;
use Filament\Resources\Pages\ListRecords;

class ListPlataformas extends ListRecords
{
    protected static string $resource = PlataformaResource::class;

    public function getSubheading(): string
    {
        return 'Gestiona aquí tus plataformas';
    }
}
