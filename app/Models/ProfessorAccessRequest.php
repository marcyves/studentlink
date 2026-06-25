<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorAccessRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'institution',
        'message',
        'status',
    ];
}
