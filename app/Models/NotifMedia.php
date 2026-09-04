<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'label',
    'type',
    'path',
    'url',
    'created_by',
])]
class NotifMedia extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_AUDIO = 'audio';

    protected $table = 'notif_media';

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_IMAGE => 'Gambar',
            self::TYPE_AUDIO => 'Audio',
        ];
    }
}
