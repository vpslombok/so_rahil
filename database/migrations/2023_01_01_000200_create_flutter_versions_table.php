<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flutter_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_name', 50)->unique()->comment('Contoh: 1.0.2, 1.0.3+5');
            $table->integer('version_code')->unique()->comment('Contoh: 3, 4, 5 (Kode versi Android/iOS)');
            $table->string('file_name', 255)->comment('Link Google Drive APK/AAB');
            $table->string('file_path', 255)->comment('Link Google Drive APK/AAB');
            $table->text('release_notes')->nullable()->comment('Catatan rilis untuk versi ini');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->boolean('is_latest')->default(false)->comment('1 jika ini versi terbaru, 0 jika bukan');
            // Tidak ada kolom updated_at di skema SQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flutter_versions');
    }
};
