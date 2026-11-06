<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * A converted lead can't be converted again, and deleting its last deal now
 * clears converted_at so it can. A deal deleted before that change left the lead
 * marked converted with nothing to show for it: no deal, no Convert button, and
 * hidden from the list and the board.
 *
 * Reopen those leads. A lead with no deals at all has nothing to say it was
 * reopened by a delete, so it stays converted, as does one with a deal left. So
 * does a deleted lead, which the observer can't reach either. The stage stays as
 * it is, because nothing records the one before.
 *
 * down() is a no-op - the dates can't be told from any cleared by a later delete.
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
        $leads = $prefix.'leads';
        $deals = $prefix.'deals';

        if (! Schema::hasTable($leads)
            || ! Schema::hasTable($deals)
            || ! Schema::hasColumn($leads, 'converted_at')
            || ! Schema::hasColumn($deals, 'lead_id')) {
            return;
        }

        $dealsOfLead = fn ($query) => $query->select(DB::raw(1))
            ->from($deals)
            ->whereColumn($deals.'.lead_id', $leads.'.id');

        DB::table($leads)
            ->whereNotNull('converted_at')
            ->whereNull($leads.'.deleted_at')
            ->whereExists(fn ($query) => $dealsOfLead($query)->whereNotNull($deals.'.deleted_at'))
            ->whereNotExists(fn ($query) => $dealsOfLead($query)->whereNull($deals.'.deleted_at'))
            ->update(['converted_at' => null]);
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
