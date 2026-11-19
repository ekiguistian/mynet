<?php

namespace App\Controllers;

use App\Libraries\WhatsappQueueService;

class MaintenanceTickets extends BaseController
{
    private array $roles = ['superadmin', 'administrator', 'admin', 'noc', 'teknisi', 'sales', 'pelanggan'];
    private array $managers = ['superadmin', 'administrator', 'admin', 'noc'];

    public function index()
    {
        if (! in_array(mynet_user('role'), $this->roles, true)) return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        $builder = db_connect()->table('maintenance_tickets t')
            ->select('t.*, c.full_name AS customer_name, tech.full_name AS technician_name')
            ->join('customers c', 'c.id = t.customer_id', 'left')
            ->join('users tech', 'tech.id = t.assigned_to', 'left')
            ->orderBy('t.id', 'DESC')->limit(150);
        if (mynet_user('role') === 'pelanggan') $builder->where('t.customer_id', mynet_user('customer_id') ?? 0);
        elseif (mynet_user('branch_id')) $builder->where('t.branch_id', mynet_user('branch_id'));
        elseif (mynet_user('role') === 'teknisi') $builder->where('t.assigned_to', mynet_user('id'));
        if (mynet_user('role') === 'teknisi') $builder->where('t.assigned_to', mynet_user('id'));
        $tickets = $builder->get()->getResultArray();
        $ticketUpdates = [];
        if ($tickets) {
            $updates = db_connect()->table('ticket_updates u')
                ->select('u.ticket_id, u.status, u.comment, u.created_at, operator.full_name AS operator_name')
                ->join('users operator', 'operator.id = u.user_id', 'left')
                ->whereIn('u.ticket_id', array_column($tickets, 'id'))
                ->orderBy('u.created_at', 'ASC')->get()->getResultArray();
            foreach ($updates as $update) $ticketUpdates[$update['ticket_id']][] = $update;
        }

        $technicians = db_connect()->table('users')->select('id, full_name')->where('active', 1)->where('role', 'teknisi')->orderBy('full_name');
        if (mynet_user('branch_id')) $technicians->where('branch_id', mynet_user('branch_id'));
        $customers = db_connect()->table('customers')->select('id, full_name, customer_code')->where('status', 'active')->orderBy('full_name');
        if (mynet_user('branch_id')) $customers->where('branch_id', mynet_user('branch_id'));
        $branches = db_connect()->table('branches')->select('id, name')->where('status', 'active')->orderBy('name')->get()->getResultArray();

        return view('tickets/index', [
            'title' => 'Tiket maintenance', 'tickets' => $tickets, 'ticketUpdates' => $ticketUpdates,
            'technicians' => $technicians->get()->getResultArray(), 'customers' => $customers->get()->getResultArray(),
            'branches' => $branches, 'canAssign' => in_array(mynet_user('role'), $this->managers, true),
            'canCreate' => mynet_user('role') !== 'pelanggan' || ! empty(mynet_user('customer_id')),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function create()
    {
        if (! in_array(mynet_user('role'), $this->roles, true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $title = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $category = (string) $this->request->getPost('category');
        $priority = (string) $this->request->getPost('priority');
        if ($title === '' || $description === '' || mb_strlen($title) > 160 || ! in_array($category, ['network', 'equipment', 'installation', 'other'], true)
            || ! in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            return redirect()->back()->withInput()->with('error', 'Lengkapi judul, kategori, prioritas, dan uraian masalah.');
        }
        $customerId = mynet_user('role') === 'pelanggan' ? (int) (mynet_user('customer_id') ?? 0) : ((int) $this->request->getPost('customer_id') ?: 0);
        $customer = $customerId ? db_connect()->table('customers')->where('id', $customerId)->get()->getRowArray() : null;
        if ($customerId && ! $customer) return redirect()->back()->with('error', 'Pelanggan tidak ditemukan.');
        if (mynet_user('role') === 'pelanggan' && (! $customer || $customerId !== (int) mynet_user('customer_id'))) return $this->response->setStatusCode(403)->setBody('Profil pelanggan tidak valid.');

        $branchId = $customer ? (int) $customer['branch_id'] : (int) (mynet_user('branch_id') ?: $this->request->getPost('branch_id'));
        if (! $branchId || ! $this->inBranch($branchId) || ! db_connect()->table('branches')->where('id', $branchId)->where('status', 'active')->countAllResults()) {
            return redirect()->back()->with('error', 'Pilih cabang aktif yang dapat Anda akses.');
        }
        if ($customer && (int) $customer['branch_id'] !== $branchId) return redirect()->back()->with('error', 'Pelanggan harus berada di cabang tiket.');
        $ticketNo = 'MT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $db = db_connect();
        $db->transBegin();
        $db->table('maintenance_tickets')->insert([
            'branch_id' => $branchId, 'customer_id' => $customerId ?: null, 'ticket_no' => $ticketNo,
            'title' => $title, 'category' => $category, 'description' => $description,
            'priority' => $priority, 'status' => 'open', 'opened_by' => (int) mynet_user('id'),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $ticketId = (int) $db->insertID();
        $db->table('ticket_updates')->insert(['ticket_id' => $ticketId, 'user_id' => mynet_user('id'), 'status' => 'open', 'comment' => 'Tiket dibuat.', 'created_at' => date('Y-m-d H:i:s')]);
        $db->transCommit();

        if (mynet_user('role') === 'pelanggan') {
            $this->queueNotice('admin', $branchId, 'maintenance_new', ['nomor_tiket' => $ticketNo, 'nama' => $customer['full_name']]);
        }
        return redirect()->to('/tickets')->with('success', 'Tiket ' . $ticketNo . ' masuk ke antrean maintenance.');
    }

    public function assign(int $id)
    {
        if (! in_array(mynet_user('role'), $this->managers, true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $ticket = $this->ticketForUser($id);
        $technicianId = (int) $this->request->getPost('technician_id');
        $technician = db_connect()->table('users')->where('id', $technicianId)->where('role', 'teknisi')->where('active', 1)->get()->getRowArray();
        if (! $ticket || ! $technician || (int) $technician['branch_id'] !== (int) $ticket['branch_id']) return redirect()->back()->with('error', 'Tiket atau teknisi tidak valid untuk cabang ini.');
        $scheduledAt = trim((string) $this->request->getPost('scheduled_at'));
        if ($scheduledAt !== '') {
            $scheduledAt = str_replace('T', ' ', $scheduledAt);
            if (strlen($scheduledAt) === 16) $scheduledAt .= ':00';
            $scheduledTime = strtotime($scheduledAt);
            if (! $scheduledTime) return redirect()->back()->with('error', 'Jadwal kunjungan tidak valid.');
            $scheduledAt = date('Y-m-d H:i:s', $scheduledTime);
        } else $scheduledAt = null;
        db_connect()->table('maintenance_tickets')->where('id', $id)->update([
            'assigned_to' => $technicianId, 'scheduled_at' => $scheduledAt,
            'status' => 'assigned', 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->addUpdate($id, 'assigned', trim((string) $this->request->getPost('comment')) ?: 'Ditugaskan ke ' . $technician['full_name'] . '.');
        $this->queueNotice('teknisi', (int) $ticket['branch_id'], 'maintenance_assigned', ['nomor_tiket' => $ticket['ticket_no'], 'nama' => $technician['full_name']], null, $technicianId);
        return redirect()->to('/tickets')->with('success', 'Tiket ditugaskan ke ' . $technician['full_name'] . '.');
    }

    public function updateStatus(int $id)
    {
        $ticket = $this->ticketForUser($id);
        $role = mynet_user('role');
        if (! $ticket || ($role === 'pelanggan')) return $this->response->setStatusCode(404)->setBody('Tiket tidak ditemukan.');
        if (! in_array($role, $this->managers, true) && ($role !== 'teknisi' || (int) $ticket['assigned_to'] !== (int) mynet_user('id'))) {
            return $this->response->setStatusCode(403)->setBody('Tiket ini bukan penugasan Anda.');
        }
        $status = (string) $this->request->getPost('status');
        $allowed = in_array($role, $this->managers, true) ? ['in_progress', 'resolved', 'closed'] : ['in_progress', 'resolved'];
        if (! in_array($status, $allowed, true) || in_array($ticket['status'], ['closed'], true)) return redirect()->back()->with('error', 'Perubahan status tiket tidak valid.');
        $comment = trim((string) $this->request->getPost('comment'));
        $resolution = trim((string) $this->request->getPost('resolution'));
        if ($status === 'resolved' && $resolution === '') $resolution = $comment;
        db_connect()->table('maintenance_tickets')->where('id', $id)->update([
            'status' => $status, 'resolution' => $resolution ?: $ticket['resolution'], 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->addUpdate($id, $status, $comment ?: ($resolution ?: 'Status diperbarui.'));
        if ($ticket['customer_id']) {
            $customer = db_connect()->table('customers')->where('id', $ticket['customer_id'])->get()->getRowArray();
            if ($customer && $customer['phone']) $this->queueNotice('pelanggan', (int) $ticket['branch_id'], 'maintenance_update', ['nomor_tiket' => $ticket['ticket_no'], 'nama' => $customer['full_name'], 'status' => $status], (int) $customer['id']);
        }
        return redirect()->to('/tickets')->with('success', 'Status tiket diperbarui.');
    }

    private function ticketForUser(int $id): ?array
    {
        $query = db_connect()->table('maintenance_tickets')->where('id', $id);
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $ticket = $query->get()->getRowArray();
        return $ticket ?: null;
    }

    private function addUpdate(int $ticketId, string $status, string $comment): void
    {
        db_connect()->table('ticket_updates')->insert([
            'ticket_id' => $ticketId, 'user_id' => mynet_user('id'), 'status' => $status,
            'comment' => $comment ?: null, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function queueNotice(string $group, int $branchId, string $code, array $context, ?int $customerId = null, ?int $userId = null): void
    {
        $template = db_connect()->table('message_templates')->where('code', $code)->where('active', 1)->get()->getRowArray();
        $body = $template['body'] ?? 'Tiket maintenance {{nomor_tiket}} diperbarui.';
        (new WhatsappQueueService())->enqueue($group, $branchId, $body, date('Y-m-d H:i:s'), (int) mynet_user('id'), $customerId, $userId, $template ? (int) $template['id'] : null, $context);
    }

    private function inBranch(int $branchId): bool
    {
        return empty(mynet_user('branch_id')) || (int) mynet_user('branch_id') === $branchId;
    }
}
