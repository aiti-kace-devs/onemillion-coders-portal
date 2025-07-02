<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'duration',
        'start_date',
        'end_date',
        'status'
    ];

    public function centre(){
        return $this->belongsToMany(Centre::class, 'courses');
    }
}
