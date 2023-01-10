<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\CacheWarmer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CacheWarmerAggregateTest extends TestCase
{
    public function testInjectWarmersUsingConstructor()
    {
        $warmer = $this->createMock(CacheWarmerInterface::class);
        $warmer
            ->expects($this->once())
            ->method('warmUp');
        $aggregate = new CacheWarmerAggregate([$warmer]);
        $aggregate->warmUp(__DIR__);
    }

    public function testWarmupDoesCallWarmupOnOptionalWarmersWhenEnableOptionalWarmersIsEnabled()
    {
        $warmer = $this->createMock(CacheWarmerInterface::class);
        $warmer
            ->expects($this->never())
            ->method('isOptional');
        $warmer
            ->expects($this->once())
            ->method('warmUp');

        $aggregate = new CacheWarmerAggregate([$warmer]);
        $aggregate->enableOptionalWarmers();
        $aggregate->warmUp(__DIR__);
    }

    public function testWarmupDoesNotCallWarmupOnOptionalWarmersWhenEnableOptionalWarmersIsNotEnabled()
    {
        $warmer = $this->createMock(CacheWarmerInterface::class);
        $warmer
            ->expects($this->once())
            ->method('isOptional')
            ->willReturn(true);
        $warmer
            ->expects($this->never())
            ->method('warmUp');

        $aggregate = new CacheWarmerAggregate([$warmer]);
        $aggregate->warmUp(__DIR__);
    }

    public function testWarmupReturnsFilesOrClasses()
    {
        $warmer = $this->createMock(CacheWarmerInterface::class);
        $warmer
            ->expects($this->never())
            ->method('isOptional');
        $warmer
            ->expects($this->once())
            ->method('warmUp')
            ->willReturn([__CLASS__, __FILE__]);

        $aggregate = new CacheWarmerAggregate([$warmer]);
        $aggregate->enableOptionalWarmers();

        $this->assertSame([__CLASS__, __FILE__], $aggregate->warmUp(__DIR__));
    }

    public function testWarmupChecksInvalidFiles()
    {
        $warmer = $this->createMock(CacheWarmerInterface::class);
        $warmer
            ->expects($this->never())
            ->method('isOptional');
        $warmer
            ->expects($this->once())
            ->method('warmUp')
            ->willReturn([self::class, __DIR__]);

        $aggregate = new CacheWarmerAggregate([$warmer]);
        $aggregate->enableOptionalWarmers();

        $this->expectException(\LogicException::class);
        $aggregate->warmUp(__DIR__);
    }
}
