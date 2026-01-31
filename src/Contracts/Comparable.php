<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer\Contracts;

/**
 * Interface for comparable version components.
 *
 * @author Brian Faust <brian@cline.sh>
 * @template T of Comparable
 */
interface Comparable
{
    /**
     * Compare this instance to another.
     *
     * @param self&T $other The other instance to compare
     *
     * @return int Returns < 0 if this is less than other, 0 if equal, > 0 if greater
     */
    public function compareTo(self $other): int;

    /**
     * Check if this instance equals another.
     *
     * @param self&T $other The other instance to compare
     */
    public function equals(self $other): bool;

    /**
     * Check if this instance is less than another.
     *
     * @param self&T $other The other instance to compare
     */
    public function lessThan(self $other): bool;

    /**
     * Check if this instance is greater than another.
     *
     * @param self&T $other The other instance to compare
     */
    public function greaterThan(self $other): bool;

    /**
     * Check if this instance is less than or equal to another.
     *
     * @param self&T $other The other instance to compare
     */
    public function lessThanOrEquals(self $other): bool;

    /**
     * Check if this instance is greater than or equal to another.
     *
     * @param self&T $other The other instance to compare
     */
    public function greaterThanOrEquals(self $other): bool;
}
