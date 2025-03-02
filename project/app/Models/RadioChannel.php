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

    protected $fillable = [
        self::LINK,
        self::SRC,
        self::ALT,
        self::BASE_URL,
        self::PHOTO,
        self::TITLE,
        self::AUDIO_URL,
        self::SUBTITLE,
    ];
}