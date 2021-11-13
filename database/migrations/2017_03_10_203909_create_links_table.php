<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('links', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("user_id")->default(0);
            $table->string("url")->length(2083)->default("");
            $table->string("url_canonical")->length(2083)->default("");
            $table->string("title")->length(350)->default("");
            $table->text("description")->nullable();
            $table->string("type")->length(100);
            $table->integer("views")->length(32)->default(1);
            $table->date("last_viewed");
            $table->text("images")->nullable();
            $table->integer("image_cover_id")->length(32)->default(0);
            $table->text("embed")->nullable();
            $table->string("author_name")->length(300)->default("")->nullable();
            $table->string("author_url")->length(2083)->default("")->nullable();
            $table->string("provider_name")->length(100)->default("")->nullable();
            $table->string("provider_url")->length(2083)->default("")->nullable();
            $table->string("provider_icon")->length(2083)->default("")->nullable();
            $table->text("provider_icons")->nullable();
            $table->date("publish_date");
            $table->string("license")->length(100)->default("")->nullable();
            $table->text("rss_feeds")->nullable();
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
        Schema::dropIfExists('links');
    }
}
