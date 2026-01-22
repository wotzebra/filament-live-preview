<?php

namespace Wotz\FilamentLivePreview;

use Illuminate\Support\Facades\Cache;

class CachedPreview
{
    public static ?string $cacheStore = null;

    public static int $cacheDuration = 60;

    public function __construct(
        public string $pageClass,
        public string $view,
        public array $data,
        public ?string $locale = null,
    ) {}

    public static function make(
        string $pageClass,
        string $view,
        array $data,
        ?string $locale = null,
    ): CachedPreview {
        return new CachedPreview($pageClass, $view, $data, $locale);
    }

    public function put(string $token, ?int $ttl = null): bool
    {
        $ttl ??= self::$cacheDuration;

        return Cache::store(static::$cacheStore)->put("filament-peek-preview-{$token}", $this, $ttl);
    }

    public static function get(string $token): ?CachedPreview
    {
        return Cache::store(static::$cacheStore)->get("filament-peek-preview-{$token}");
    }
}
