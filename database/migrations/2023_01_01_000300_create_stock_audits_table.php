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
        Schema::create('stock_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->after('id') // Position after id
                  ->comment('User yang melakukan atau terkait dengan audit ini');
            $table->foreignId('stock_opname_event_id')
                  ->constrained('stock_opname_events')
                  ->onDelete('cascade')
                  ->after('user_id') // Position after user_id
                  ->comment('Event Stock Opname terkait');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('nomor_nota', 50)->nullable()->comment('Nomor Nota atau Referensi');
            $table->integer('system_stock');
            $table->integer('physical_stock');
            $table->integer('difference');
            $table->text('notes')->nullable();
            $table->string('checked_by', 100)->nullable()->comment('Nama pengecek jika berbeda dari user_id atau untuk catatan tambahan');
            $table->timestamp('checked_at')->nullable()->useCurrent();
            // Tidak ada kolom created_at atau updated_at di skema SQL,
            // jadi tidak menggunakan $table->timestamps()
            $table->index(['user_id', 'nomor_nota', 'stock_opname_event_id'], 'idx_user_nota_event_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_audits');
    }
};
