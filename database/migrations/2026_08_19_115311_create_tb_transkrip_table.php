<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_transkrip', function (Blueprint $table) {
            $table->id();
            $table->string('npm', 12);
            $table->string('path_file', 255);
            $table->string('nama_file_asli', 255);
            $table->string('diunggah_oleh', 50)->default('mahasiswa');
            $table->timestamps();

            // Foreign key ke tbkelasmahasiswa
            //$table->foreign('npm')->references('npm')->on('tbkelasmahasiswa')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_transkrip');
    }
};