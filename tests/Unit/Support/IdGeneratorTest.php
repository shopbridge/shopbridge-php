<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use ShopBridge\Support\IdGenerator;

final class IdGeneratorTest extends TestCase
{
    public function testGenerateProducesRandomHex(): void
    {
        $generator = new IdGenerator();
        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $first);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $second);
        $this->assertNotSame($first, $second);
    }
}
