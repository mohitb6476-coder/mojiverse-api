<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoupleResult extends Model
{
    protected $fillable = ['person1', 'person2', 'score', 'message'];

    protected function casts(): array
    {
        return [
            'person1' => 'array',
            'person2' => 'array',
        ];
    }
}
