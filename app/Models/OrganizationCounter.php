<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationCounter extends Model
{
    protected $fillable = ['organization_id', 'key', 'value'];
}
