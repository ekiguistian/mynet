<?php

namespace App\Controllers;

use App\Libraries\ApiKeyService;
use App\Libraries\WhatsappQueueService;

class ExternalApi extends BaseController
{
    public function ping()
    {
        return $this->response->setJSON(['success' => true, 'service' => 'Mynet API', 'version' => 'v1']);
    }

    public function customers()
    {
        $key = ApiKeyService::current();
        $query = db_connect()->table('customers')->select('customer_code, full_name, phone, email, service_type, status, branch_id');
        if ($key['branch_id'] !== null) $query->where('branch_id', $key['branch_id']);
        return $this->response->setJSON(['data' => $query->orderBy('id', 'DESC')->limit(500)->get()->getResultArray()]);
    }

    public function invoices()
    {
        $key = ApiKeyService::current();
        $query = db_connect()->table('invoices')->select('invoice_no, customer_id, period, total, status, due_date, branch_id');
        if ($key['branch_id'] !== null) $query->where('branch_id', $key['branch_id']);
        return $this->response->setJSON(['data' => $query->orderBy('id', 'DESC')->limit(500)->get()->getResultArray()]);
    }

    public function tickets()
    {
        $key = ApiKeyService::current();
        $query = db_connect()->table('maintenance_tickets')->select('ticket_no, customer_id, title, category, priority, status, assigned_to, scheduled_at, created_at, branch_id');
        if ($key['branch_id'] !== null) $query->where('branch_id', $key['branch_id']);
        return $this->response->setJSON(['data' => $query->orderBy('id', 'DESC')->limit(500)->get()->getResultArray()]);
    }

    public function createTicket()
    {
        $key = ApiKeyService::current();
        $payload = $this->request->getJSON(true) ?: [];
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if ($title === '' || $description === '') return $this->response->setStatusCode(422)->setJSON(['error' => 'title and description are required.']);
        $branchId = $key['branch_id'] ? (int) $key['branch_id'] : (int) ($payload['branch_id'] ?? 0);
        if (! $branchId || ! db_connect()->table('branches')->where('id', $branchId)->where('status', 'active')->countAllResults()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'A valid active branch_id is required.']);
        }
        $customerId = (int) ($payload['customer_id'] ?? 0) ?: null;
        if ($customerId && ! db_connect()->table('customers')->where('id', $customerId)->where('branch_id', $branchId)->countAllResults()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Customer does not belong to this branch.']);
        }
        $ticketNo = 'MT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $db = db_connect();
        $db->transBegin();
        $db->table('maintenance_tickets')->insert([
            'branch_id' => $branchId, 'customer_id' => $customerId, 'ticket_no' => $ticketNo,
            'title' => mb_substr($title, 0, 160), 'category' => in_array($payload['category'] ?? '', ['network', 'equipment', 'installation', 'other'], true) ? $payload['category'] : 'other',
            'description' => $description, 'priority' => in_array($payload['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true) ? $payload['priority'] : 'normal',
            'status' => 'open', 'opened_by' => 0, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $ticketId = (int) $db->insertID();
        $db->table('ticket_updates')->insert([
            'ticket_id' => $ticketId, 'user_id' => 0, 'status' => 'open',
            'comment' => 'Tiket dibuat melalui API eksternal.', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();
        $template = $db->table('message_templates')->where('code', 'maintenance_new')->where('active', 1)->get()->getRowArray();
        (new WhatsappQueueService())->enqueue(
            'admin', $branchId, $template['body'] ?? 'Tiket {{nomor_tiket}} baru masuk ke antrean.',
            date('Y-m-d H:i:s'), 0, null, null, $template ? (int) $template['id'] : null,
            ['ticket_no' => $ticketNo, 'nama' => 'Integrasi API']
        );
        return $this->response->setStatusCode(201)->setJSON(['success' => true, 'ticket_no' => $ticketNo]);
    }
}
