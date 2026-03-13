<?php

use App\Models\CuentaReportada;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    CuentaReportada::query()
        ->where('estado', 'solucionado')
        ->whereNotNull('solucionado_at')
        ->where('solucionado_at', '<=', now()->subHours(12))
        ->delete();
})->hourly()->name('cuentas-reportadas:purge-solved');

Artisan::command('app:ensure-plataformas-imagen-column', function () {
    if (! Schema::hasTable('plataformas')) {
        $this->error('La tabla plataformas no existe.');

        return;
    }

    if (Schema::hasColumn('plataformas', 'imagen')) {
        $this->info('La columna imagen ya existe en plataformas.');

        return;
    }

    Schema::table('plataformas', function (Blueprint $table): void {
        $table->string('imagen')->nullable();
    });

    $this->info('Columna imagen agregada en plataformas.');
})->purpose('Agrega la columna imagen en plataformas si no existe');

Artisan::command('app:diagnose-uploads', function () {
    $uploadTmp = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    $livewireDisk = (string) config('livewire.temporary_file_upload.disk');
    $livewireDir = (string) config('livewire.temporary_file_upload.directory');
    $diskRoot = (string) config("filesystems.disks.{$livewireDisk}.root");
    $targetPath = rtrim($diskRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($livewireDir, DIRECTORY_SEPARATOR);

    $this->line('APP_URL: ' . config('app.url'));
    $this->line('UPLOAD_TMP_DIR: ' . $uploadTmp);
    $this->line('LIVEWIRE_DISK: ' . $livewireDisk);
    $this->line('LIVEWIRE_DIR: ' . $livewireDir);
    $this->line('LIVEWIRE_TARGET_PATH: ' . $targetPath);
    $this->line('UPLOAD_MAX_FILESIZE: ' . ini_get('upload_max_filesize'));
    $this->line('POST_MAX_SIZE: ' . ini_get('post_max_size'));
    $this->line('FILE_UPLOADS: ' . ini_get('file_uploads'));

    $checks = [
        'tmp_dir_exists' => File::isDirectory($uploadTmp),
        'tmp_dir_writable' => is_writable($uploadTmp),
        'target_dir_exists' => File::isDirectory($targetPath),
        'target_dir_writable' => is_writable($targetPath),
        'gd_extension_loaded' => extension_loaded('gd'),
        'fileinfo_extension_loaded' => extension_loaded('fileinfo'),
    ];

    foreach ($checks as $name => $ok) {
        $this->line(($ok ? '[OK] ' : '[FAIL] ') . $name);
    }
})->purpose('Diagnostica prerequisitos de subida Livewire/Filament');
