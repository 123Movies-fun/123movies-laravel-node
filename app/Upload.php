<?php

namespace App;

use \App\Genre;
use Illuminate\Database\Eloquent\Model;
use DB;

class Upload extends Model
{
  protected $table = 'uploads';
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'server_id', 'imdb_id', 'ident_id', 'finished_at', 'last_checked', 'size_bytes', 'quality', 'views', 'episode_num', 'title'
  ];
  public $timestamps = true;

  /* Database Relationships: */
  public function Movie()
  {
    return $this->belongsTo('Movie');
  }

  public function Server()
  {
    return $this->belongsTo('Server');
  }
}
