<?php

namespace App\Support;

use Filament\Forms;

class ClientFormSchema
{
    public static function identityFields(): array
    {
        return [
            Forms\Components\TextInput::make('cliente_nombre')
                ->label('Nombre cliente')
                ->required()
                ->maxLength(120),
            self::countryCodeField(),
            self::phoneField(),
        ];
    }

    public static function identityAndExpiryFields(): array
    {
        return [
            ...self::identityFields(),
            self::expiryField(),
        ];
    }

    public static function countryCodeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('cliente_codigo_pais')
            ->label('Código de país')
            ->options(self::countryDialCodeOptions())
            ->default('504')
            ->afterStateHydrated(function (Forms\Components\Select $component, $state): void {
                if (blank($state)) {
                    $component->state('504');
                }
            })
            ->required()
            ->native(true)
            ->dehydrateStateUsing(function ($state): string {
                $codigo = preg_replace('/\D+/', '', (string) $state);

                return $codigo !== '' ? $codigo : '504';
            });
    }

    public static function phoneField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('cliente_telefono')
            ->label('Número teléfono')
            ->required()
            ->tel()
            ->maxLength(30)
            ->placeholder('9876-5432')
            ->dehydrateStateUsing(fn ($state): ?string => preg_replace('/\D+/', '', (string) $state) ?: null);
    }

    public static function expiryField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('fecha_caducidad_cuenta')
            ->label('Fecha caducidad')
            ->required();
    }

    public static function countryDialCodeOptions(): array
    {
        return [
            '502' => 'Guatemala (+502)',
            '503' => 'El Salvador (+503)',
            '504' => 'Honduras (+504)',
            '505' => 'Nicaragua (+505)',
            '506' => 'Costa Rica (+506)',
            '507' => 'Panamá (+507)',
            '1' => 'Estados Unidos/Canadá (+1)',
            '34' => 'España (+34)',
            '52' => 'México (+52)',
            '57' => 'Colombia (+57)',
            '58' => 'Venezuela (+58)',
            '51' => 'Perú (+51)',
            '54' => 'Argentina (+54)',
            '56' => 'Chile (+56)',
            '593' => 'Ecuador (+593)',
            '591' => 'Bolivia (+591)',
            '595' => 'Paraguay (+595)',
            '598' => 'Uruguay (+598)',
            '55' => 'Brasil (+55)',
            '53' => 'Cuba (+53)',
            '809' => 'República Dominicana (+809)',
        ];
    }
}
