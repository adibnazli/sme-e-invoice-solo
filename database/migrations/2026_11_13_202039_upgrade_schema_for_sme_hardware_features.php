<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['WALK_IN', 'CONTRACTOR'])->default('WALK_IN')->after('email');
            $table->decimal('credit_limit', 10, 2)->default(5000.00)->after('customer_type');
            $table->decimal('current_debt', 10, 2)->default(0.00)->after('credit_limit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('bulk_unit_type', 20)->nullable()->after('unit_type');
            $table->integer('bulk_conversion_rate')->nullable()->after('bulk_unit_type');
            $table->decimal('bulk_selling_price', 10, 2)->nullable()->after('retail_selling_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['PAID', 'CREDIT_HUTANG'])->default('PAID')->after('payment_method');
            $table->enum('delivery_type', ['COUNTER_PICKUP', 'SITE_DELIVERY'])->default('COUNTER_PICKUP')->after('payment_status');
            $table->text('delivery_address')->nullable()->after('delivery_type');
            $table->enum('invoice_type', ['STANDARD_RECEIPT', 'LHDN_EINVOICE'])->default('STANDARD_RECEIPT')->after('delivery_address');
            $table->enum('lhdn_status', ['NOT_APPLICABLE', 'PENDING_CONSOLIDATION', 'VALIDATED', 'CONSOLIDATED'])->default('PENDING_CONSOLIDATION')->after('invoice_type');
            $table->string('lhdn_uin', 100)->nullable()->after('lhdn_status');
            $table->text('raw_ai_prompt')->nullable()->after('lhdn_uin');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('unit_sold', 20)->default('PIECE')->after('quantity');
            $table->decimal('recorded_cost_price', 10, 2)->default(0.00)->after('unit_sold');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_sold', 'recorded_cost_price']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'delivery_type',
                'delivery_address',
                'invoice_type',
                'lhdn_status',
                'lhdn_uin',
                'raw_ai_prompt'
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['bulk_unit_type', 'bulk_conversion_rate', 'bulk_selling_price']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'credit_limit', 'current_debt']);
        });
    }
};