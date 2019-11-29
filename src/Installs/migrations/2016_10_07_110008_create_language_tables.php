<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLanguageTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Module::generate("Languages", 'languages', 'name', 'fa-language', [
            ["name", "Name", "Name", true, "", 1, 250, true],
            ["iso", "Iso", "String", true, "", 0, 250, true]
        ]);

        Schema::create('texts', function (Blueprint $table) {
            $table->increments('id');
            $table->longText("text");
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("text_id")->unsigned();
            $table->integer("language_id")->unsigned();
            $table->longText("text");
            $table->string("key");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('languages')) {
            Schema::drop('languages');
        }
        if (Schema::hasTable('texts')) {
            Schema::drop('texts');
        }
        if (Schema::hasTable('translations')) {
            Schema::drop('translations');
        }
    }
}
