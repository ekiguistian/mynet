<?php

namespace App\Controllers;

use Throwable;

class Version extends BaseController
{
    public function index()
    {
        if ($denied = $this->authorize()) return $denied;

        $runner = service('migrations');
        $applied = [];
        foreach ($runner->getHistory() as $migration) {
            $applied[(string) $migration->version] = true;
        }

        $migrations = [];
        foreach ($runner->findMigrations() as $migration) {
            $migrations[] = [
                'version' => (string) $migration->version,
                'class' => (string) $migration->class,
                'namespace' => (string) $migration->namespace,
                'applied' => isset($applied[(string) $migration->version]),
            ];
        }

        return view('version/index', [
            'title' => 'Versi aplikasi',
            'version' => trim((string) @file_get_contents(ROOTPATH . 'VERSION')) ?: 'development',
            'frameworkVersion' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'migrations' => $migrations,
            'pendingCount' => count(array_filter($migrations, static fn (array $row): bool => ! $row['applied'])),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function migrate()
    {
        if ($denied = $this->authorize()) return $denied;

        try {
            service('migrations')->latest();
            return redirect()->to('/version')->with('success', 'Migrasi database selesai dijalankan.');
        } catch (Throwable $e) {
            log_message('error', 'Versi aplikasi: migrasi gagal: {message}', ['message' => $e->getMessage()]);
            return redirect()->to('/version')->with('error', 'Migrasi gagal. Periksa log aplikasi dan backup database sebelum mencoba lagi.');
        }
    }

    private function authorize()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator'], true)) {
            return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        }

        return null;
    }
}
