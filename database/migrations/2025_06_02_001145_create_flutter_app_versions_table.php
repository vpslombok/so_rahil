<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flutter_app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_name')->unique(); // Nama versi, e.g., "1.0.1"
            $table->string('file_name')->comment('Link Google Drive APK/AAB'); // Link Google Drive, bukan nama file
            $table->string('file_path')->comment('Link Google Drive APK/AAB'); // Link Google Drive, bukan path file
            $table->unsignedBigInteger('file_size')->nullable(); // Ukuran file dalam bytes (opsional, bisa diisi manual jika perlu)
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(false); // Menandakan versi aktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flutter_app_versions');
    }
};
