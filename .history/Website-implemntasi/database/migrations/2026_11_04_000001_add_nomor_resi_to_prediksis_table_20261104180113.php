<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediksis', function (Blueprint $table) {
            $table->string('nomor_resi', 60)->nullable()->after('label_dataset');
            $table->string('metode_baca', 20)->nullable()->after('nomor_resi');
            $table->boolean('label_ketemu')->nullable()->after('metode_baca');
        });
    }
    public function down(): void
    {
        Schema::table('prediksis', function (Blueprint $table) {
            $table->dropColumn(['nomor_resi', 'metode_baca', 'label_ketemu']);
        });
    }
};
