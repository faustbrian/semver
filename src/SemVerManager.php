<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

use function array_map;
use function preg_match;
use function usort;

/**
 * Central manager for semantic versioning operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[Singleton()]
final class SemVerManager
{
    /**
     * Parse a version string.
     *
     * @param string $version The version string
     */
    public function parse(string $version): Version
    {
        return Version::parse($version);
    }

    /**
     * Try to parse a version string.
     *
     * @param string $version The version string
     */
    public function tryParse(string $version): ?Version
    {
        return Version::tryParse($version);
    }

    /**
     * Check if a string is a valid semantic version.
     *
     * @param string $version The version string
     */
    public function valid(string $version): bool
    {
        return Version::isValid($version);
    }

    /**
     * Create a version from components.
     *
     * @param int                      $major      The major version
     * @param int                      $minor      The minor version
     * @param int                      $patch      The patch version
     * @param null|list<string>|string $preRelease Optional pre-release identifiers
     * @param null|list<string>|string $build      Optional build metadata
     */
    public function create(
        int $major,
        int $minor = 0,
        int $patch = 0,
        array|string|null $preRelease = null,
        array|string|null $build = null,
    ): Version {
        return Version::create($major, $minor, $patch, $preRelease, $build);
    }

    /**
     * Coerce a version-like string into a valid semver.
     *
     * Attempts to extract a valid semver from strings like:
     * - "v1.2.3" -> "1.2.3"
     * - "1.2" -> "1.2.0"
     * - "1" -> "1.0.0"
     *
     * @param string $version The version-like string
     */
    public function coerce(string $version): ?Version
    {
        // Already valid
        if (($parsed = Version::tryParse($version)) instanceof Version) {
            return $parsed;
        }

        // Try to extract version pattern
        if (preg_match('/v?(\d+)(?:\.(\d+))?(?:\.(\d+))?/', $version, $matches) === 1) {
            $major = (int) $matches[1];
            $minor = isset($matches[2]) ? (int) $matches[2] : 0;
            $patch = isset($matches[3]) ? (int) $matches[3] : 0;

            return Version::create($major, $minor, $patch);
        }

        return null;
    }

    /**
     * Parse a version constraint.
     *
     * @param string $constraint The constraint string
     */
    public function parseConstraint(string $constraint): Constraint
    {
        return Constraint::parse($constraint);
    }

    /**
     * Check if a version satisfies a constraint.
     *
     * @param string|Version $version    The version to check
     * @param string         $constraint The constraint
     */
    public function satisfies(Version|string $version, string $constraint): bool
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return Constraint::parse($constraint)->isSatisfiedBy($v);
    }

    /**
     * Compare two versions.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     *
     * @return int Returns < 0 if a < b, 0 if equal, > 0 if a > b
     */
    public function compare(Version|string $a, Version|string $b): int
    {
        $vA = $a instanceof Version ? $a : Version::parse($a);
        $vB = $b instanceof Version ? $b : Version::parse($b);

        return $vA->compareTo($vB);
    }

    /**
     * Check if version A equals version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function eq(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) === 0;
    }

    /**
     * Check if version A is not equal to version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function neq(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) !== 0;
    }

    /**
     * Check if version A is less than version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function lt(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * Check if version A is less than or equal to version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function lte(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * Check if version A is greater than version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function gt(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * Check if version A is greater than or equal to version B.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     */
    public function gte(Version|string $a, Version|string $b): bool
    {
        return $this->compare($a, $b) >= 0;
    }

    /**
     * Compare two versions with an operator.
     *
     * @param string|Version $a  The first version
     * @param string         $op The operator (=, !=, <, <=, >, >=)
     * @param string|Version $b  The second version
     */
    public function cmp(Version|string $a, string $op, Version|string $b): bool
    {
        return match ($op) {
            '=', '==' => $this->eq($a, $b),
            '!=' => $this->neq($a, $b),
            '<' => $this->lt($a, $b),
            '<=' => $this->lte($a, $b),
            '>' => $this->gt($a, $b),
            '>=' => $this->gte($a, $b),
            default => throw new InvalidArgumentException('Unknown operator: '.$op),
        };
    }

    /**
     * Sort versions in ascending order.
     *
     * @param list<string|Version> $versions The versions to sort
     *
     * @return list<Version>
     */
    public function sort(array $versions): array
    {
        $parsed = array_map(
            static fn (Version|string $v): Version => $v instanceof Version ? $v : Version::parse($v),
            $versions,
        );
        usort($parsed, static fn (Version $a, Version $b): int => $a->compareTo($b));

        return $parsed;
    }

    /**
     * Sort versions in descending order.
     *
     * @param list<string|Version> $versions The versions to sort
     *
     * @return list<Version>
     */
    public function rsort(array $versions): array
    {
        $parsed = array_map(
            static fn (Version|string $v): Version => $v instanceof Version ? $v : Version::parse($v),
            $versions,
        );
        usort($parsed, static fn (Version $a, Version $b): int => $b->compareTo($a));

        return $parsed;
    }

    /**
     * Get the maximum version from a list.
     *
     * @param list<string|Version> $versions The versions
     */
    public function max(array $versions): ?Version
    {
        if ($versions === []) {
            return null;
        }

        $sorted = $this->rsort($versions);

        return $sorted[0];
    }

    /**
     * Get the minimum version from a list.
     *
     * @param list<string|Version> $versions The versions
     */
    public function min(array $versions): ?Version
    {
        if ($versions === []) {
            return null;
        }

        $sorted = $this->sort($versions);

        return $sorted[0];
    }

    /**
     * Get the maximum version satisfying a constraint.
     *
     * @param list<string|Version> $versions   The versions
     * @param string               $constraint The constraint
     */
    public function maxSatisfying(array $versions, string $constraint): ?Version
    {
        return VersionCollection::fromVersions(
            array_map(
                static fn (Version|string $v): Version => $v instanceof Version ? $v : Version::parse($v),
                $versions,
            ),
        )->maxSatisfying($constraint);
    }

    /**
     * Get the minimum version satisfying a constraint.
     *
     * @param list<string|Version> $versions   The versions
     * @param string               $constraint The constraint
     */
    public function minSatisfying(array $versions, string $constraint): ?Version
    {
        return VersionCollection::fromVersions(
            array_map(
                static fn (Version|string $v): Version => $v instanceof Version ? $v : Version::parse($v),
                $versions,
            ),
        )->minSatisfying($constraint);
    }

    /**
     * Increment a version's major number.
     *
     * @param string|Version $version The version
     */
    public function incMajor(Version|string $version): Version
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return $v->incrementMajor();
    }

    /**
     * Increment a version's minor number.
     *
     * @param string|Version $version The version
     */
    public function incMinor(Version|string $version): Version
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return $v->incrementMinor();
    }

    /**
     * Increment a version's patch number.
     *
     * @param string|Version $version The version
     */
    public function incPatch(Version|string $version): Version
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return $v->incrementPatch();
    }

    /**
     * Increment a version's pre-release.
     *
     * @param string|Version $version The version
     */
    public function incPreRelease(Version|string $version): Version
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return $v->incrementPreRelease();
    }

    /**
     * Get the difference type between two versions.
     *
     * @param string|Version $a The first version
     * @param string|Version $b The second version
     *
     * @return null|string The difference type or null if identical
     */
    public function diff(Version|string $a, Version|string $b): ?string
    {
        $vA = $a instanceof Version ? $a : Version::parse($a);
        $vB = $b instanceof Version ? $b : Version::parse($b);

        return $vA->diff($vB);
    }

    /**
     * Create a version collection.
     *
     * @param list<string|Version> $versions The versions
     */
    public function collection(array $versions = []): VersionCollection
    {
        if ($versions === []) {
            return new VersionCollection();
        }

        return VersionCollection::fromVersions(
            array_map(
                static fn (Version|string $v): Version => $v instanceof Version ? $v : Version::parse($v),
                $versions,
            ),
        );
    }
}
