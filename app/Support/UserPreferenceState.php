<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class UserPreferenceState
{
    private const TABLE = 'user_preferences';

    private const TEMPLATE_COLUMNS = [
        'delivery_message_template',
        'expiry_message_template',
        'expiry_today_message_template',
    ];

    public static function defaults(): array
    {
        $defaults = [
            'theme_mode' => 'system',
            'colorize_accounts' => true,
            'dense_interface' => false,
            'reduced_motion' => false,
        ];

        if (static::supportsMessageTemplates()) {
            $defaults['delivery_message_template'] = ClientMessageBuilder::defaultTemplateFor('delivery_message_template');
            $defaults['expiry_message_template'] = ClientMessageBuilder::defaultTemplateFor('expiry_message_template');
            $defaults['expiry_today_message_template'] = ClientMessageBuilder::defaultTemplateFor('expiry_today_message_template');
        }

        return $defaults;
    }

    public static function forUser($user): array
    {
        $defaults = static::defaults();

        if (! $user) {
            return $defaults;
        }

        if (! method_exists($user, 'preference')) {
            return $defaults;
        }
        $preference = $user->preference()->first();

        if (! $preference) {
            return $defaults;
        }

        return array_merge(
            $defaults,
            Arr::only($preference->toArray(), array_keys($defaults)),
        );
    }

    public static function supportsMessageTemplates(): bool
    {
        return static::hasColumns(static::TEMPLATE_COLUMNS);
    }

    public static function filterPersistable(array $payload): array
    {
        if (! static::tableExists()) {
            return $payload;
        }

        $columns = Schema::getColumnListing(static::TABLE);

        return Arr::only($payload, $columns);
    }

    private static function hasColumns(array $columns): bool
    {
        if (! static::tableExists()) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn(static::TABLE, $column)) {
                return false;
            }
        }

        return true;
    }

    private static function tableExists(): bool
    {
        return Schema::hasTable(static::TABLE);
    }
}
