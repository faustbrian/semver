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
 * Exception thrown when a pre-release identifier is invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidPreReleaseException extends SemVerException
{
    /**
     * Create exception for empty identifier.
     */
    public static function emptyIdentifier(): self
    {
        return new self('Pre-release identifiers must not be empty');
    }

    /**
     * Create exception for invalid characters.
     *
     * @param string $identifier The invalid identifier
     */
    public static function invalidCharacters(string $identifier): self
    {
        return new self(sprintf("Pre-release identifier contains invalid characters: '%s'. Only alphanumerics and hyphens are allowed [0-9A-Za-z-]", $identifier));
    }

    /**
     * Create exception for leading zeros in numeric identifier.
     *
     * @param string $identifier The identifier with leading zeros
     */
    public static function numericLeadingZeros(string $identifier): self
    {
        return new self(sprintf("Numeric pre-release identifier must not have leading zeros: '%s'", $identifier));
    }
}
