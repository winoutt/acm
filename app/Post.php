<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $with = ['content'];

    public function content()
    {
        return $this->hasOne('App\PostContent');
    }

    public function album ()
    {
        return $this->hasMany('App\PostAlbumPhoto');
    }
}
