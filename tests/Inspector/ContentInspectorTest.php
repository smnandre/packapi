<?php

declare(strict_types=1);

/*
 * This file is part of the smnandre/packapi package.
 *
 * (c) Simon Andre <smn.andre@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PackApi\Tests\Inspector;

use PackApi\Inspector\ContentInspector;
use PackApi\Model\ContentOverview;
use PackApi\Package\Package;
use PackApi\Provider\ContentProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentInspector::class)]
final class ContentInspectorTest extends TestCase
{
    public function testGetContentOverviewReturnsNullWhenNoProviderSupports(): void
    {
        $package = $this->createStub(Package::class);

        $provider = new class implements ContentProviderInterface {
            public function supports(Package $package): bool
            {
                return false;
            }

            public function getContentOverview(Package $package): ?ContentOverview
            {
                throw new \LogicException('Should not be called');
            }
        };

        $inspector = new ContentInspector([$provider]);

        $this->assertNull($inspector->getContentOverview($package));
    }

    public function testGetContentOverviewReturnsOverviewWhenProviderSupports(): void
    {
        $package = $this->createStub(Package::class);
        $expected = new ContentOverview(0, 0);

        $provider = new class($expected) implements ContentProviderInterface {
            public function __construct(private ContentOverview $overview)
            {
            }

            public function supports(Package $package): bool
            {
                return true;
            }

            public function getContentOverview(Package $package): ContentOverview
            {
                return $this->overview;
            }
        };

        $inspector = new ContentInspector([$provider]);

        $this->assertSame($expected, $inspector->getContentOverview($package));
    }

    public function testGetContentOverviewFallsBackWhenSupportingProviderReturnsNull(): void
    {
        $package = $this->createStub(Package::class);
        $expected = new ContentOverview(1, 10);
        $empty = $this->createStub(ContentProviderInterface::class);
        $empty->method('supports')->willReturn(true);
        $empty->method('getContentOverview')->willReturn(null);
        $fallback = $this->createStub(ContentProviderInterface::class);
        $fallback->method('supports')->willReturn(true);
        $fallback->method('getContentOverview')->willReturn($expected);

        $this->assertSame($expected, (new ContentInspector([$empty, $fallback]))->getContentOverview($package));
    }
}
