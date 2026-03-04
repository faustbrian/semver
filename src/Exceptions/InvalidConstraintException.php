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
 * Exception thrown when a version constraint is invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidConstraintException extends SemVerException
{
    /**
     * Create exception for invalid constraint format.
     *
     * @param string $constraint The invalid constraint string
     */
    public static function invalidFormat(string $constraint): self
    {
        return new self(sprintf("Invalid version constraint format: '%s'", $constraint));
    }

    /**
     * Create exception for unknown operator.
     *
     * @param string $operator The unknown operator
     */
    public static function unknownOperator(string $operator): self
    {
        return new self(sprintf("Unknown constraint operator: '%s'", $operator));
    }
}
