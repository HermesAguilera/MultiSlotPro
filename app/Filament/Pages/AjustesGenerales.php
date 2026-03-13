<?php

namespace App\Filament\Pages;

use App\Support\ClientMessageBuilder;
use App\Support\UserPreferenceState;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class AjustesGenerales extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Ajustes';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'ajustes';

    protected static string $view = 'filament.pages.ajustes-generales';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $user->preference()->firstOrCreate(
                ['user_id' => $user->id],
                UserPreferenceState::defaults(),
            );
        }

        $this->form->fill(UserPreferenceState::forUser($user));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Apariencia')
                    ->schema([
                        Forms\Components\Select::make('theme_mode')
                            ->label('Tema')
                            ->required()
                            ->options([
                                'system' => 'Sistema',
                                'light' => 'Claro',
                                'dark' => 'Oscuro',
                            ]),
                        Forms\Components\Toggle::make('colorize_accounts')
                            ->label('Mostrar cuentas por color')
                            ->helperText('Resalta visualmente las cuentas para identificar agrupaciones más rápido.'),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Comodidad')
                    ->schema([
                        Forms\Components\Toggle::make('dense_interface')
                            ->label('Interfaz compacta')
                            ->helperText('Reduce espacios verticales para mostrar más información en pantalla.'),
                        Forms\Components\Toggle::make('reduced_motion')
                            ->label('Reducir animaciones')
                            ->helperText('Disminuye transiciones para una experiencia más estable y cómoda.'),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Mensajes a clientes')
                    ->description('Personaliza los mensajes de WhatsApp sin eliminar las variables entre llaves {}.')
                    ->visible(fn (): bool => UserPreferenceState::supportsMessageTemplates())
                    ->schema([
                        Forms\Components\Textarea::make('delivery_message_template')
                            ->label('Plantilla: entrega de acceso')
                            ->rows(8)
                            ->autosize()
                            ->helperText('Variables obligatorias: {cliente_nombre}, {cliente_telefono}, {plataforma}, {correo}, {contrasena}, {numero_perfil}, {pin}, {fecha_inicio}, {fecha_vencimiento}'),
                        Forms\Components\Textarea::make('expiry_message_template')
                            ->label('Plantilla: recordatorio de vencimiento')
                            ->rows(7)
                            ->autosize()
                            ->helperText('Variables obligatorias: {cliente_nombre}, {plataforma}, {correo}, {tiempo_restante}'),
                        Forms\Components\Textarea::make('expiry_today_message_template')
                            ->label('Plantilla: vence hoy')
                            ->rows(7)
                            ->autosize()
                            ->helperText('Variables obligatorias: {cliente_nombre}, {plataforma}, {correo}'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        if (! UserPreferenceState::supportsMessageTemplates()) {
            return [];
        }

        return [
            Action::make('restoreMessageTemplates')
                ->label('Restaurar plantillas por defecto')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Restaurar plantillas')
                ->modalDescription('Se restauraran las plantillas de mensajes a sus valores predeterminados. Los ajustes de apariencia y comodidad no se modifican.')
                ->action(function (): void {
                    $current = $this->data ?? [];
                    $defaults = UserPreferenceState::defaults();

                    $current['delivery_message_template'] = (string) $defaults['delivery_message_template'];
                    $current['expiry_message_template'] = (string) $defaults['expiry_message_template'];
                    $current['expiry_today_message_template'] = (string) $defaults['expiry_today_message_template'];

                    $this->data = $current;
                    $this->form->fill($current);

                    Notification::make()
                        ->title('Plantillas restauradas. Presiona Guardar ajustes para aplicar los cambios.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function save(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $state = $this->data ?? [];
        $defaults = UserPreferenceState::defaults();
        $supportsTemplates = UserPreferenceState::supportsMessageTemplates();

        $validatedTemplates = [];

        if ($supportsTemplates) {
            $templateFields = [
                'delivery_message_template',
                'expiry_message_template',
                'expiry_today_message_template',
            ];

            foreach ($templateFields as $field) {
                $template = ClientMessageBuilder::sanitizeTemplate($field, (string) ($state[$field] ?? $defaults[$field]));
                $missingTokens = ClientMessageBuilder::missingRequiredTokens($template, $field);

                if ($missingTokens !== []) {
                    throw ValidationException::withMessages([
                        $field => 'Faltan variables obligatorias: ' . implode(', ', $missingTokens),
                    ]);
                }

                $validatedTemplates[$field] = $template;
            }
        }

        $payload = [
            'theme_mode' => (string) ($state['theme_mode'] ?? $defaults['theme_mode']),
            'colorize_accounts' => (bool) ($state['colorize_accounts'] ?? false),
            'dense_interface' => (bool) ($state['dense_interface'] ?? false),
            'reduced_motion' => (bool) ($state['reduced_motion'] ?? false),
        ];

        if ($supportsTemplates) {
            $payload['delivery_message_template'] = $validatedTemplates['delivery_message_template'];
            $payload['expiry_message_template'] = $validatedTemplates['expiry_message_template'];
            $payload['expiry_today_message_template'] = $validatedTemplates['expiry_today_message_template'];
        }

        $payload = UserPreferenceState::filterPersistable($payload);

        $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            $payload,
        );

        $user->unsetRelation('preference');

        Notification::make()
            ->title('Ajustes guardados correctamente.')
            ->success()
            ->send();
    }
}
