<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientToLaravelCrmDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
            $table->string('url')->nullable();
        });  
        
        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'quotes', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'clients', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
                
       
        Schema::table(config('laravel-crm.db_table_prefix').'users', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table('crm_teams', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
        
        Schema::table(config('laravel-crm.db_table_prefix').'products', function (Blueprint $table) {
            $table->string('url')->nullable();
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'leads', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'deals', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'quotes', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'orders', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'invoices', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'deliveries', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'clients', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'organisations', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'people', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'users', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table('crm_teams', function (Blueprint $table) {
             $table->dropColumn('url');
        });
        
        Schema::table(config('laravel-crm.db_table_prefix').'products', function (Blueprint $table) {
             $table->dropColumn('url');
        });
    }
}
