<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer\Exceptions;

use function sprintf;

/**
 * Exception thrown when a version string cannot be parsed.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidVersionException extends SemVerException
{
    /**
     * Create exception for invalid version format.
     *
     * @param string $version The invalid version string
     */
    public static function invalidFormat(string $version): self
    {
        return new self(sprintf("Invalid semantic version format: '%s'", $version));
    }

    /**
     * Create exception for negative version number.
     *
     * @param string $component The component name (major, minor, patch)
     * @param int    $value     The negative value
     */
    public static function negativeNumber(string $component, int $value): self
    {
        return new self(sprintf('Version %s must be a non-negative integer, got: %d', $component, $value));
    }

    /**
     * Create exception for leading zeros in version number.
     *
     * @param string $component The component name
     * @param string $value     The value with leading zeros
     */
    public static function leadingZeros(string $component, string $value): self
    {
        return new self(sprintf("Version %s must not have leading zeros: '%s'", $component, $value));
    }
}
