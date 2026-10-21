<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PurchaseOrderService has written both columns since purchase orders could
     * be raised from an order, but no migration ever added them, so saving a
     * purchase order with lines failed on a real database.
     */
    public function up(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        Schema::table($name, function (Blueprint $table) use ($name) {
            if (! Schema::hasColumn($name, 'comments')) {
                $table->text('comments')->nullable()->after('description');
            }

            if (! Schema::hasColumn($name, 'order_product_id')) {
                $table->unsignedBigInteger('order_product_id')->nullable()->index()->after('product_variation_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        if (Schema::hasIndex($name, $name.'_order_product_id_index')) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex($name.'_order_product_id_index');
            });
        }

        $columns = array_values(array_filter(
            ['comments', 'order_product_id'],
            fn ($column) => Schema::hasColumn($name, $column)
        ));

        if ($columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    protected function tableName(): string
    {
        return config('laravel-crm.db_table_prefix').'purchase_order_lines';
    }
};
