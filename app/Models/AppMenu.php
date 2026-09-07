<?php

namespace App\Models;

use App\Support\AppMenuHost;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'type',
    'key',
    'judul',
    'url',
    'icon_path',
    'open_mode',
    'package_name',
    'play_store_url',
    'audience',
    'requires_auth',
    'sort_order',
    'is_active',
])]
class AppMenu extends Model
{
    public const TYPE_BUILTIN = 'builtin';

    public const TYPE_CUSTOM = 'custom';

    public const OPEN_WEBVIEW = 'webview';

    public const OPEN_CHROME_TAB = 'chrome_tab';

    public const OPEN_APP = 'app';

    public const AUDIENCE_GURU = 'guru';

    public const AUDIENCE_SISWA = 'siswa';

    public const AUDIENCE_SEMUA = 'semua';

    protected function casts(): array
    {
        return [
            'requires_auth' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isBuiltin(): bool
    {
        return $this->type === self::TYPE_BUILTIN;
    }

    public function isCustom(): bool
    {
        return $this->type === self::TYPE_CUSTOM;
    }

    public function iconUrl(): ?string
    {
        if ($this->icon_path === null || $this->icon_path === '') {
            return null;
        }

        if (str_starts_with($this->icon_path, 'http://') || str_starts_with($this->icon_path, 'https://')) {
            return $this->icon_path;
        }

        return Storage::disk('r2')->url($this->icon_path);
    }

    public function allowsRequiresAuth(): bool
    {
        return $this->isCustom()
            && in_array($this->open_mode, [self::OPEN_WEBVIEW, self::OPEN_CHROME_TAB], true)
            && AppMenuHost::isMadaniHost($this->url);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->where(function (Builder $inner) use ($audience): void {
            $inner->where('audience', $audience)
                ->orWhere('audience', self::AUDIENCE_SEMUA);
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToApi(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->where('type', self::TYPE_BUILTIN)
                ->orWhere(function (Builder $custom): void {
                    $custom->where('type', self::TYPE_CUSTOM)
                        ->where('is_active', true);
                });
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'key' => $this->key,
            'judul' => $this->judul,
            'url' => $this->url,
            'icon_url' => $this->iconUrl(),
            'open_mode' => $this->open_mode,
            'package_name' => $this->package_name,
            'play_store_url' => $this->play_store_url,
            'requires_auth' => (bool) $this->requires_auth,
            'sort_order' => $this->sort_order,
        ];
    }
}
