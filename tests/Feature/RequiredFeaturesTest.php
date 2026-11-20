<?php

namespace Tests\Feature;

use App\Filament\Pages\ActivityLogs;
use App\Filament\Pages\ProfileSetup;
use App\Filament\Resources\UserResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FiberCore;
use App\Models\FiberOdc;
use App\Models\FinanceEntry;
use App\Models\IntegrationSetting;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RequiredFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_movements_update_stock_and_refuse_negative_stock(): void
    {
        $branch = Branch::query()->create(['name' => 'Pusat', 'code' => 'PST']);
        $product = Product::query()->create([
            'branch_id' => $branch->getKey(),
            'sku' => 'ONT-001',
            'name' => 'ONT',
            'category' => 'onu',
            'unit' => 'pcs',
            'stock_qty' => 5,
            'minimum_stock' => 1,
            'cost_price' => 100000,
            'sale_price' => 125000,
            'active' => true,
        ]);

        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'product_id' => $product->getKey(),
            'direction' => 'out',
            'quantity' => 3,
            'unit_cost' => 100000,
            'reason' => 'Pemasangan pelanggan',
        ]);

        $this->assertSame('2.00', $product->fresh()->stock_qty);

        try {
            InventoryMovement::query()->create([
                'branch_id' => $branch->getKey(),
                'product_id' => $product->getKey(),
                'direction' => 'out',
                'quantity' => 3,
                'unit_cost' => 100000,
            ]);
            $this->fail('Stok tidak boleh menjadi negatif.');
        } catch (ValidationException) {
            $this->assertSame('2.00', $product->fresh()->stock_qty);
            $this->assertSame(1, InventoryMovement::query()->count());
        }
    }

    public function test_midtrans_webhook_marks_payment_and_invoice_paid_once(): void
    {
        $branch = Branch::query()->create(['name' => 'Pusat', 'code' => 'PST']);
        $customer = Customer::query()->create([
            'branch_id' => $branch->getKey(),
            'full_name' => 'Pelanggan Uji',
            'service_type' => 'pppoe',
            'due_day' => 20,
            'auto_billing' => true,
        ]);
        $invoice = Invoice::query()->create([
            'branch_id' => $branch->getKey(),
            'customer_id' => $customer->getKey(),
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'discount' => 0,
            'due_date' => today()->addDays(7),
        ]);
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'provider' => 'midtrans',
            'external_reference' => 'MYN-PAY-'.$invoice->getKey(),
            'amount' => 100000,
            'status' => 'pending',
        ]);
        $serverKey = 'local-test-server-key';
        IntegrationSetting::query()->create([
            'section' => 'payment_gateway',
            'encrypted_value' => ['provider' => 'midtrans', 'server_key' => $serverKey],
        ]);

        $payload = [
            'order_id' => $payment->external_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'transaction_id' => 'gateway-tx-1',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey);

        $first = $this->postJson('/api/webhooks/payments/midtrans', $payload);
        $first->assertOk()->assertJson(['success' => true, 'duplicate' => false]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, FinanceEntry::query()->where('reference', 'PAYMENT:'.$payment->getKey())->count());

        $second = $this->postJson('/api/webhooks/payments/midtrans', $payload);
        $second->assertOk()->assertJson(['success' => true, 'duplicate' => true]);
        $this->assertSame(1, FinanceEntry::query()->where('reference', 'PAYMENT:'.$payment->getKey())->count());
    }

    public function test_tripay_checkout_uses_the_provider_signature_and_returns_checkout_url(): void
    {
        $branch = Branch::query()->create(['name' => 'Pusat', 'code' => 'PST']);
        $customer = Customer::query()->create([
            'branch_id' => $branch->getKey(),
            'full_name' => 'Pelanggan Uji',
            'email' => 'pelanggan@example.test',
            'service_type' => 'pppoe',
            'due_day' => 20,
            'auto_billing' => true,
        ]);
        $invoice = Invoice::query()->create([
            'branch_id' => $branch->getKey(),
            'customer_id' => $customer->getKey(),
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'discount' => 0,
            'due_date' => today()->addDays(7),
        ]);
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'provider' => 'tripay',
            'amount' => 100000,
            'status' => 'pending',
        ]);
        IntegrationSetting::query()->create([
            'section' => 'payment_gateway',
            'encrypted_value' => [
                'provider' => 'tripay',
                'api_key' => 'tripay-api-key',
                'secret_key' => 'tripay-private-key',
                'merchant_code' => 'T0001',
                'default_method' => 'BRIVA',
                'sandbox' => true,
            ],
        ]);
        Http::fake(['https://tripay.co.id/api-sandbox/transaction/create' => Http::response([
            'success' => true,
            'data' => ['checkout_url' => 'https://tripay.co.id/checkout/test'],
        ])]);

        $checkoutUrl = app(PaymentGatewayService::class)->createCheckout($payment);

        $this->assertSame('https://tripay.co.id/checkout/test', $checkoutUrl);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://tripay.co.id/api-sandbox/transaction/create'
            && $request->data()['signature'] === hash_hmac('sha256', 'T0001MYN-P'.$payment->getKey().'100000', 'tripay-private-key')
            && $request->data()['merchant_ref'] === 'MYN-P'.$payment->getKey());
    }

    public function test_branch_manager_only_sees_customers_in_their_branch(): void
    {
        $branchA = Branch::query()->create(['name' => 'Cabang A', 'code' => 'CBA']);
        $branchB = Branch::query()->create(['name' => 'Cabang B', 'code' => 'CBB']);
        $customerA = Customer::query()->create(['branch_id' => $branchA->getKey(), 'full_name' => 'A', 'service_type' => 'pppoe', 'due_day' => 20]);
        Customer::query()->create(['branch_id' => $branchB->getKey(), 'full_name' => 'B', 'service_type' => 'pppoe', 'due_day' => 20]);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->getKey()]);

        $this->actingAs($manager);

        $this->assertSame([$customerA->getKey()], Customer::query()->pluck('id')->all());
    }

    public function test_administrator_can_render_the_main_panel_pages(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'branch_id' => null]);
        $this->actingAs($administrator);

        foreach ([
            '/admin',
            '/admin/customers',
            '/admin/branches',
            '/admin/plans',
            '/admin/invoices',
            '/admin/payments',
            '/admin/finance-categories',
            '/admin/finance-entries',
            '/admin/products',
            '/admin/inventory-movements',
            '/admin/attendances',
            '/admin/maintenance-tickets',
            '/admin/work-orders',
            '/admin/routers',
            '/admin/hotspot-vouchers',
            '/admin/users',
            '/admin/operational-reports',
            '/admin/my-attendance',
            '/admin/profile-setup',
            '/admin/application-settings',
            '/admin/company-settings',
            '/admin/integration-settings',
            '/admin/activity-logs',
            '/admin/version-management',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_branch_scope_filters_nested_branch_relations(): void
    {
        $branchA = Branch::query()->create(['name' => 'Cabang A', 'code' => 'CBA']);
        $branchB = Branch::query()->create(['name' => 'Cabang B', 'code' => 'CBB']);
        $odcA = FiberOdc::query()->create(['branch_id' => $branchA->getKey(), 'code' => 'ODC-A', 'name' => 'ODC A', 'active' => true]);
        $odcB = FiberOdc::query()->create(['branch_id' => $branchB->getKey(), 'code' => 'ODC-B', 'name' => 'ODC B', 'active' => true]);
        $coreA = FiberCore::query()->create(['odc_id' => $odcA->getKey(), 'core_number' => 1, 'color' => 'biru', 'status' => 'available']);
        FiberCore::query()->create(['odc_id' => $odcB->getKey(), 'core_number' => 2, 'color' => 'merah', 'status' => 'available']);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->getKey()]);

        $this->actingAs($manager);

        $this->assertSame([$coreA->getKey()], FiberCore::query()->pluck('id')->all());

        $this->expectException(ValidationException::class);
        FiberCore::query()->create(['odc_id' => $odcB->getKey(), 'core_number' => 3, 'color' => 'hijau', 'status' => 'available']);
    }

    public function test_branch_scope_limits_stock_to_branch_but_allows_shared_service_plans(): void
    {
        $branchA = Branch::query()->create(['name' => 'Cabang A', 'code' => 'CBA']);
        $branchB = Branch::query()->create(['name' => 'Cabang B', 'code' => 'CBB']);
        $sharedPlan = Plan::query()->create(['name' => 'Paket Bersama', 'service_type' => 'pppoe', 'monthly_price' => 100000, 'active' => true]);
        $branchPlan = Plan::query()->create(['branch_id' => $branchA->getKey(), 'name' => 'Paket A', 'service_type' => 'pppoe', 'monthly_price' => 120000, 'active' => true]);
        Plan::query()->create(['branch_id' => $branchB->getKey(), 'name' => 'Paket B', 'service_type' => 'pppoe', 'monthly_price' => 140000, 'active' => true]);
        $branchProduct = Product::query()->create(['branch_id' => $branchA->getKey(), 'sku' => 'ONT-A', 'name' => 'ONT A', 'category' => 'onu', 'unit' => 'pcs', 'active' => true]);
        Product::query()->create(['sku' => 'ONT-SHARED', 'name' => 'ONT Bersama', 'category' => 'onu', 'unit' => 'pcs', 'active' => true]);
        Product::query()->create(['branch_id' => $branchB->getKey(), 'sku' => 'ONT-B', 'name' => 'ONT B', 'category' => 'onu', 'unit' => 'pcs', 'active' => true]);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->getKey()]);

        $this->actingAs($manager);

        $this->assertSame([$sharedPlan->getKey(), $branchPlan->getKey()], Plan::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame([$branchProduct->getKey()], Product::query()->pluck('id')->all());
    }

    public function test_users_with_incomplete_account_setup_are_redirected_to_profile_setup(): void
    {
        $user = User::factory()->create(['force_password_change' => true, 'email_verified_at' => null]);
        $this->actingAs($user);

        $this->get('/admin/customers')->assertRedirect('/admin/profile-setup');
        $this->get('/admin/profile-setup')->assertOk();
    }

    public function test_saving_profile_cannot_clear_a_required_password_change(): void
    {
        $user = User::factory()->create([
            'force_password_change' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(ProfileSetup::class)
            ->set('data.name', $user->name)
            ->call('saveProfile')
            ->assertHasErrors(['data.password']);

        $this->assertTrue($user->fresh()->force_password_change);
    }

    public function test_non_global_user_cannot_manage_or_create_administrator_accounts(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'branch_id' => null]);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => null]);

        $this->actingAs($manager);

        $this->assertFalse(UserResource::getEloquentQuery()->whereKey($administrator->getKey())->exists());
        $this->assertFalse(UserResource::canEdit($administrator));
        $this->assertFalse(ActivityLogs::canAccess());

        $this->expectException(ValidationException::class);
        User::factory()->create(['role' => 'administrator', 'branch_id' => null]);
    }
}
