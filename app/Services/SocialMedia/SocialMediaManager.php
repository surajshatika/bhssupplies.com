<?php

namespace App\Services\SocialMedia;

use App\Services\SocialMedia\Platforms\SocialPlatformInterface;
use App\Services\SocialMedia\Platforms\TwitterService;
use App\Services\SocialMedia\Platforms\TelegramService;
use App\Services\SocialMedia\Platforms\FacebookService;
use App\Services\SocialMedia\Platforms\YouTubeService;
use App\Services\SocialMedia\Platforms\InstagramService;
use App\Services\SocialMedia\Platforms\LinkedInService;
use App\Services\SocialMedia\Platforms\WhatsAppService;
use App\Services\SocialMedia\Platforms\TikTokService;
use App\Services\SocialMedia\Platforms\PinterestService;
use App\Services\SocialMedia\Platforms\RedditService;
use App\Services\SocialMedia\Platforms\SnapchatService;
use App\Services\SocialMedia\Platforms\DiscordService;

class SocialMediaManager
{
    private static array $map = [
        'twitter'   => TwitterService::class,
        'telegram'  => TelegramService::class,
        'facebook'  => FacebookService::class,
        'youtube'   => YouTubeService::class,
        'instagram' => InstagramService::class,
        'linkedin'  => LinkedInService::class,
        'whatsapp'  => WhatsAppService::class,
        'tiktok'    => TikTokService::class,
        'pinterest' => PinterestService::class,
        'reddit'    => RedditService::class,
        'snapchat'  => SnapchatService::class,
        'discord'   => DiscordService::class,
    ];

    public static function make(string $platform): SocialPlatformInterface
    {
        $class = self::$map[$platform] ?? null;
        if (!$class) {
            throw new \InvalidArgumentException("Unknown social platform: {$platform}");
        }
        return new $class();
    }

    public static function platforms(): array
    {
        return array_keys(self::$map);
    }

    public static function platformConfig(): array
    {
        return config('social_media.platforms', []);
    }

    public static function configuredPlatforms(): array
    {
        $configured = [];
        foreach (self::$map as $slug => $class) {
            $service = new $class();
            if ($service->isConfigured()) {
                $configured[] = $slug;
            }
        }
        return $configured;
    }
}
