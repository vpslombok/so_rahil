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
        Schema::create('so_selected_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Asumsi tabel produk bernama 'products'
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // User yang menambahkan
            $table->timestamps();

            $table->unique('product_id', 'so_selected_product_unique'); // Pastikan produk hanya dipilih sekali
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_selected_products');
    }
};
