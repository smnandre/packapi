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

use PackApi\Inspector\MetadataInspector;
use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataInspector::class)]
final class MetadataInspectorTest extends TestCase
{
    public function testGetMetadataReturnsNullWhenNoProviderSupports(): void
    {
        $package = $this->createStub(Package::class);
        $provider = $this->createStub(MetadataProviderInterface::class);
        $provider->method('supports')->willReturn(false);

        $inspector = new MetadataInspector([$provider]);

        $this->assertNull($inspector->getMetadata($package));
    }

    public function testGetMetadataReturnsMetadataWhenProviderSupports(): void
    {
        $package = $this->createStub(Package::class);
        $expectedMetadata = new Metadata('test', 'test', 'test', 'test');

        $provider = $this->createStub(MetadataProviderInterface::class);
        $provider->method('supports')->willReturn(true);
        $provider->method('getMetadata')->willReturn($expectedMetadata);

        $inspector = new MetadataInspector([$provider]);

        $this->assertSame($expectedMetadata, $inspector->getMetadata($package));
    }

    public function testGetMetadataReturnsNullWhenProviderSupportsButReturnsNull(): void
    {
        $package = $this->createStub(Package::class);

        $provider = $this->createStub(MetadataProviderInterface::class);
        $provider->method('supports')->willReturn(true);
        $provider->method('getMetadata')->willReturn(null);

        $inspector = new MetadataInspector([$provider]);

        $this->assertNull($inspector->getMetadata($package));
    }
}
