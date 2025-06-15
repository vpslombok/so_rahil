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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tambahkan baris ini
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'user'])->default('user');
            // $table->timestamp('created_at')->useCurrent(); // Dihapus karena timestamps() akan menanganinya
            // $table->timestamp('updated_at')->useCurrent(); // Dihapus karena timestamps() akan menanganinya
            // Laravel secara otomatis menambahkan updated_at jika menggunakan $table->timestamps()
            // Jika hanya created_at yang dibutuhkan dan tidak ada updated_at di skema SQL, ini sudah cukup.
            // Jika ada updated_at di skema SQL, gunakan $table->timestamps(); atau $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamps(); // Tambahkan ini untuk created_at dan updated_at standar Laravel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};