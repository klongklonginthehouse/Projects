<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Report extends Model
{
    protected $fillable = ['user_id', 'resource_id', 'reason', 'resolved_at'];

    protected function reason(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: function ($value) {
                if ($value === null || $value === '') {
                    return $value;
                }

                try {
                    return Crypt::encryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
        );
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
