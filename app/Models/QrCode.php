<?php

// app/Models/QrCode.php
namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use BelongsToEvent;

    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'source',
        'destination_url',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($qrCode) {
            $qrCode->uuid = Str::uuid();
        });
    }

    public function scans()
    {
        return $this->hasMany(QrCodeScan::class);
    }
}