<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Support\AddressContent;

/*
 * The order and delivery forms prefill the country from settings, and the
 * services used to save every address the form sent - so an order or delivery
 * saved with its address left blank got one that was only a country
 * ("United States"). The services now skip a blank address.
 *
 * Remove the ones saved before that change: any live order or delivery address
 * with nothing in it but a country (AddressContent::isBlank). The fields may be
 * encrypted, so the rows are read through the model and checked in PHP. They
 * are soft-deleted, as a delete through the model would have done, with the
 * query builder, so no observers run; running it again changes nothing.
 *
 * Team (settings) addresses and purchase orders' copies of them are left
 * alone - the purchase order form just no longer offers one that is only a
 * country.
 *
 * down() is a no-op - the rows can't be told from any removed by a later edit.
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
        $table = config('laravel-crm.db_table_prefix').'addresses';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        $blankIds = [];

        Address::withoutGlobalScopes()
            ->whereIn('addressable_type', [Order::class, Delivery::class])
            ->whereNull('deleted_at')
            ->chunkById(500, function ($addresses) use (&$blankIds) {
                foreach ($addresses as $address) {
                    if (AddressContent::isBlank($address)) {
                        $blankIds[] = $address->id;
                    }
                }
            });

        foreach (array_chunk($blankIds, 500) as $ids) {
            DB::table($table)->whereIn('id', $ids)->update(['deleted_at' => now()]);
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
