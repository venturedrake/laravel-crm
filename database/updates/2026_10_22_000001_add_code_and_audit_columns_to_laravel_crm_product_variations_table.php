<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The user columns the ProductVariationObserver writes.
     *
     * Indexed but without a foreign key, unlike the original stubs: an install
     * that added any of these by hand would fail the constraint on rows whose
     * user has since gone.
     */
    protected $userColumns = [
        'user_created_id',
        'user_updated_id',
        'user_deleted_id',
        'user_restored_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        Schema::table($name, function (Blueprint $table) use ($name) {
            if (! Schema::hasColumn($name, 'code')) {
                $table->string('code')->nullable()->after('name');
            }

            if (! Schema::hasColumn($name, 'barcode')) {
                $table->string('barcode')->nullable()->after('code');
            }

            if (! Schema::hasColumn($name, 'order')) {
                $table->integer('order')->nullable();
            }

            if (! Schema::hasColumn($name, 'active')) {
                $table->boolean('active')->default(true);
            }

            foreach ($this->userColumns as $column) {
                if (! Schema::hasColumn($name, $column)) {
                    $table->unsignedBigInteger($column)->nullable()->index();
                }
            }
        });

        if (! Schema::hasIndex($name, $this->codeIndex())) {
            Schema::table($name, function (Blueprint $table) {
                $table->index('code', $this->codeIndex());
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * The indexes go first: SQLite refuses to drop an indexed column.
     */
    public function down(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        $indexes = array_merge([$this->codeIndex()], array_map(
            fn ($column) => $name.'_'.$column.'_index',
            $this->userColumns
        ));

        foreach ($indexes as $index) {
            if (Schema::hasIndex($name, $index)) {
                Schema::table($name, function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        $columns = array_values(array_filter(
            array_merge(['code', 'barcode', 'order', 'active'], $this->userColumns),
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
        return config('laravel-crm.db_table_prefix').'product_variations';
    }

    protected function codeIndex(): string
    {
        return $this->tableName().'_code_index';
    }
};
