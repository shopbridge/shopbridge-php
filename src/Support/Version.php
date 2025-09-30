<?php

declare(strict_types=1);

namespace ShopBridge\Support;

final class Version
{
    private const FALLBACK = 'unknown';

    public static function string(): string
    {
        if (class_exists(\Composer\InstalledVersions::class) && \Composer\InstalledVersions::isInstalled('shopbridge/shopbridge-php')) {
            $version = \Composer\InstalledVersions::getPrettyVersion('shopbridge/shopbridge-php');

            if (is_string($version) && $version !== '') {
                return $version;
            }
        }

        return self::FALLBACK;
    }
}

