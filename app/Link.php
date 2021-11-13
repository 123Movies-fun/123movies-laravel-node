<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use App\User;

class Link extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                'user_id',
                'url',
                'url_canonical',
                'title',
                'description',
                'type',
                'views',
                'images',
                //'image_cover_id' => $request->input('image_cover_id'),
                'embed',
                'author',
                'author_url',
                'provider_name',
                'provider_url',
                'provider_icon',
                'provider_icons',
                'publish_date',
                'license',
                'image',
                'rss_feeds',
    ];

    /**
     * Get the validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'url'    => 'A URL is required.',
            'title'   => 'A title is required.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'url' => 'required|unique:links',
            'title' => 'required',
        ];
    }

    public function validator($data)
    {
		Validator::extend('without_spaces', function($attr, $value){
		    return preg_match('/^\S*$/u', $value);
		});

        // make a new validator object
        $v = Validator::make($data, $this->rules());
        // return the result
        return $v;
    }

    /**
     * Define a one-many relationship with users.
     *
     * @return array
     */
    public function User()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Define a one-many relationship with links.
     *
     * @return array
     */
    public function Tags()
    {
        return $this->belongsTo('App\TagRelation');
    }


    /**
     * Define a one-many relationship with links.
     *
     * @return array
     */
    public function CommunityLinks()
    {
        return $this->hasMany('App\CommunityLinks');
    }


    /**
     * Define a one-many relationship with votes.
     *
     * @return array
     */
    public function Votes()
    {
        return $this->hasMany('Vote');
    }

}
