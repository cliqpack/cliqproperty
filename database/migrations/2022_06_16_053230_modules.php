<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Modules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->biginteger('menu_id')->unsigned();
            $table->foreign('menu_id')->references('id')->on('menus');
            $table->string('name');
            $table->string('created_by');
            $table->boolean('soft_delete');
            $table->timestamps();
        });
        DB::table('modules')->insert([
            // Property (menu_id = 1)
            ['menu_id' => 1, 'name' => 'Property-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 1, 'name' => 'Property-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 1, 'name' => 'Property-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 1, 'name' => 'Property-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Contact (menu_id = 2)
            ['menu_id' => 2, 'name' => 'Contact-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 2, 'name' => 'Contact-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 2, 'name' => 'Contact-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 2, 'name' => 'Contact-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Inspection (menu_id = 3)
            ['menu_id' => 3, 'name' => 'Inspection-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 3, 'name' => 'Inspection-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 3, 'name' => 'Inspection-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 3, 'name' => 'Inspection-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Listing (menu_id = 4)
            ['menu_id' => 4, 'name' => 'Listing-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 4, 'name' => 'Listing-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 4, 'name' => 'Listing-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 4, 'name' => 'Listing-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Maintenance (menu_id = 5)
            ['menu_id' => 5, 'name' => 'Maintenance-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 5, 'name' => 'Maintenance-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 5, 'name' => 'Maintenance-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 5, 'name' => 'Maintenance-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Tasks (menu_id = 6)
            ['menu_id' => 6, 'name' => 'Tasks-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 6, 'name' => 'Tasks-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 6, 'name' => 'Tasks-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 6, 'name' => 'Tasks-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        
            // Reminders (menu_id = 8)
            ['menu_id' => 7, 'name' => 'Reminders-Add', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 7, 'name' => 'Reminders-View', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 7, 'name' => 'Reminders-Edit', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => 7, 'name' => 'Reminders-Delete', 'created_by' => 'admin', 'soft_delete' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
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
}
