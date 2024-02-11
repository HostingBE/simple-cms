<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WWvergetenModel extends Model {

protected $fillable = ['gebruiker', 'email', 'code'];

protected $table = 'wwvergeten';
}

?>