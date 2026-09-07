<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task_name');
            $table->string('status', 16);
            $table->timestamp('executed_at');
            $table->decimal('runtime', 10, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->string('exception_class')->nullable();
            $table->integer('exit_code')->nullable();
            $table->timestamps();

            $table->index(['task_name', 'id']);
            $table->index(['status', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
