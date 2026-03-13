<?php

namespace App\Support;

use App\Models\Perfil;
use Illuminate\Support\Arr;

class ClientMessageBuilder
{
    private const E_WAVE = "\xF0\x9F\x91\x8B";
    private const E_EMAIL = "\xF0\x9F\x93\xA7";
    private const E_KEY = "\xF0\x9F\x94\x91";
    private const E_USER = "\xF0\x9F\x91\xA4";
    private const E_PIN = "\xF0\x9F\x93\x8C";
    private const E_CAL_START = "\xF0\x9F\x97\x93\xEF\xB8\x8F";
    private const E_CAL_CUT = "\xF0\x9F\x93\x85";
    private const E_POPCORN = "\xF0\x9F\x8D\xBF";
    private const E_SPARKLES = "\xE2\x9C\xA8";
    private const E_TV = "\xF0\x9F\x93\xBA";
    private const E_HOURGLASS = "\xE2\x8F\xB3";
    private const E_CLAPPER = "\xF0\x9F\x8E\xAC";
    private const E_CARD = "\xF0\x9F\x92\xB3";
    private const E_WARNING = "\xE2\x9A\xA0\xEF\xB8\x8F";

    private const DELIVERY_TEMPLATE_FIELD = 'delivery_message_template';
    private const EXPIRY_TEMPLATE_FIELD = 'expiry_message_template';
    private const EXPIRY_TODAY_TEMPLATE_FIELD = 'expiry_today_message_template';

    public static function templateDefinitions(): array
    {
        return [
            self::DELIVERY_TEMPLATE_FIELD => [
                'label' => 'Mensaje de entrega',
                'required_tokens' => [
                    '{cliente_nombre}',
                    '{cliente_telefono}',
                    '{plataforma}',
                    '{correo}',
                    '{contrasena}',
                    '{numero_perfil}',
                    '{pin}',
                    '{fecha_inicio}',
                    '{fecha_vencimiento}',
                ],
                'default' => self::defaultDeliveryTemplate(),
            ],
            self::EXPIRY_TEMPLATE_FIELD => [
                'label' => 'Mensaje de recordatorio de vencimiento',
                'required_tokens' => [
                    '{cliente_nombre}',
                    '{plataforma}',
                    '{correo}',
                    '{tiempo_restante}',
                ],
                'default' => self::defaultExpiryTemplate(),
            ],
            self::EXPIRY_TODAY_TEMPLATE_FIELD => [
                'label' => 'Mensaje de vence hoy',
                'required_tokens' => [
                    '{cliente_nombre}',
                    '{plataforma}',
                    '{correo}',
                ],
                'default' => self::defaultExpiryTodayTemplate(),
            ],
        ];
    }

    public static function defaultTemplateFor(string $field): string
    {
        $definition = Arr::get(self::templateDefinitions(), $field);

        return (string) ($definition['default'] ?? '');
    }

    public static function missingRequiredTokens(string $template, string $field): array
    {
        $requiredTokens = (array) Arr::get(self::templateDefinitions(), $field . '.required_tokens', []);

        return collect($requiredTokens)
            ->filter(fn (string $token): bool => ! str_contains($template, $token))
            ->values()
            ->all();
    }

    public static function sanitizeTemplate(string $field, ?string $template): string
    {
        $normalized = trim((string) $template);

        return $normalized !== '' ? $normalized : self::defaultTemplateFor($field);
    }

    public static function buildDeliveryMessage(Perfil $perfil): string
    {
        return self::renderTemplate(
            $perfil,
            self::DELIVERY_TEMPLATE_FIELD,
            self::defaultDeliveryTemplate(),
            self::variablesForPerfil($perfil),
        );
    }

    public static function buildExpiryReminderMessage(Perfil $perfil): string
    {
        return self::renderTemplate(
            $perfil,
            self::EXPIRY_TEMPLATE_FIELD,
            self::defaultExpiryTemplate(),
            self::variablesForPerfil($perfil),
        );
    }

    public static function buildExpiryTodayAlertMessage(Perfil $perfil): string
    {
        return self::renderTemplate(
            $perfil,
            self::EXPIRY_TODAY_TEMPLATE_FIELD,
            self::defaultExpiryTodayTemplate(),
            self::variablesForPerfil($perfil),
        );
    }

    public static function buildExpiryMessage(Perfil $perfil): string
    {
        $diasRestantes = $perfil->dias_restantes;

        if (is_numeric($diasRestantes) && (int) $diasRestantes === 0) {
            return self::buildExpiryTodayAlertMessage($perfil);
        }

        return self::buildExpiryReminderMessage($perfil);
    }

    private static function renderTemplate(Perfil $perfil, string $field, string $fallback, array $variables): string
    {
        $template = self::resolveTemplateFromUserPreference($field, $fallback);
        $template = self::sanitizeTemplate($field, $template);

        $missingTokens = self::missingRequiredTokens($template, $field);

        if ($missingTokens !== []) {
            $template = $fallback;
        }

        return strtr($template, $variables);
    }

