<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('conditions');
            $table->unsignedTinyInteger('guarantee_deposit_months')->nullable()->after('attachment_path');
            $table->unsignedTinyInteger('guarantee_advance_months')->nullable()->after('guarantee_deposit_months');
        });

        Schema::create('recognition_deeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->string('payee_name');
            $table->unsignedTinyInteger('deposit_months');
            $table->unsignedTinyInteger('advance_months');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('identity_document')->nullable();
            $table->string('identity_path')->nullable();
            $table->string('origin')->nullable();
            $table->string('premises');
            $table->text('landlord_witnesses')->nullable();
            $table->text('tenant_witnesses')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->string('certificate_code')->nullable();
            $table->string('certificate_holder')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
            $table->unique('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recognition_deeds');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'guarantee_deposit_months', 'guarantee_advance_months']);
        });
    }
};
