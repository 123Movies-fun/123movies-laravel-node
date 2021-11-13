<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImdbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imdb', function (Blueprint $table) {
            $table->increments('id');
            $table->string("title")->length(350);
            $table->text("akas");
            $table->string("aspect_ratio")->nullable();
            $table->string("awards")->nullable();

            //the following columns need their own tables + many/to/one relationships. 
            //$table->text("cast_linked")->nullable();
            //$table->text("cast")->nullable();
            // $table->text("CastAndCharacterLinked")->nullable();
            // $table->text("Certification")->nullable();
            //$table->text("genre")->nullable();
            //$table->text("keywords")->nullable();
            //$writers 


            $table->string("company")->nullable();
            $table->string("country")->nullable();
            $table->string("creator")->nullable();
            $table->string("director")->nullable();
            $table->string("is_released")->nullable();
            $table->string("language")->nullable();
            $table->string("location")->nullable();
            $table->text("mpaa")->nullable();
            $table->text("plot")->nullable();
            $table->string("poster")->length(2078);
            $table->string("rating")->length(10);
            $table->date("release_date");
            $table->string("runtime")->length(2078);
            $table->text("seasons");
            $table->text("soundmix");
            $table->text("tagline");
            $table->string("trailer_link")->length(2078);
            $table->string("url")->length(2078);
            $table->text("user_review");
            $table->integer("votes");
            $table->integer("year");




            $table->integer("link_id")->default(0)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imdb');
    }
}
