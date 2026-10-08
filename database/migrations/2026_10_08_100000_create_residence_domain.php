<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->json('settings');
            $table->timestamps();
        });

        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('organization_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'key']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->char('base_currency', 3);
            $table->char('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->timestamp('effective_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'base_currency', 'quote_currency', 'effective_at']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('reference');
            $table->string('type');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->json('features')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->string('status')->default('available');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status']);
            $table->index(['property_id', 'status']);
        });

        Schema::create('unit_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->timestamp('effective_at');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'effective_at']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('occupants')->default(1);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'phone']);
            $table->index(['organization_id', 'status']);
            $table->index('user_id');
        });

        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['unit_id', 'status']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('rent_minor');
            $table->char('currency', 3);
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->char('fx_base_currency', 3)->nullable();
            $table->char('fx_quote_currency', 3)->nullable();
            $table->decimal('fx_rate', 20, 8)->nullable();
            $table->unsignedBigInteger('equivalent_minor')->nullable();
            $table->char('equivalent_currency', 3)->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->unsignedTinyInteger('generation_day');
            $table->unsignedTinyInteger('due_day');
            $table->unsignedTinyInteger('grace_until_day');
            $table->string('prorata_method');
            $table->text('conditions')->nullable();
            $table->string('status');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('utility_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->char('period_key', 7);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('total_minor');
            $table->char('currency', 3);
            $table->string('method');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'property_id', 'type', 'period_key']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('utility_charge_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->string('type');
            $table->char('period_key', 7);
            $table->string('dedupe_key')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->decimal('fx_rate', 20, 8)->nullable();
            $table->date('due_on');
            $table->string('status');
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->unique(['contract_id', 'dedupe_key']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'period_start']);
            $table->index(['tenant_id', 'status']);
            $table->index('due_on');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('amount_minor');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('invoice_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->string('provider')->default('manual');
            $table->string('provider_reference')->nullable();
            $table->string('kind');
            $table->foreignId('reverses_payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('method');
            $table->string('status');
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('declared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_id');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();
            $table->unique(['payment_id', 'invoice_id']);
            $table->index(['invoice_id', 'payment_id']);
        });

        Schema::create('cash_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->string('code', 12);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status');
            $table->timestamp('tenant_confirmed_at')->nullable();
            $table->timestamp('agent_confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->unique('code');
            $table->index(['organization_id', 'status']);
            $table->index(['agent_id', 'status']);
        });

        Schema::create('cash_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->string('reference');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status']);
            $table->index(['agent_id', 'status']);
        });

        Schema::create('cash_remittance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_remittance_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_collection_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();
            $table->unique('cash_collection_id');
            $table->index('cash_remittance_id');
        });

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('urgency')->default('normal');
            $table->string('status');
            $table->string('photo_path')->nullable();
            $table->unsignedBigInteger('estimated_cost_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->text('quote_note')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['unit_id', 'status']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->date('spent_on');
            $table->string('payee')->nullable();
            $table->string('motif');
            $table->text('comment')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('recorded');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'spent_on']);
            $table->index(['organization_id', 'status']);
            $table->index(['unit_id', 'spent_on']);
        });

        Schema::create('maintenance_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status_to')->nullable();
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('maintenance_request_id');
        });

        Schema::create('move_out_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('requested_on');
            $table->date('planned_on');
            $table->text('reason')->nullable();
            $table->string('status');
            $table->json('checklist')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['contract_id', 'status']);
        });

        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('pair_key');
            $table->string('subject')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'pair_key']);
        });

        Schema::create('message_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['message_thread_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->index(['message_thread_id', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organization_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('user_id');
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->date('sent_on');
            $table->timestamps();
            $table->unique(['invoice_id', 'kind', 'sent_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_participants');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('move_out_requests');
        Schema::dropIfExists('maintenance_updates');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('cash_remittance_items');
        Schema::dropIfExists('cash_remittances');
        Schema::dropIfExists('cash_collections');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('utility_charges');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('rental_requests');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('unit_price_histories');
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('organization_counters');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
