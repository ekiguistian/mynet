<?php

namespace App\Libraries;

class WhatsappQueueService
{
    public function enqueue(
        string $targetGroup,
        ?int $branchId,
        string $message,
        string $scheduledAt,
        ?int $createdBy,
        ?int $customerId = null,
        ?int $userId = null,
        ?int $templateId = null,
        array $context = []
    ): int {
        $db = db_connect();
        $db->table('whatsapp_queue')->insert([
            'branch_id' => $branchId, 'target_group' => $targetGroup,
            'target_customer_id' => $customerId, 'target_user_id' => $userId,
            'template_id' => $templateId, 'context_json' => $context ? json_encode($context) : null, 'message' => $message,
            'scheduled_at' => $scheduledAt, 'status' => 'pending', 'attempts' => 0,
            'created_by' => $createdBy ?? 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }
}
