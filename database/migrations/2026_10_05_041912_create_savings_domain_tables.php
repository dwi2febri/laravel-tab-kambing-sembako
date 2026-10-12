<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->string('role')->default('user')->index();
            $t->boolean('is_active')->default(true);
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type');
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('stock')->default(0);
            $t->string('thumbnail')->nullable();
            $t->string('breed')->nullable();
            $t->string('gender')->nullable();
            $t->unsignedInteger('age_month')->nullable();
            $t->decimal('weight_min', 8, 2)->nullable();
            $t->decimal('weight_max', 8, 2)->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->boolean('is_featured')->default(false);
            $t->timestamps();
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('product_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('item_name');
            $t->decimal('qty', 8, 2);
            $t->string('unit');
            $t->timestamps();
        });
        Schema::create('saving_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('months');
            $t->unsignedBigInteger('monthly_amount')->nullable();
            $t->unsignedBigInteger('down_payment')->default(0);
            $t->unsignedBigInteger('admin_fee')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('user_savings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('saving_plan_id')->constrained();
            $t->string('product_name_snapshot');
            $t->unsignedBigInteger('product_price_snapshot');
            $t->unsignedInteger('tenor_snapshot');
            $t->unsignedBigInteger('monthly_amount_snapshot');
            $t->unsignedBigInteger('down_payment_snapshot')->default(0);
            $t->unsignedBigInteger('admin_fee_snapshot')->default(0);
            $t->unsignedBigInteger('total_paid')->default(0);
            $t->unsignedBigInteger('remaining_amount');
            $t->date('start_date');
            $t->date('estimated_end_date');
            $t->string('status')->default('active')->index();
            $t->timestamps();
        });
        Schema::create('installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_saving_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('installment_number');
            $t->date('due_date')->index();
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('paid_amount')->default(0);
            $t->timestamp('paid_at')->nullable();
            $t->string('status')->default('unpaid')->index();
            $t->timestamps();
            $t->unique(['user_saving_id', 'installment_number']);
        });
        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('bank_name');
            $t->string('account_number');
            $t->string('account_holder');
            $t->string('logo')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('user_saving_id')->constrained();
            $t->foreignId('installment_id')->constrained();
            $t->foreignId('payment_method_id')->constrained();
            $t->unsignedBigInteger('amount');
            $t->string('proof_path');
            $t->text('note')->nullable();
            $t->string('status')->default('pending')->index();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users');
            $t->timestamp('rejected_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('handovers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_saving_id')->unique()->constrained();
            $t->timestamp('requested_at');
            $t->timestamp('scheduled_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('location')->nullable();
            $t->string('recipient_name');
            $t->text('note')->nullable();
            $t->string('status')->default('requested')->index();
            $t->foreignId('handled_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->string('title');
            $t->text('message');
            $t->json('data')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action')->index();
            $t->text('description');
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('type')->default('string');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'activity_logs', 'notifications', 'handovers', 'payments', 'payment_methods', 'installments', 'user_savings', 'saving_plans', 'product_items', 'product_images', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['phone', 'address', 'role', 'is_active']));
    }
};
