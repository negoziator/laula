<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'content',
        'is_user',
        'role',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_user' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('is_user', true);
    }

    public function scopeAssistantMessages($query)
    {
        return $query->where('is_user', false);
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->sent_at->format('H:i');
    }
}
