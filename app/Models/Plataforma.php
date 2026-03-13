<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Plataforma extends Model
{
    use HasFactory;

    protected static ?bool $hasImagenColumnCache = null;

    protected $table = 'plataformas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'activa',
        'perfiles_por_cuenta',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'perfiles_por_cuenta' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $plataforma): void {
            if (! static::hasImagenColumn()) {
                $plataforma->offsetUnset('imagen');
            }
        });
    }

    public static function hasImagenColumn(): bool
    {
        if (static::$hasImagenColumnCache === null) {
            static::$hasImagenColumnCache = Schema::hasColumn((new static())->getTable(), 'imagen');
        }

        return static::$hasImagenColumnCache;
    }

    public function perfiles(): HasMany
    {
        return $this->hasMany(Perfil::class);
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }
}
