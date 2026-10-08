<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'address', 'city', 'description', 'photo_path', 'status',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
