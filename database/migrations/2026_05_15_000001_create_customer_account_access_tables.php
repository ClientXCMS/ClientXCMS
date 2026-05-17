<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_account_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_customer_id')->constrained('customers', indexName: 'caa_owner_customer_fk')->cascadeOnDelete();
            $table->foreignId('sub_customer_id')->constrained('customers', indexName: 'caa_sub_customer_fk')->cascadeOnDelete();
            $table->foreignId('created_by_customer_id')->nullable()->constrained('customers', indexName: 'caa_created_by_customer_fk')->nullOnDelete();
            $table->json('permissions');
            $table->boolean('all_services')->default(false);
            $table->timestamps();

            $table->unique(['owner_customer_id', 'sub_customer_id'], 'caa_owner_sub_unique');
        });

        Schema::create('customer_account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_customer_id')->constrained('customers', indexName: 'cai_owner_customer_fk')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique('cai_token_unique');
            $table->json('permissions');
            $table->boolean('all_services')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['owner_customer_id', 'email'], 'cai_owner_email_index');
        });

        Schema::create('customer_account_access_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_access_id')->constrained('customer_account_accesses', indexName: 'caas_access_fk')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services', indexName: 'caas_service_fk')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_account_access_id', 'service_id'], 'customer_access_service_unique');
        });

        Schema::create('customer_account_invitation_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_invitation_id')->constrained('customer_account_invitations', indexName: 'cais_invitation_fk')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services', indexName: 'cais_service_fk')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_account_invitation_id', 'service_id'], 'customer_invitation_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_account_invitation_service');
        Schema::dropIfExists('customer_account_access_service');
        Schema::dropIfExists('customer_account_invitations');
        Schema::dropIfExists('customer_account_accesses');
    }
};
