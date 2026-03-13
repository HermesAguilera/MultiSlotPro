<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cuenta;
use App\Models\CuentaPerfil;
use App\Models\CuentaReportada;
use App\Models\Perfil;
use App\Models\Plataforma;
use App\Support\ActionStyle;
use App\Support\ClientFormSchema;
use App\Support\ClientMessageBuilder;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClienteResource extends Resource
{
    protected static ?string $model = Perfil::class;

    protected static ?string $modelLabel = 'perfil';

    protected static ?string $pluralModelLabel = 'perfiles';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Streaming';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?int $navigationSort = 0;

    protected static function hasPermission(string $permission): bool
    {
        $user = auth()->user();

        return $user?->hasRole('administrador') || $user?->can($permission);
    }

    public static function canViewAny(): bool
    {
        return static::hasPermission('clientes.view')
            || static::hasPermission('clientes.create')
            || static::hasPermission('clientes.edit')
            || static::hasPermission('clientes.delete');
    }

    public static function canCreate(): bool
    {
        return static::hasPermission('clientes.create');
    }

    public static function canEdit($record): bool
    {
        return static::hasPermission('clientes.edit');
    }

    public static function canDelete($record): bool
    {
        return static::hasPermission('clientes.delete');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction('detalleCliente')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByRaw('LOWER(TRIM(cliente_nombre)) asc')
                ->orderByRaw('LOWER(TRIM(correo_cuenta)) asc')
                ->orderByRaw(static::getNombrePerfilOrderExpression())
                ->orderBy('id', 'asc'))
            ->columns([
                Tables\Columns\TextColumn::make('cliente_posicion')
                    ->label('#')
                    ->alignment('center')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('cliente_nombre')
                    ->label('Nombre del cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cliente_telefono')
                    ->label('Telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plataforma.nombre')
                    ->label('Plataforma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proveedor_nombre')
                    ->label('Proveedor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo_cuenta')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_perfil')
                    ->label('Perfil')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pin')
                    ->label('PIN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_caducidad_cuenta')
                    ->label('Fecha de caducidad')
                    ->date(),
                Tables\Columns\TextColumn::make('dias_restantes')
                    ->label('Quedan (dias)')
                    ->alignment('center')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')))
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : (string) $state),
            ])
            ->headerActions([
                ActionStyle::create(Tables\Actions\Action::make('agregarCliente'))
                    ->label('Agregar cliente')
                    ->visible(fn (): bool => static::hasPermission('clientes.create'))
                    ->form([
                        ...ClientFormSchema::identityAndExpiryFields(),
                        Forms\Components\Repeater::make('asignaciones')
                            ->label('Plataformas a asignar')
                            ->schema([
                                Forms\Components\Select::make('plataforma_id')
                                    ->label('Plataforma')
                                    ->options(fn (): array => static::getPlataformaOptions())
                                    ->native(true)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set): mixed => $set('cuenta_id', null))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Forms\Components\Select::make('cuenta_id')
                                    ->label('Cuenta')
                                    ->options(function (Get $get): array {
                                        return static::getCuentaOptionsForPlatform((int) ($get('plataforma_id') ?? 0));
                                    })
                                    ->native(true)
                                    ->required(),
                                Forms\Components\TextInput::make('cantidad_perfiles')
                                    ->label('Cantidad de perfiles')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Anadir otra plataforma')
                            ->itemLabel(fn (array $state): ?string => filled($state['plataforma_id'] ?? null)
                                ? (static::getPlataformaOptions()[(int) $state['plataforma_id']] ?? 'Plataforma')
                                : null)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $created = static::createClientForMultiplePlatforms($data);
                        } catch (\Throwable $exception) {
                            if (! ($exception instanceof ValidationException)) {
                                report($exception);
                            }

                            if ($exception instanceof ValidationException) {
                                $message = collect($exception->errors())->flatten()->first();
                            } elseif ($exception instanceof QueryException) {
                                $message = 'Error de base de datos: ' . Str::limit($exception->getMessage(), 200);
                            } else {
                                $message = get_class($exception) . ': ' . Str::limit($exception->getMessage(), 200);
                            }

                            Notification::make()
                                ->danger()
                                ->title('Error al crear cliente')
                                ->body($message ?: 'Error inesperado.')
                                ->persistent()
                                ->send();

                            throw (new Halt)->rollBackDatabaseTransaction();
                        }

                        Notification::make()
                            ->success()
                            ->title('Cliente agregado correctamente.')
                            ->body('Se crearon ' . $created . ' perfil(es).')
                            ->send();
                    }),
            ])
            ->actions([
                ActionStyle::whatsapp(Tables\Actions\Action::make('mensaje'))
                    ->label('WhatsApp')
                    ->url(function (Perfil $record): ?string {
                        $telefono = $record->getWhatsappPhoneNumber();

                        if (blank($telefono)) {
                            return null;
                        }

                        $mensaje = ClientMessageBuilder::buildDeliveryMessage($record);

                        return 'https://wa.me/' . $telefono . '?text=' . rawurlencode($mensaje);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\ActionGroup::make([
                    ActionStyle::view(Tables\Actions\Action::make('detalleCliente'))
                        ->label('Detalle del cliente')
                        ->modalHeading('Detalle del cliente')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(fn (Perfil $record) => view('filament.modals.detalle-cliente', [
                            'perfil' => $record->loadMissing(['plataforma', 'cuenta']),
                        ])),
                    ActionStyle::report(Tables\Actions\Action::make('reportarCuenta'))
                        ->label('Reportar cuenta')
                        ->modalHeading('Reportar cuenta')
                        ->modalDescription('Ingresa el motivo del reporte para enviarlo al modulo Cuentas Reportadas.')
                        ->modalSubmitActionLabel('Enviar')
                        ->form([
                            Forms\Components\Textarea::make('descripcion')
                                ->label('Descripcion')
                                ->rows(4)
                                ->required()
                                ->maxLength(1500),
                        ])
                        ->action(function (Perfil $record, array $data): void {
                            CuentaReportada::query()->create([
                                'perfil_id' => $record->id,
                                'cuenta_id' => $record->cuenta_id,
                                'plataforma_id' => $record->plataforma_id,
                                'cuenta' => (string) ($record->correo_cuenta ?? '-'),
                                'numero_perfil' => (string) ($record->nombre_perfil ?? '-'),
                                'descripcion' => trim((string) ($data['descripcion'] ?? '')),
                                'estado' => 'en_proceso',
                                'reportado_por' => auth()->id(),
                            ]);
                        })
                        ->successNotificationTitle('Cuenta reportada correctamente.'),
                    ActionStyle::edit(Tables\Actions\EditAction::make())
                        ->visible(fn (): bool => static::hasPermission('clientes.edit'))
                        ->successNotificationTitle('Cambios guardados correctamente.')
                        ->form([
                            ...ClientFormSchema::identityAndExpiryFields(),
                            Forms\Components\Select::make('plataforma_id')
                                ->label('Plataforma')
                                ->options(fn (): array => static::getPlataformaOptions())
                                ->native(true)
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('cuenta_id')
                                ->label('Cuenta')
                                ->options(function (Get $get): array {
                                    return static::getCuentaOptionsForPlatform((int) ($get('plataforma_id') ?? 0));
                                })
                                ->native(true)
                                ->required(),
                        ])
                        ->using(function (Perfil $record, array $data): Perfil {
                            try {
                                $payload = static::hydratePayloadWithCuentaData($data);

                                $record->update($payload);

                                return $record->refresh();
                            } catch (\Throwable $exception) {
                                if (! ($exception instanceof ValidationException)) {
                                    report($exception);
                                }

                                $message = $exception instanceof ValidationException
                                    ? collect($exception->errors())->flatten()->first()
                                    : 'No se pudieron guardar los cambios. Verifica la cuenta seleccionada e intenta de nuevo.';

                                Notification::make()
                                    ->danger()
                                    ->title('Error al actualizar cliente')
                                    ->body($message)
                                    ->persistent()
                                    ->send();

                                throw (new Halt)->rollBackDatabaseTransaction();
                            }
                        }),
                    ActionStyle::delete(Tables\Actions\DeleteAction::make())
                        ->visible(fn (): bool => static::hasPermission('clientes.delete'))
                        ->modalHeading('Confirmar eliminacion')
                        ->modalDescription('Esta accion no se puede deshacer.')
                        ->modalSubmitActionLabel('Eliminar')
                        ->successNotificationTitle('Registro eliminado correctamente.'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label(''),
            ])
            ->actionsColumnLabel('Accion')
            ->actionsAlignment('center');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['plataforma', 'cuenta']);
    }

    protected static function createClientForMultiplePlatforms(array $data): int
    {
        $asignaciones = collect($data['asignaciones'] ?? []);
        $created = 0;

        if ($asignaciones->isEmpty()) {
            throw ValidationException::withMessages([
                'asignaciones' => 'Agrega al menos una plataforma.',
            ]);
        }

        DB::transaction(function () use ($data, $asignaciones, &$created): void {
            foreach ($asignaciones as $index => $asignacion) {
                $cuentaId = (int) ($asignacion['cuenta_id'] ?? 0);
                $plataformaId = (int) ($asignacion['plataforma_id'] ?? 0);
                $cantidad = max((int) ($asignacion['cantidad_perfiles'] ?? 1), 1);

                $cuenta = Cuenta::query()
                    ->with(['plataforma:id,perfiles_por_cuenta'])
                    ->where('id', $cuentaId)
                    ->where('plataforma_id', $plataformaId)
                    ->lockForUpdate()
                    ->first();

                if (! $cuenta) {
                    throw ValidationException::withMessages([
                        'asignaciones' => 'La cuenta seleccionada no pertenece a la plataforma indicada.',
                    ]);
                }

                $slots = static::resolveNextAvailableSlotsForCuenta($cuenta, $cantidad, $index);

                foreach ($slots as $slot) {
                    Perfil::query()->create([
                        'plataforma_id' => $cuenta->plataforma_id,
                        'cuenta_id' => $cuenta->id,
                        'cliente_nombre' => trim((string) ($data['cliente_nombre'] ?? '')),
                        'cliente_codigo_pais' => trim((string) ($data['cliente_codigo_pais'] ?? '504')),
                        'cliente_telefono' => trim((string) ($data['cliente_telefono'] ?? '')),
                        'proveedor_nombre' => (string) ($cuenta->proveedor ?? ''),
                        'correo_cuenta' => Str::lower(trim((string) ($cuenta->correo ?? ''))),
                        'contrasena_cuenta' => (string) ($cuenta->contrasena ?? ''),
                        'fecha_inicio' => $cuenta->fecha_inicio?->toDateString(),
                        'fecha_corte' => $cuenta->fecha_corte?->toDateString(),
                        'fecha_caducidad_cuenta' => $data['fecha_caducidad_cuenta'] ?? null,
                        'nombre_perfil' => (string) $slot,
                        'pin' => static::getPinForCuentaSlot((int) $cuenta->id, (int) $slot),
                    ]);

                    $created++;
                }
            }
        });

        return $created;
    }

    protected static function resolveNextAvailableSlotsForCuenta(Cuenta $cuenta, int $cantidad, int $itemIndex): array
    {
        $limite = static::getCuentaProfilesLimit($cuenta);

        $correoCuenta = Str::lower(trim((string) ($cuenta->correo ?? '')));

        $ocupadosQuery = Perfil::query()
            ->where('plataforma_id', $cuenta->plataforma_id)
            ->lockForUpdate()
            ->when(
                $correoCuenta !== '',
                fn ($query) => $query->where('correo_cuenta', $correoCuenta),
                fn ($query) => $query->where('cuenta_id', $cuenta->id),
            );

        $ocupados = $ocupadosQuery
            ->pluck('nombre_perfil')
            ->map(fn ($perfil): int => (int) $perfil)
            ->filter(fn (int $slot): bool => $slot > 0)
            ->unique()
            ->values();

        $slots = [];

        for ($slot = 1; $slot <= $limite; $slot++) {
            if ($ocupados->contains($slot)) {
                continue;
            }

            $slots[] = $slot;

            if (count($slots) === $cantidad) {
                return $slots;
            }
        }

        $disponibles = max($limite - $ocupados->count(), 0);

        throw ValidationException::withMessages([
            'asignaciones' => 'Solo hay ' . $disponibles . ' perfiles disponibles para esta cuenta.',
            'asignaciones.' . $itemIndex . '.cantidad_perfiles' => 'Solo hay ' . $disponibles . ' perfiles disponibles para esta cuenta.',
        ]);
    }

    protected static function getCuentaProfilesLimit(Cuenta $cuenta): int
    {
        $configurados = CuentaPerfil::query()
            ->where('cuenta_id', $cuenta->id)
            ->count();

        if ($configurados > 0) {
            return $configurados;
        }

        return max((int) ($cuenta->plataforma?->perfiles_por_cuenta ?: 5), 1);
    }

    protected static function getPinForCuentaSlot(int $cuentaId, int $slot): ?string
    {
        $pin = CuentaPerfil::query()
            ->where('cuenta_id', $cuentaId)
            ->where('numero_perfil', $slot)
            ->value('pin');

        return blank($pin) ? null : (string) $pin;
    }

    protected static function hydratePayloadWithCuentaData(array $data): array
    {
        $cuentaId = (int) ($data['cuenta_id'] ?? 0);
        $plataformaId = (int) ($data['plataforma_id'] ?? 0);

        $cuenta = Cuenta::query()
            ->where('id', $cuentaId)
            ->where('plataforma_id', $plataformaId)
            ->first();

        if (! $cuenta) {
            throw ValidationException::withMessages([
                'cuenta_id' => 'Selecciona una cuenta valida para la plataforma indicada.',
            ]);
        }

        return [
            'plataforma_id' => $cuenta->plataforma_id,
            'cuenta_id' => $cuenta->id,
            'cliente_nombre' => trim((string) ($data['cliente_nombre'] ?? '')),
            'cliente_codigo_pais' => trim((string) ($data['cliente_codigo_pais'] ?? '504')),
            'cliente_telefono' => trim((string) ($data['cliente_telefono'] ?? '')),
            'fecha_caducidad_cuenta' => $data['fecha_caducidad_cuenta'] ?? null,
            'proveedor_nombre' => (string) ($cuenta->proveedor ?? ''),
            'correo_cuenta' => Str::lower(trim((string) ($cuenta->correo ?? ''))),
            'contrasena_cuenta' => (string) ($cuenta->contrasena ?? ''),
            'fecha_inicio' => $cuenta->fecha_inicio?->toDateString(),
            'fecha_corte' => $cuenta->fecha_corte?->toDateString(),
        ];
    }

    protected static function getPlataformaOptions(): array
    {
        return Plataforma::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    protected static function getCuentaOptionsForPlatform(int $plataformaId): array
    {
        if ($plataformaId <= 0) {
            return [];
        }

        return Cuenta::query()
            ->where('plataforma_id', $plataformaId)
            ->orderByRaw('LOWER(correo)')
            ->get()
            ->mapWithKeys(function (Cuenta $cuenta): array {
                return [
                    $cuenta->id => $cuenta->correo . ' (' . $cuenta->proveedor . ')',
                ];
            })
            ->all();
    }

    protected static function getNombrePerfilOrderExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "COALESCE(NULLIF(regexp_replace(nombre_perfil, '[^0-9]', '', 'g'), ''), '0')::int asc";
        }

        return 'CAST(nombre_perfil AS UNSIGNED) asc';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
        ];
    }
}
