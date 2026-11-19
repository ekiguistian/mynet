<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 30)->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('service_type', 20)->default('pppoe')->index();
            $table->string('speed_download', 30)->nullable();
            $table->string('speed_upload', 30)->nullable();
            $table->decimal('monthly_price', 14, 2)->default(0);
            $table->string('hotspot_profile', 80)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->index(['branch_id', 'name']);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_code', 40)->unique();
            $table->string('full_name', 160)->index();
            $table->string('email', 180)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('service_type', 20)->default('pppoe')->index();
            $table->string('connection_mode', 20)->default('create');
            $table->string('static_ip', 45)->nullable();
            $table->unsignedBigInteger('mikrotik_router_id')->nullable()->index();
            $table->string('mikrotik_username', 100)->nullable();
            $table->unsignedTinyInteger('due_day')->default(20);
            $table->boolean('auto_billing')->default(true);
            $table->string('device_serial', 120)->nullable();
            $table->unsignedBigInteger('odp_port_id')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->date('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });

        Schema::create('routers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->text('host')->nullable();
            $table->text('api_port')->nullable();
            $table->text('username')->nullable();
            $table->longText('password')->nullable();
            $table->boolean('api_ssl')->default(false);
            $table->boolean('verify_ssl')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->index(['branch_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 60);
            $table->string('name', 160)->index();
            $table->string('category', 40)->default('other')->index();
            $table->string('unit', 30)->default('pcs');
            $table->decimal('stock_qty', 14, 2)->default(0);
            $table->decimal('minimum_stock', 14, 2)->default(0);
            $table->decimal('cost_price', 14, 2)->default(0);
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['branch_id', 'sku']);
        });

        Schema::create('product_serials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('serial_number', 120)->unique();
            $table->string('status', 20)->default('available')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'product_id', 'status']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('direction', 10)->index();
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('reason', 160)->nullable();
            $table->string('reference', 80)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('invoice_no', 50)->unique();
            $table->char('period', 7);
            $table->decimal('amount', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('status', 20)->default('unpaid')->index();
            $table->date('due_date')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'period']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('provider', 20)->default('manual')->index();
            $table->string('payment_method', 80)->nullable();
            $table->string('external_reference', 120)->nullable()->unique();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('checkout_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('entry_type', 10)->index();
            $table->string('category', 80);
            $table->decimal('amount', 14, 2);
            $table->string('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('transaction_date')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->string('status', 20)->default('present');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'work_date']);
        });

        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('work_order_no', 50)->unique();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->decimal('billable_amount', 14, 2)->default(0);
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('earning_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 30);
            $table->string('calculation_type', 20)->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['branch_id', 'role', 'active']);
        });

        Schema::create('staff_earnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_order_id')->constrained()->restrictOnDelete();
            $table->string('earning_type', 20);
            $table->char('period', 7);
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'work_order_id', 'earning_type']);
        });

        Schema::create('hotspot_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('profile', 80);
            $table->string('username', 100)->unique();
            $table->string('password', 100);
            $table->string('status', 20)->default('ready')->index();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('unit_price', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 20);
            $table->string('event_key', 160);
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_key']);
        });

        Schema::create('message_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20);
            $table->string('recipient', 30);
            $table->text('message');
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_reference', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('integration_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('section', 40)->unique();
            $table->longText('encrypted_value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('prefix', 24)->unique();
            $table->string('secret_hash', 64)->unique();
            $table->json('scopes');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('whatsapp_queue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_group', 20);
            $table->foreignId('target_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->json('context_json')->nullable();
            $table->text('message');
            $table->timestamp('scheduled_at')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processing_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('maintenance_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_no', 50)->unique();
            $table->string('title', 160);
            $table->string('category', 40);
            $table->text('description');
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ticket_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('branch_whatsapp_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('encrypted_value');
            $table->timestamps();
        });

        Schema::create('fiber_odcs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->text('location')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'code']);
        });

        Schema::create('fiber_cores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('odc_id')->constrained('fiber_odcs')->cascadeOnDelete();
            $table->unsignedInteger('core_number');
            $table->string('color', 40);
            $table->string('route_label', 120)->nullable();
            $table->string('status', 20)->default('available');
            $table->unique(['odc_id', 'core_number']);
        });

        Schema::create('fiber_odps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('odc_id')->constrained('fiber_odcs')->restrictOnDelete();
            $table->foreignId('core_id')->constrained('fiber_cores')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->text('location')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'code']);
        });

        Schema::create('fiber_odp_ports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('odp_id')->constrained('fiber_odps')->cascadeOnDelete();
            $table->unsignedInteger('port_number');
            $table->string('status', 20)->default('available')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['odp_id', 'port_number']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('mikrotik_router_id')->references('id')->on('routers')->nullOnDelete();
            $table->foreign('odp_port_id')->references('id')->on('fiber_odp_ports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['branch_id']);
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['odp_port_id']);
            $table->dropForeign(['mikrotik_router_id']);
        });

        foreach ([
            'fiber_odp_ports', 'fiber_odps', 'fiber_cores', 'fiber_odcs', 'branch_whatsapp_integrations',
            'ticket_updates', 'maintenance_tickets', 'whatsapp_queue', 'message_templates', 'api_keys',
            'integration_settings', 'app_settings', 'message_logs', 'webhook_events', 'hotspot_vouchers',
            'staff_earnings', 'earning_rules', 'work_orders', 'attendances', 'finance_entries', 'payments',
            'invoices', 'inventory_movements', 'product_serials', 'products', 'routers', 'customers', 'plans',
            'branches',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
