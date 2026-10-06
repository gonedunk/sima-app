<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_alumni', function (Blueprint $table) {
            $table->id();
            $table->string('npm')->unique();
            $table->string('nama');
            $table->string('program_studi')->nullable();
            $table->year('tahun_lulus');
            $table->string('ijazah_gdrive_id')->nullable(); // ID file di Google Drive
            $table->string('transkrip_gdrive_id')->nullable(); // ID file di Google Drive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_alumni');
    }
};