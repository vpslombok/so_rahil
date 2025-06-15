<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('so_selected_products', function (Blueprint $table) {
            // Step 1: Drop the existing foreign key constraint on product_id.
            // Laravel's default name is <table>_<column>_foreign.
            $table->dropForeign('so_selected_products_product_id_foreign');

            // Step 2: Drop the existing unique index on product_id.
            $table->dropUnique('so_selected_product_unique');

            // Step 3: Add the new column and its foreign key constraint.
            $table->foreignId('stock_opname_event_id')
                ->after('id')
                ->constrained('stock_opname_events')
                ->onDelete('cascade');

            // Step 4: Add the new composite unique index.
            $table->unique(['stock_opname_event_id', 'product_id'], 'so_event_product_unique');

            // Step 5: Re-add the foreign key constraint for product_id,
            // referencing the 'products' table.
            $table->foreign('product_id', 'so_selected_products_product_id_foreign_new') // Using a potentially new name for clarity or to avoid issues
                  ->references('id')->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('so_selected_products', function (Blueprint $table) {
            // Step 1: Drop the re-added/new foreign key on product_id.
            $table->dropForeign('so_selected_products_product_id_foreign_new');

            // Step 2: Drop the new composite unique index.
            $table->dropUnique('so_event_product_unique');

            // Step 3: Drop the foreign key for stock_opname_event_id.
            // Laravel's default name is <table>_<column>_foreign.
            $table->dropForeign('so_selected_products_stock_opname_event_id_foreign');
            $table->dropColumn('stock_opname_event_id');

            // Step 4: Restore the original unique index on product_id.
            $table->unique('product_id', 'so_selected_product_unique');

            // Step 5: Restore the original foreign key constraint on product_id.
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // This will recreate 'so_selected_products_product_id_foreign'
        });
    }
};
