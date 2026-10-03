<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('opd_id')
                  ->nullable()->constrained('opd')->onDelete('set null');
            $table->string('jabatan')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn(['opd_id','jabatan','is_active']);
        });
    }
};