    private static function resolveTemplateFromUserPreference(string $field, string $fallback): string
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'preference')) {
            return $fallback;
        }

        $value = $user->preference()->first()?->{$field};

        return filled($value) ? (string) $value : $fallback;
    }

    private static function variablesForPerfil(Perfil $perfil): array
    {
        $plataforma = trim((string) ($perfil->plataforma?->nombre ?? 'No definida'));
        $correo = trim((string) ($perfil->correo_cuenta ?: '-'));
        $contrasena = trim((string) ($perfil->contrasena_cuenta ?: '-'));
        $numeroPerfil = trim((string) ($perfil->nombre_perfil ?: '-'));
        $pin = trim((string) ($perfil->pin ?: '-'));
        $fechaInicio = $perfil->fecha_inicio?->format('d/m/Y') ?? '-';
        $fechaVencimiento = $perfil->fecha_caducidad_cuenta?->format('d/m/Y')
            ?? $perfil->fecha_corte?->format('d/m/Y')
            ?? '-';
        $diasRestantes = $perfil->dias_restantes;
        $tiempoRestante = is_numeric($diasRestantes)
            ? (((int) $diasRestantes) === 1 ? '1 día' : ((int) $diasRestantes) . ' días')
            : '2 días';

        return [
            '{cliente_nombre}' => trim((string) ($perfil->cliente_nombre ?: 'Cliente')),
            '{cliente_telefono}' => trim((string) ($perfil->cliente_telefono ?: '-')),
            '{plataforma}' => $plataforma,
            '{correo}' => $correo,
            '{contrasena}' => $contrasena,
            '{numero_perfil}' => $numeroPerfil,
            '{pin}' => $pin,
            '{fecha_inicio}' => $fechaInicio,
            '{fecha_vencimiento}' => $fechaVencimiento,
            '{dias_restantes}' => is_numeric($diasRestantes) ? (string) ((int) $diasRestantes) : '-',
            '{tiempo_restante}' => $tiempoRestante,
            '{e_wave}' => self::E_WAVE,
            '{e_email}' => self::E_EMAIL,
            '{e_key}' => self::E_KEY,
            '{e_user}' => self::E_USER,
            '{e_pin}' => self::E_PIN,
            '{e_cal_start}' => self::E_CAL_START,
            '{e_cal_cut}' => self::E_CAL_CUT,
            '{e_popcorn}' => self::E_POPCORN,
            '{e_sparkles}' => self::E_SPARKLES,
            '{e_tv}' => self::E_TV,
            '{e_hourglass}' => self::E_HOURGLASS,
            '{e_clapper}' => self::E_CLAPPER,
            '{e_card}' => self::E_CARD,
            '{e_warning}' => self::E_WARNING,
        ];
    }

    private static function defaultDeliveryTemplate(): string
    {
        return "Hola {cliente_nombre}! {e_wave} Gracias por tu compra. Aqui tienes los detalles de tu acceso a {plataforma}.\n"
            . "{e_email} Correo: {correo}\n"
            . "{e_key} Contrasena: {contrasena}\n"
            . "{e_user} Perfil asignado: Perfil #{numero_perfil}\n"
            . "{e_pin} PIN de perfil: {pin}\n"
            . "{e_cal_start} Fecha de inicio: {fecha_inicio}\n"
            . "{e_cal_cut} Fecha de corte: {fecha_vencimiento}\n"
            . "{e_email} Telefono: {cliente_telefono}\n"
            . "Muchas gracias por confiar en nosotros. Disfruta tu contenido {e_popcorn}{e_sparkles}";
    }

    private static function defaultExpiryTemplate(): string
    {
        return "Hola {cliente_nombre}! {e_wave} Solo un recordatorio de que tu perfil vence muy pronto.\n\n"
            . "{e_tv} Plataforma: {plataforma}\n"
            . "{e_email} Cuenta: {correo}\n"
            . "{e_hourglass} Tiempo restante: {tiempo_restante}\n\n"
            . "Evita perder tu historial y recomendaciones renovando a tiempo. Gracias por tu preferencia {e_clapper}{e_card}";
    }

    private static function defaultExpiryTodayTemplate(): string
    {
        return "Hola {cliente_nombre}! {e_wave} Tu acceso esta a punto de expirar. Hoy es tu ultimo dia de servicio.\n"
            . "{e_tv} Plataforma: {plataforma}\n"
            . "{e_email} Cuenta: {correo}\n"
            . "{e_warning} Estado: Vence hoy\n\n"
            . "Para mantener tu perfil activo y no perder el acceso, por favor realiza tu renovacion antes de las 04:00 PM.\n\n"
            . "No te quedes sin acceso a tu contenido favorito {e_popcorn}{e_hourglass}";
    }
}
