<?php

namespace App\Controllers;

use App\Libraries\RouterOs;

class RouterMonitoring extends BaseController
{
    public function status()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'noc'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Tidak memiliki akses.']);
        }

        $query = db_connect()->table('routers')->select('id, name, branch_id, base_url, username, password_encrypted, verify_ssl, active')->orderBy('name');
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $routers = $query->get()->getResultArray();
        $result = [];
        foreach ($routers as $router) {
            $item = ['id' => (int) $router['id'], 'name' => $router['name'], 'connected' => false, 'uptime' => null, 'cpu' => null, 'memory_used' => null, 'memory_total' => null];
            if ((int) $router['active'] === 1) {
                try {
                    $resource = (new RouterOs())->systemResource($router);
                    $total = (int) ($resource['total-memory'] ?? 0);
                    $free = (int) ($resource['free-memory'] ?? 0);
                    $item['connected'] = true;
                    $item['uptime'] = (string) $resource['uptime'];
                    $item['cpu'] = isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null;
                    $item['memory_used'] = max(0, $total - $free);
                    $item['memory_total'] = $total;
                } catch (\Throwable $e) {
                    log_message('warning', 'Router monitor check failed for router {id}: {error}', ['id' => $router['id'], 'error' => $e->getMessage()]);
                }
            }
            $result[] = $item;
        }

        return $this->response->setJSON(['routers' => $result, 'checked_at' => date(DATE_ATOM)]);
    }
}
