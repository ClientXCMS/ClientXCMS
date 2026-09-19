<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropTemplateForeignKey();

        Schema::table('email_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('template')->nullable()->change();
            $table->foreign('template')->references('id')->on('email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Rows archived without a template have no equivalent in the old shape.
        DB::table('email_messages')->whereNull('template')->delete();

        $this->dropTemplateForeignKey();

        Schema::table('email_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('template')->nullable(false)->change();
            $table->foreign('template')->references('id')->on('email_templates')->cascadeOnDelete();
        });
    }

    // Drops by the name the database reports: dropForeign(['template']) rebuilds a conventional name that a restored dump may not use.
    private function dropTemplateForeignKey(): void
    {
        foreach (Schema::getForeignKeys('email_messages') as $foreignKey) {
            if ($foreignKey['columns'] === ['template'] && $foreignKey['foreign_table'] === 'email_templates') {
                Schema::table('email_messages', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }
    }
};
