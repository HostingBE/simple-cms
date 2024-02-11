<?php

namespace App\Models;


use Cartalyst\Sentinel\Users\EloquentUser as CartalystUser;

class UserModel extends CartalystUser {

    protected $fillable = [
        'email',
        'password',
        'permissions',
        'last_name',
        'first_name',
        'icon',
    ];

}
?>
