<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_connections', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->jsonb('credentials');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace_product_id');
            $table->string('marketplace_sku')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['product_id', 'marketplace_connection_id']);
        });

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('marketplace_order_id')->unique();
            $table->jsonb('raw_data')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_sync_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_connection_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 10);
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->jsonb('request_body')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_log');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_product_mappings');
        Schema::dropIfExists('marketplace_connections');
    }
};
