<?php

declare(strict_types=1);

namespace ShopBridge\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class TimestampProvider
{
    public function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->format(DateTimeInterface::RFC3339_EXTENDED);
    }
}
