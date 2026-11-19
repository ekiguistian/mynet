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

        $query = db_connect()->table('routers')->select('id, name, branch_id, host, api_port, base_url, username, password_encrypted, connection_encrypted, verify_ssl, active')->orderBy('name');
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $routers = $query->get()->getResultArray();
        $result = [];
        foreach ($routers as $router) {
            $item = ['id' => (int) $router['id'], 'identity' => 'Offline', 'uptime' => '—', 'cpu' => null, 'memory_used' => 0, 'memory_total' => 0];
            if ((int) $router['active'] === 1) {
                try {
                    $item = array_merge($item, (new RouterOs())->monitor($router));
                } catch (\Throwable $e) {
                    log_message('warning', 'Router monitor check failed for router {id}: {error}', ['id' => $router['id'], 'error' => $e->getMessage()]);
                    $item['identity'] = 'Offline';
                }
            }
            $result[] = $item;
        }

        return $this->response->setJSON(['routers' => $result, 'checked_at' => date(DATE_ATOM)]);
    }
}
