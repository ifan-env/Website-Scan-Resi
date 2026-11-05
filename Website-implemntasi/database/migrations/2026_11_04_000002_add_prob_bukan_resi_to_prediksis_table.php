<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Model menjadi 3 kelas (ada_resi / bukan_resi / tidak_ada_resi),
     * jadi probabilitas kelas bukan_resi perlu kolomnya sendiri,
     * sejajar prob_ada_resi dan prob_tidak_ada_resi yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('prediksis', function (Blueprint $table) {
            $table->decimal('prob_bukan_resi', 6, 4)->default(0)->after('prob_tidak_ada_resi');
        });
    }

    public function down(): void
    {
        Schema::table('prediksis', function (Blueprint $table) {
            $table->dropColumn('prob_bukan_resi');
        });
    }
};
