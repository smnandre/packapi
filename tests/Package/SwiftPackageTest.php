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

namespace PackApi\Tests\Package;

use PackApi\Exception\ValidationException;
use PackApi\Package\SwiftPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SwiftPackage::class)]
final class SwiftPackageTest extends TestCase
{
    public function testConstructorBuildsGitHubPackage(): void
    {
        $package = new SwiftPackage('Alamofire', 'Alamofire');

        $this->assertSame('Alamofire/Alamofire', $package->getName());
        $this->assertSame('Alamofire/Alamofire', $package->getIdentifier());
        $this->assertSame('Alamofire', $package->getOwner());
        $this->assertSame('Alamofire', $package->getRepository());
        $this->assertSame('https://github.com/Alamofire/Alamofire', $package->getRepositoryUrl());
    }

    public function testInvalidOwnerThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        new SwiftPackage('/owner', 'repository');
    }

    public function testInvalidRepositoryThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        new SwiftPackage('owner', 'repository/name');
    }
}
