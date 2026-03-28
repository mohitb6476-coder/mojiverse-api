<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedResult extends Model
{
    protected $fillable = ['user_id', 'message', 'hash'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
