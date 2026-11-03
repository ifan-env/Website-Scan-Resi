<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediksis', function (Blueprint $table) {
            $table->id();
            $table->string('gambar');
            $table->string('nama_asli')->nullable();
            $table->string('sumber', 20)->default('upload');
            $table->string('label', 40);
            $table->string('nama_kelas', 60);
            $table->decimal('confidence', 6, 4)->default(0);
            $table->decimal('prob_ada_resi', 6, 4)->default(0);
            $table->decimal('prob_tidak_ada_resi', 6, 4)->default(0);
            $table->unsignedInteger('waktu_proses_ms')->default(0);
            $table->unsignedSmallInteger('jumlah_fitur')->default(1812);
            $table->string('label_dataset', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediksis');
    }
};
