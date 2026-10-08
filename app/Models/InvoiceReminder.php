<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    protected $fillable = ['invoice_id', 'kind', 'sent_on'];

    protected function casts(): array
    {
        return ['sent_on' => 'date'];
    }
}
