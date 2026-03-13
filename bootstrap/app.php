<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_PORT,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e, ?Request $request = null): void {
            $request ??= request();

            if (! $request) {
                return;
            }

            if (! $request->is('livewire/upload-file') && ! $request->is('livewire/update')) {
                return;
            }

            Log::error('Livewire request failure', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'path' => $request->path(),
                'app_url' => config('app.url'),
                'request_url' => $request->fullUrl(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'is_secure' => $request->isSecure(),
                'content_length' => $request->server('CONTENT_LENGTH'),
                'content_type' => $request->server('CONTENT_TYPE'),
                'has_file' => $request->hasFile('files'),
                'components' => collect((array) $request->input('components'))->pluck('snapshot.memo.name')->filter()->values()->all(),
                'livewire_upload_disk' => config('livewire.temporary_file_upload.disk'),
                'livewire_upload_directory' => config('livewire.temporary_file_upload.directory'),
            ]);
        });
    })
    ->create();
