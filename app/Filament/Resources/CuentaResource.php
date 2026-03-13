<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuentaResource\Pages;
use App\Models\Cuenta;
use App\Models\Plataforma;
use App\Support\ActionStyle;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CuentaResource extends Resource
{
    protected static ?string $model = Cuenta::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Streaming';
    protected static ?string $navigationLabel = 'Cuentas';
    protected static ?int $navigationSort = 2;

    protected static function hasPermission(string $permission): bool
    {
        $user = auth()->user();

        return $user?->hasRole('administrador') || $user?->can($permission);
    }

    public static function canViewAny(): bool
    {
        return static::hasPermission('cuentas.view')
            || static::hasPermission('cuentas.create')
            || static::hasPermission('cuentas.edit')
            || static::hasPermission('cuentas.delete');
    }

    public static function canCreate(): bool
    {
        return static::hasPermission('cuentas.create');
    }

    public static function canEdit($record): bool
    {
        return static::hasPermission('cuentas.edit');
    }

    public static function canDelete($record): bool
    {
        return static::hasPermission('cuentas.delete');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('plataforma_id')
                ->label('Plataforma')
                ->relationship('plataforma', 'nombre')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    $cantidad = static::resolveProfilesLimitForPlatform((int) $state);
                    $actual = $get('configuracionPerfiles');

                    $set('configuracionPerfiles', static::buildProfileConfigState($cantidad, is_array($actual) ? $actual : []));
                })
                ->required(),
            Forms\Components\TextInput::make('proveedor')
                ->label('Proveedor')
                ->required()
                ->maxLength(120),
            Forms\Components\TextInput::make('correo')
                ->label('Correo')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('contrasena')
                ->label('Contraseña')
                ->password()
                ->revealable()
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('fecha_inicio')
                ->label('Fecha de inicio')
                ->required(),
            Forms\Components\DatePicker::make('fecha_corte')
                ->label('Fecha de corte')
                ->required(),
            Forms\Components\Placeholder::make('perfiles_por_cuenta_info')
                ->label('Perfiles por cuenta')
                ->content(function (Get $get, ?Cuenta $record): string {
                    $plataformaId = (int) ($get('plataforma_id') ?: $record?->plataforma_id ?: 0);

                    return (string) static::resolveProfilesLimitForPlatform($plataformaId);
                })
                ->helperText('Esta cantidad se define en la plataforma y no se edita aqui'),
            Forms\Components\Repeater::make('configuracionPerfiles')
                ->label('Perfiles de la Cuenta')
                ->relationship('configuracionPerfiles')
                ->afterStateHydrated(function (Get $get, Set $set, ?Cuenta $record, $state): void {
                    $plataformaId = (int) ($get('plataforma_id') ?: $record?->plataforma_id ?: 0);
                    $cantidad = static::resolveProfilesLimitForPlatform($plataformaId);

                    $actual = is_array($state)
                        ? $state
                        : ($record?->configuracionPerfiles?->toArray() ?: []);

                    $set('configuracionPerfiles', static::buildProfileConfigState($cantidad, $actual));
                })
                ->schema([
                    Forms\Components\Hidden::make('numero_perfil')
                        ->required()
                        ->dehydrated(),
                    Forms\Components\Placeholder::make('perfil_label')
                        ->label('Perfil')
                        ->content(fn (Get $get): string => 'Perfil ' . ((int) ($get('numero_perfil') ?: 0))),
                    Forms\Components\TextInput::make('pin')
                        ->label('PIN')
                        ->maxLength(20),
                ])
                ->columns(2)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
        ]);
    }

    protected static function buildProfileConfigState(int $cantidad, array $currentState): array
    {
        $cantidad = max($cantidad, 1);

        $rowsBySlot = collect($currentState)
            ->mapWithKeys(function ($item): array {
                $slot = (int) ($item['numero_perfil'] ?? 0);

                if ($slot <= 0) {
                    return [];
                }

                return [$slot => [
                    'id' => $item['id'] ?? null,
                    'pin' => $item['pin'] ?? null,
                ]];
            });

        $rows = [];

        for ($slot = 1; $slot <= $cantidad; $slot++) {
            $existing = $rowsBySlot->get($slot, []);

            $rows[] = [
                'id' => $existing['id'] ?? null,
                'numero_perfil' => $slot,
                'pin' => $existing['pin'] ?? null,
            ];
        }

        return $rows;
    }

    protected static function resolveProfilesLimitForPlatform(int $plataformaId): int
    {
        if ($plataformaId <= 0) {
            return 5;
        }

        $limite = (int) (Plataforma::query()->whereKey($plataformaId)->value('perfiles_por_cuenta') ?: 5);

        return max($limite, 1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ActionStyle::create(Tables\Actions\CreateAction::make())
                    ->label('Agregar cuenta'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('proveedor')
                    ->label('Proveedor')
                    ->action(fn (Cuenta $record, $livewire) => $livewire->mountTableAction('ver', (string) $record->getKey()))
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plataforma.nombre')
                    ->label('Plataforma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha inicio')
                    ->date(),
                Tables\Columns\TextColumn::make('fecha_corte')
                    ->label('Fecha corte')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ActionStyle::view(Tables\Actions\Action::make('ver'))
                        ->label('Ver')
                        ->modalHeading('Detalle de la cuenta')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(fn (Cuenta $record) => view('filament.modals.detalle-cuenta', [
                            'cuenta' => $record->loadMissing('plataforma'),
                        ])),
                    ActionStyle::edit(Tables\Actions\EditAction::make()),
                    ActionStyle::delete(Tables\Actions\DeleteAction::make())
                        ->modalHeading('Confirmar eliminación')
                        ->modalDescription('Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Eliminar')
                        ->successNotificationTitle('Registro eliminado correctamente.'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label(''),
            ])
                    ->actionsColumnLabel('Acción')
                    ->actionsAlignment('center')
            ->bulkActions([
                ActionStyle::delete(Tables\Actions\DeleteBulkAction::make())
                    ->modalHeading('Confirmar eliminación masiva')
                    ->modalDescription('Se eliminarán los registros seleccionados y esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Eliminar seleccionados')
                    ->successNotificationTitle('Registros eliminados correctamente.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuentas::route('/'),
            'create' => Pages\CreateCuenta::route('/create'),
            'edit' => Pages\EditCuenta::route('/{record}/edit'),
        ];
    }
}
