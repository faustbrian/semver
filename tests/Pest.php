<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Version;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Create a Version from a string.
 *
 * @param string $version The version string
 *
 * @return Version The parsed version
 */
function version(string $version): Version
{
    return Version::parse($version);
}
