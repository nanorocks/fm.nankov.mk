<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadioChannel extends Model
{
    public const TABLE = 'radio_channels';
    protected $table = self::TABLE;

    public const LINK = 'link';
    public const SRC = 'src';
    public const ALT = 'alt';
    public const BASE_URL = 'base_url';
    public const PHOTO = 'photo';
    public const TITLE = 'title';
    public const AUDIO_URL = 'audio_url';
    public const SUBTITLE = 'subtitle';
    public const PUBLISHED = 'published';

    protected $hidden = [
        self::LINK,
        self::SRC,
        self::ALT,
        self::BASE_URL,
        // 'created_at',
        // 'updated_at',
    ];

    protected $fillable = [
        self::LINK,
        self::SRC,
        self::ALT,
        self::BASE_URL,
        self::PHOTO,
        self::TITLE,
        self::AUDIO_URL,
        self::SUBTITLE,
        self::PUBLISHED
    ];

    /**
     * Mutator to ensure the photo path is always prefixed with /storage/photos/
     * and to avoid storing empty values.
     *
     * @param string|null $value
     */
    public function setPhotoAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes[self::PHOTO] = '/storage/' . ltrim($value, '/');
        }
    }
}
