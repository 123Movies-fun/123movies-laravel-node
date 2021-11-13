<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewImdbColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imdb', function (Blueprint $table) {
            $table->string("trailer")->length(2078);
            $table->integer("season");
            $table->integer("episodes_total");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imdb', function (Blueprint $table) {
            $table->dropColumn("trailer");
            $table->dropColumn("season");
            $table->dropColumn("episodes_total");
        });
    }
}
