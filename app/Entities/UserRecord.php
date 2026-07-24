<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRecord extends Model
{
    protected $table = 'users';
    public $timestamps = false;
    protected $hidden = ['password', 'api_token'];

    protected function casts(): array
    {
        return ['id' => 'integer'];
    }
}
