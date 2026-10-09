<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'pair_key', 'subject'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }
}
