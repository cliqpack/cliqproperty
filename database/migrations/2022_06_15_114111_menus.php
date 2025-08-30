<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class Menus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('menu_title', 255)->default(NULL);
            $table->integer('parent_id')->default(0);
            $table->string('sort_order')->default(0);
            $table->string('slug', 255)->default(NULL);
            $table->timestamps();
        });
        DB::table('menus')->insert([
            [
                'menu_title' => 'Property',
                'parent_id' => 1,
                'sort_order' => 1,
                'slug' => 'propertylist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Contact',
                'parent_id' => 2,
                'sort_order' => 2,
                'slug' => 'contactList',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Inspection',
                'parent_id' => 3,
                'sort_order' => 3,
                'slug' => 'inspections',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Listing',
                'parent_id' => 4,
                'sort_order' => 4,
                'slug' => 'listing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Maintenance',
                'parent_id' => 5,
                'sort_order' => 5,
                'slug' => 'maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Tasks',
                'parent_id' => 6,
                'sort_order' => 6,
                'slug' => 'tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_title' => 'Reminders',
                'parent_id' => 7,
                'sort_order' => 7,
                'slug' => 'allReminders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
