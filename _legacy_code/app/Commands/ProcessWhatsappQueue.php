<?php

namespace App\Commands;

use App\Libraries\WhatsappGateway;
use App\Libraries\IntegrationConfig;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessWhatsappQueue extends BaseCommand
{
    protected $group = 'Mynet';
    protected $name = 'whatsapp:process-queue';
    protected $description = 'Kirim pesan WhatsApp terjadwal yang sudah jatuh waktu.';
    protected $usage = 'whatsapp:process-queue';

    public function run(array $params)
    {
        $db = db_connect();
        $staleBefore = date('Y-m-d H:i:s', time() - 3600);
        $db->table('whatsapp_queue')->where('status', 'sending')->where('processing_at <', $staleBefore)
            ->update(['status' => 'pending', 'processing_at' => null, 'last_error' => 'Worker sebelumnya berhenti; antrean dipulihkan otomatis.']);
        $rows = $db->table('whatsapp_queue')->where('status', 'pending')->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->orderBy('scheduled_at')->limit(50)->get()->getResultArray();
        $waConfig = (new IntegrationConfig())->get('whatsapp');
        $sent = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $db->transBegin();
            $db->table('whatsapp_queue')->where('id', $row['id'])->where('status', 'pending')->update([
                'status' => 'sending', 'attempts' => (int) $row['attempts'] + 1, 'processing_at' => date('Y-m-d H:i:s'),
            ]);
            if ($db->affectedRows() !== 1) {
                $db->transRollback();
                continue;
            }
            $db->transCommit();

            $waConfig = ! empty($row['branch_id']) ? (new IntegrationConfig())->getBranchWhatsapp((int) $row['branch_id']) : (new IntegrationConfig())->get('whatsapp');
            $targets = $this->targets($row, $waConfig);
            $allSent = count($targets) > 0;
            $errors = [];
            foreach ($targets as $target) {
                $phone = trim((string) ($target['phone'] ?? ''));
                if ($phone === '') {
                    $allSent = false;
                    $errors[] = 'Nomor kosong untuk ' . ($target['full_name'] ?? 'penerima');
                    continue;
                }
                $context = json_decode($row['context_json'] ?? '', true) ?: [];
                $targetBranch = (int) ($target['branch_id'] ?? $row['branch_id'] ?? 0);
                $targetConfig = $targetBranch ? (new IntegrationConfig())->getBranchWhatsapp($targetBranch) : $waConfig;
                $message = strtr($row['message'], [
                    '{{nama}}' => (string) ($target['full_name'] ?? 'Pelanggan'),
                    '{{nomor_tiket}}' => (string) ($context['ticket_no'] ?? ''),
                    '{{nomor_tagihan}}' => (string) ($context['invoice_no'] ?? ''),
                    '{{periode}}' => (string) ($context['period'] ?? ''),
                    '{{status}}' => (string) ($context['status'] ?? ''),
                    '{{total}}' => isset($context['total']) ? 'Rp ' . number_format((float) $context['total'], 0, ',', '.') : '',
                ]);
                try {
                    $result = (new WhatsappGateway())->send($phone, $message, 'queue-' . $row['id'], $targetBranch ?: null);
                    $db->table('message_logs')->insert([
                        'customer_id' => $target['customer_id'] ?? null, 'provider' => $result['provider'], 'recipient' => $target['log_recipient'] ?? $phone,
                        'message' => $message, 'status' => 'sent', 'provider_reference' => $result['reference'], 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                    $allSent = false;
                    $errors[] = $e->getMessage();
                    $db->table('message_logs')->insert([
                        'customer_id' => $target['customer_id'] ?? null, 'provider' => (string) ($targetConfig['provider'] ?? ''),
                        'recipient' => $target['log_recipient'] ?? $phone, 'message' => $message, 'status' => 'failed', 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            $db->table('whatsapp_queue')->where('id', $row['id'])->update([
                'status' => $allSent ? 'sent' : 'failed', 'sent_at' => $allSent ? date('Y-m-d H:i:s') : null, 'processing_at' => null,
                'last_error' => $allSent ? null : mb_substr(implode('; ', $errors) ?: 'Tidak ada penerima aktif dengan nomor WhatsApp.', 0, 4000),
            ]);
            $allSent ? $sent++ : $failed++;
        }
        CLI::write("Antrean selesai. Terkirim: {$sent}, gagal: {$failed}.", $failed ? 'yellow' : 'green');
    }

    private function targets(array $queue, array $waConfig): array
    {
        $db = db_connect();
        if ($queue['target_group'] === 'pelanggan') {
            $query = $db->table('customers')->select('id AS customer_id, full_name, phone, branch_id')->where('status', 'active');
            if ($queue['target_customer_id']) $query->where('id', $queue['target_customer_id']);
            if ($queue['branch_id']) $query->where('branch_id', $queue['branch_id']);
            return $query->get()->getResultArray();
        }
        $provider = strtolower((string) ($waConfig['provider'] ?? ''));
        $groupField = match ($queue['target_group']) {
            'teknisi' => 'technician_group_id',
            'keuangan' => 'finance_group_id',
            'admin' => 'admin_group_id',
            default => null,
        };
        $branchGroups = $waConfig['branch_groups'] ?? [];
        $branchGroupId = $queue['branch_id'] ? ($branchGroups[(string) $queue['branch_id']][$queue['target_group']] ?? '') : '';
        $groupId = trim((string) ($branchGroupId ?: ($groupField ? ($waConfig[$groupField] ?? '') : '')));
        if (in_array($provider, ['fonnte', 'ngirimwa'], true) && $groupId !== '' && empty($queue['target_user_id'])) {
            return [[
                'phone' => $groupId, 'full_name' => 'Grup ' . $queue['target_group'],
                'group_target' => true, 'log_recipient' => 'Grup ' . $queue['target_group'],
            ]];
        }
        $query = $db->table('users')->select('id, full_name, phone')->where('active', 1);
        if ($queue['target_user_id']) $query->where('id', $queue['target_user_id']);
        elseif ($queue['target_group'] === 'teknisi' || $queue['target_group'] === 'keuangan') $query->where('role', $queue['target_group']);
        elseif ($queue['target_group'] === 'admin') $query->whereIn('role', ['admin', 'administrator', 'superadmin']);
        if ($queue['branch_id']) $query->groupStart()->where('branch_id', $queue['branch_id'])->orWhere('branch_id', null)->groupEnd();
        return $query->get()->getResultArray();
    }
}
