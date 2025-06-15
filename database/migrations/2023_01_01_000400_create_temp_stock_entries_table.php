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
        Schema::create('temp_stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('User yang melakukan entri');
            $table->foreignId('stock_opname_event_id')
                ->constrained('stock_opname_events')
                ->onDelete('cascade');
            $table->string('nomor_nota', 50)->comment('Nomor nota yang sedang dikerjakan');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            // Kolom berikut bisa bersifat redundan jika product_id sudah ada,
            // namun dipertahankan sesuai skema awal yang Anda berikan.
            // Data ini bisa diambil melalui relasi dengan tabel products.
            $table->string('product_name', 255)->comment('Nama produk (bisa diambil dari relasi)');
            $table->string('barcode', 100)->nullable()->comment('Barcode produk (bisa diambil dari relasi)');
            $table->string('product_code', 50)->nullable()->comment('Kode produk (bisa diambil dari relasi)');
            $table->integer('system_stock')->comment('Stok sistem dari user_product_stock');
            $table->integer('physical_stock')->nullable()->comment('Stok fisik hasil hitung');
            $table->integer('difference')->nullable()->comment('Selisih sistem dan fisik');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable(); // Sesuai skema SQL: `updated_at` timestamp NULL DEFAULT NULL
            $table->unique(['user_id', 'nomor_nota', 'product_id'], 'uk_user_nota_product');
            $table->index(['user_id', 'nomor_nota'], 'idx_user_nota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_stock_entries');
    }
};
