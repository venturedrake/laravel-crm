<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Deleting a custom field from the settings pages used to delete only the field,
 * leaving its links to models (field_models) and its values (field_values) live.
 * Every record created afterwards for a linked model then read the default of a
 * field that was gone, and failed. FieldObserver now removes both on delete.
 *
 * Remove the ones left behind by a delete from before that change: any live link
 * or value whose field is deleted or missing. They are soft-deleted, as a delete
 * through the observers would have done. This uses the query builder only, so no
 * observers run, and running it again changes nothing.
 *
 * down() is a no-op - the rows can't be told from any removed by a later delete.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = config('laravel-crm.db_table_prefix');
        $fields = $prefix.'fields';

        if (! Schema::hasTable($fields)) {
            return;
        }

        foreach ([$prefix.'field_models', $prefix.'field_values'] as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'field_id')
                || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            DB::table($table)
                ->whereNull($table.'.deleted_at')
                ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                    ->from($fields)
                    ->whereColumn($fields.'.id', $table.'.field_id')
                    ->whereNull($fields.'.deleted_at'))
                ->update(['deleted_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
