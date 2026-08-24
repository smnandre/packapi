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

require_once __DIR__.'/../vendor/autoload.php';

use PackApi\Inspector\PackageInspectorFacade;
use PackApi\Package\ComposerPackage;

$package = new ComposerPackage('symfony/maker-bundle');
$report = PackageInspectorFacade::defaults()->inspect($package);

echo "PackApi report for {$package->getName()}\n";
echo "=========================================\n";

foreach ($report->getSummary() as $name => $value) {
    if (is_array($value)) {
        $value = [] === $value ? 'none' : implode(', ', $value);
    } elseif (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } elseif (null === $value) {
        $value = 'not available';
    }

    echo str_replace('_', ' ', ucfirst($name)).': '.$value."\n";
}

if ($report->hasSecurityAdvisories()) {
    echo "\nSecurity advisories\n";
    foreach ($report->securityAdvisories ?? [] as $advisory) {
        echo "- {$advisory->severity}: {$advisory->title} ({$advisory->link})\n";
    }
}
