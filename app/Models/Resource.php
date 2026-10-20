<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Resource extends Model
{
    protected $fillable = [
        'user_id', 'department_id', 'title', 'course_code', 'instructor', 'type',
        'description', 'file_path', 'original_name', 'status', 'is_official', 'verified_at',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['file_path'];

    protected $appends = ['cost'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    protected function description(): Attribute
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

    /** Download price in credits. Official material and syllabi are free. */
    public function getCostAttribute(): int
    {
        if ($this->is_official || $this->type === 'syllabus') {
            return 0;
        }

        return $this->type === 'past_exam' ? 3 : 2;
    }
}
