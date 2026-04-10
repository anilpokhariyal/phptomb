<?php

declare(strict_types=1);

namespace Phptomb\Tests;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testDbClassIsLoadable(): void
    {
        $this->assertTrue(class_exists(\DB::class));
    }
}
