<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerAccessAndConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        foreach (['order_items', 'orders', 'shop_expenses', 'user_login_logs', 'products', 'customers', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('role', 20)->default('CASHIER');
            $table->string('status', 20)->default('ACTIVE');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_login_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('login_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('telegram_chat_id', 100)->nullable();
            $table->string('customer_type', 20)->default('WALK_IN');
            $table->decimal('credit_limit', 10, 2)->default(5000.00);
            $table->decimal('current_debt', 10, 2)->default(0.00);
            $table->integer('loyalty_points')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 100)->default('General Hardware');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('unit_type', 20)->default('PIECE');
            $table->string('bulk_unit_type', 20)->nullable();
            $table->integer('bulk_conversion_rate')->nullable();
            $table->string('lhdn_class_code', 10)->nullable()->default('022');
            $table->decimal('supplier_cost_price', 10, 2);
            $table->decimal('retail_selling_price', 10, 2);
            $table->decimal('bulk_selling_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->string('status', 20)->default('ACTIVE');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->default('CASH');
            $table->string('payment_status', 20)->default('PAID');
            $table->string('delivery_type', 20)->default('COUNTER_PICKUP');
            $table->text('delivery_address')->nullable();
            $table->string('invoice_type', 30)->default('STANDARD_RECEIPT');
            $table->string('lhdn_status', 30)->default('PENDING_CONSOLIDATION');
            $table->string('lhdn_uin', 100)->nullable();
            $table->text('raw_ai_prompt')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->string('unit_sold', 20)->default('PIECE');
            $table->decimal('recorded_cost_price', 10, 2)->default(0.00);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('shop_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recorded_by')->constrained('users');
            $table->string('category', 30);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('description');
            $table->timestamps();
        });
    }

    private function createUser(string $name, string $username, string $role): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_owner_access_returns_403_json_for_json_or_api_requests_and_redirects_web_requests(): void
    {
        $cashier = $this->createUser('Cashier One', 'cashier1', 'CASHIER');
        $owner = $this->createUser('Shop Owner', 'owner1', 'OWNER');

        $this->actingAs($cashier)
            ->getJson('/api/ai-insights')
            ->assertStatus(403)
            ->assertExactJson([
                'status' => 'error',
                'message' => 'Access Denied: Admin privileges required.',
            ]);

        $this->actingAs($cashier)
            ->get('/api/ai-insights')
            ->assertStatus(403)
            ->assertExactJson([
                'status' => 'error',
                'message' => 'Access Denied: Admin privileges required.',
            ]);

        $this->actingAs($cashier)
            ->getJson('/products')
            ->assertStatus(403)
            ->assertExactJson([
                'status' => 'error',
                'message' => 'Access Denied: Admin privileges required.',
            ]);

        $this->actingAs($cashier)
            ->get('/products')
            ->assertRedirect(route('pos'))
            ->assertSessionHas('error', 'Access Denied: Admin privileges required.');

        $this->actingAs($owner)
            ->get('/products')
            ->assertOk();
    }

    public function test_service_configs_are_wired_and_controllers_read_from_config(): void
    {
        $this->assertArrayHasKey('groq', config('services'));
        $this->assertArrayHasKey('api_key', config('services.groq'));
        $this->assertArrayHasKey('gemini', config('services'));
        $this->assertArrayHasKey('api_key', config('services.gemini'));
        $this->assertArrayHasKey('telegram', config('services'));
        $this->assertArrayHasKey('bot_token', config('services.telegram'));
        $this->assertArrayHasKey('default_chat_id', config('services.telegram'));

        $owner = $this->createUser('Shop Owner', 'owner_cfg', 'OWNER');

        config(['services.groq.api_key' => '']);

        $this->actingAs($owner)
            ->postJson('/pos/parse-ai-order', ['raw_text' => '10 beg simen'])
            ->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Missing GROQ_API_KEY in .env',
            ]);

        $this->actingAs($owner)
            ->getJson('/api/ai-insights')
            ->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Missing Groq API Key',
            ]);

        config(['services.gemini.api_key' => '']);

        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $fakeImage = UploadedFile::fake()->createWithContent('hardware.png', $pngBinary);

        $this->actingAs($owner)
            ->from('/products/create')
            ->post('/products/scan', ['hardware_image' => $fakeImage])
            ->assertRedirect('/products/create')
            ->assertSessionHas('error', 'System Error: Gemini API Key is missing.');
    }

    public function test_user_model_fillable_matches_users_schema(): void
    {
        $this->assertSame(
            ['name', 'username', 'password', 'role', 'status'],
            (new User)->getFillable()
        );

        $user = User::create([
            'name' => 'Ah Chong',
            'username' => 'ahchong_cashier',
            'password' => Hash::make('secret123'),
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ah Chong',
            'username' => 'ahchong_cashier',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_pdf_and_invoice_show_templates_fix_vert_lhdn_class_and_telegram_leak(): void
    {
        $owner = $this->createUser('Shop Owner', 'owner_tpl', 'OWNER');

        $customer = Customer::create([
            'name' => 'Binaan Maju',
            'phone' => '012-3456789',
            'email' => 'billing@binaan.my',
            'telegram_chat_id' => 'CUST_CHAT_123',
            'customer_type' => 'CONTRACTOR',
            'credit_limit' => 5000,
            'current_debt' => 0,
            'loyalty_points' => 0,
        ]);

        $productWithClass = Product::create([
            'sku' => 'HW-045',
            'name' => 'Steel Bar Y12',
            'category' => 'Steel',
            'unit_type' => 'PIECE',
            'lhdn_class_code' => '045',
            'supplier_cost_price' => 20.00,
            'retail_selling_price' => 28.00,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
            'status' => 'ACTIVE',
        ]);

        $productNullClass = Product::create([
            'sku' => 'HW-NULL',
            'name' => 'Cement 50kg',
            'category' => 'Cement',
            'unit_type' => 'BAG',
            'lhdn_class_code' => null,
            'supplier_cost_price' => 15.00,
            'retail_selling_price' => 20.00,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
            'status' => 'ACTIVE',
        ]);

        $customerOrder = Order::create([
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'total_amount' => 48.00,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
            'delivery_type' => 'COUNTER_PICKUP',
            'invoice_type' => 'STANDARD_RECEIPT',
            'lhdn_status' => 'PENDING_CONSOLIDATION',
        ]);

        OrderItem::create([
            'order_id' => $customerOrder->id,
            'product_id' => $productWithClass->id,
            'quantity' => 1,
            'unit_sold' => 'PIECE',
            'recorded_cost_price' => 20.00,
            'unit_price' => 28.00,
            'subtotal' => 28.00,
        ]);

        OrderItem::create([
            'order_id' => $customerOrder->id,
            'product_id' => $productNullClass->id,
            'quantity' => 1,
            'unit_sold' => 'BAG',
            'recorded_cost_price' => 15.00,
            'unit_price' => 20.00,
            'subtotal' => 20.00,
        ]);

        $customerOrder->load(['customer', 'user', 'items.product']);

        $pdfHtml = view('invoices.pdf', ['order' => $customerOrder])->render();
        $this->assertStringContainsString('012-3456789 | billing@binaan.my', $pdfHtml);
        $this->assertStringNotContainsString('\vert{}', $pdfHtml);
        $this->assertStringContainsString('LHDN Class: 045', $pdfHtml);
        $this->assertStringContainsString('LHDN Class: 022', $pdfHtml);

        config(['services.telegram.default_chat_id' => 'SECRET_STORE_CHAT_999']);

        $walkInOrder = Order::create([
            'user_id' => $owner->id,
            'customer_id' => null,
            'total_amount' => 28.00,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
            'delivery_type' => 'COUNTER_PICKUP',
            'invoice_type' => 'STANDARD_RECEIPT',
            'lhdn_status' => 'PENDING_CONSOLIDATION',
        ]);

        $this->actingAs($owner)
            ->get(route('invoice.show', $walkInOrder->id))
            ->assertOk()
            ->assertDontSee('SECRET_STORE_CHAT_999')
            ->assertDontSee('required>', false);

        $this->actingAs($owner)
            ->get(route('invoice.show', $customerOrder->id))
            ->assertOk()
            ->assertSee('value="CUST_CHAT_123"', false)
            ->assertDontSee('SECRET_STORE_CHAT_999');
    }

    public function test_dashboard_customers_and_pos_templates_fix_ui_and_bulk_cap_bugs(): void
    {
        $owner = $this->createUser('Shop Owner', 'owner_ui', 'OWNER');

        Product::create([
            'sku' => 'CEM-01',
            'name' => 'YTL Cement',
            'category' => 'Cement',
            'unit_type' => 'BAG',
            'bulk_unit_type' => 'PALLET',
            'bulk_conversion_rate' => 40,
            'supplier_cost_price' => 15.00,
            'retail_selling_price' => 20.00,
            'bulk_selling_price' => 760.00,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
            'status' => 'ACTIVE',
        ]);

        $dashboardHtml = $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Min: 10 BAG', $dashboardHtml);
        $this->assertStringContainsString('5 BAG', $dashboardHtml);
        $this->assertStringContainsString('.ai-mini-card {', $dashboardHtml);
        $this->assertStringNotContainsString('.ai-Mini-card', $dashboardHtml);

        $customersHtml = $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="email"[^>]*>.*?<\/div>\s*<\/div>\s*<div class="mb-3">\s*<label[^>]*>.*?Telegram Chat ID/s',
            $customersHtml
        );

        $this->actingAs($owner)
            ->get(route('pos'))
            ->assertOk()
            ->assertSee('getConversionRate', false)
            ->assertSee('getMaxAvailableQty', false);
    }
}
