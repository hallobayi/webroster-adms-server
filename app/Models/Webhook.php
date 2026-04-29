<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $table = 'webhooks';

    protected $fillable = [
        'device_id',
        'url',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
