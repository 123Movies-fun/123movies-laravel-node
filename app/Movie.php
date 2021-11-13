<?php

namespace App;

use \App\Genre;
use \App\Upload;

use Illuminate\Database\Eloquent\Model;
use DB;


class Movie extends Model
{
  protected $table = 'imdb';
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'title', 'akas', 'aspect_ratio', 'awards', 'company', 'country', 'creator', 'director', 'is_released',  'language', 'location', 'mpaa', 'plot', 'poster', 'rating', 'release_date', 'runtime', 'seasons', 'soundmix', 'tagline', 'trailer_link', 'url', 'user_review', 'votes', 'year'
  ];
  public $timestamps = true;


  /* Database Relationships: */
  public function Cast()
  {
    return $this->hasMany('Cast');
  }

  public function Certifications()
  {
    return $this->hasMany('App\Certification');
  }

  public function Genres($limit = 0)
  {
    $return = Array();
    $genres = DB::table("imdb_genres")->where("imdb_id", "=", $this->id)->get();
    foreach($genres as $genre) $return[] = Genre::find($genre->genre_id);

    if($limit != 0) return array_slice($return, 0, $limit);
      else return $return;

  }

  public function Keywords()
  {
    return $this->hasMany('App\Keyword');
  }

  public function Uploads()
  {
    $return = Array();
    $uploads = DB::table("uploads")->where("imdb_id", "=", $this->id);
    return $uploads;
  }

}
