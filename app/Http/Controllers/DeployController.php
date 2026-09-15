<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Stand-in for `php artisan migrate`/`artisan optimize` on hosting with no SSH access (see
 * DEPLOY.md and the migration plan's Faz 0/8) — a deploy uploads the built app over
 * FTP/File Manager, then hits this once instead of a terminal. Guarded by DEPLOY_TOKEN
 * (constant-time compare); refuses everything if that env var isn't set, so it can't become
 * an unauthenticated backdoor by accident on a host where the operator forgot to configure it.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $configured = config('uptik.deploy_token');

        if (blank($configured) || ! hash_equals($configured, (string) $request->query('token'))) {
            abort(404);
        }

        $action = $request->query('action', 'migrate');

        $output = match ($action) {
            'migrate' => $this->run('migrate', ['--force' => true]),
            'optimize' => $this->run('optimize'),
            'optimize-clear' => $this->run('optimize:clear'),
            default => "Unknown action: {$action}",
        };

        return response($output)->header('Content-Type', 'text/plain');
    }

    private function run(string $command, array $params = []): string
    {
        Artisan::call($command, $params);

        return Artisan::output();
    }
}
