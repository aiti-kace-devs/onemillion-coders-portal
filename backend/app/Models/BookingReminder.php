<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingReminder extends Model
{
    use HasFactory;

    protected $table = 'booking_reminders';

    protected $fillable = [
        'booking_id',
        'type',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
