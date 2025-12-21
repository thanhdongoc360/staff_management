<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\NewNotificationEvent;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'date',
        'is_read',
    ];

    protected $casts = [
        'date' => 'date',
        'is_read' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function (Notification $notification) {
            event(new NewNotificationEvent($notification));
        });
    }

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
