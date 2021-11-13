<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Page;
use App\Image;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    

  public function pages()
  {
    return $this->hasMany('App\Page');
  }

  public function createdCommunities()
  {
    return $this->hasMany('App\Community');
  }

  public function Avatar() {
    if($this->profile_image_id) {
        $image = Image::find($this->profile_image_id);
        if($image) return "/cdn/".$this->id."/thumbnails/".$image->filename;
    }
  }
    
}
