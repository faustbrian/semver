<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function usort;

/**
 * A collection of versions with filtering and sorting capabilities.
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements IteratorAggregate<int, Version>
 * @psalm-immutable
 */
final readonly class VersionCollection implements Countable, IteratorAggregate
{
    /**
     * @param list<Version> $versions The versions in the collection
     */
    public function __construct(
        private array $versions = [],
    ) {}

    /**
     * Create a collection from an array of version strings.
     *
     * @param list<string> $versions The version strings
     */
    public static function fromStrings(array $versions): self
    {
        return new self(array_map(Version::parse(...), $versions));
    }

    /**
     * Create a collection from an array of versions.
     *
     * @param list<Version> $versions The versions
     */
    public static function fromVersions(array $versions): self
    {
        return new self($versions);
    }

    /**
     * Add a version to the collection.
     *
     * @param string|Version $version The version to add
     */
    public function add(Version|string $version): self
    {
        $v = $version instanceof Version ? $version : Version::parse($version);
        $versions = $this->versions;
        $versions[] = $v;

        return new self($versions);
    }

    /**
     * Get all versions.
     *
     * @return list<Version>
     */
    public function all(): array
    {
        return $this->versions;
    }

    /**
     * Get the count of versions.
     */
    public function count(): int
    {
        return count($this->versions);
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->versions === [];
    }

    /**
     * Get the first version.
     */
    public function first(): ?Version
    {
        return $this->versions[0] ?? null;
    }

    /**
     * Get the last version.
     */
    public function last(): ?Version
    {
        if ($this->versions === []) {
            return null;
        }

        return $this->versions[count($this->versions) - 1];
    }

    /**
     * Sort versions in ascending order.
     */
    public function sorted(): self
    {
        $versions = $this->versions;
        usort($versions, static fn (Version $a, Version $b): int => $a->compareTo($b));

        return new self($versions);
    }

    /**
     * Sort versions in descending order.
     */
    public function rsorted(): self
    {
        $versions = $this->versions;
        usort($versions, static fn (Version $a, Version $b): int => $b->compareTo($a));

        return new self($versions);
    }

    /**
     * Get the highest version.
     */
    public function max(): ?Version
    {
        if ($this->versions === []) {
            return null;
        }

        return $this->rsorted()->first();
    }

    /**
     * Get the lowest version.
     */
    public function min(): ?Version
    {
        if ($this->versions === []) {
            return null;
        }

        return $this->sorted()->first();
    }

    /**
     * Filter versions that satisfy a constraint.
     *
     * @param Constraint|string $constraint The constraint
     */
    public function satisfying(Constraint|string $constraint): self
    {
        $c = $constraint instanceof Constraint ? $constraint : Constraint::parse($constraint);

        return new self(array_values(array_filter(
            $this->versions,
            $c->isSatisfiedBy(...),
        )));
    }

    /**
     * Get the highest version satisfying a constraint.
     *
     * @param Constraint|string $constraint The constraint
     */
    public function maxSatisfying(Constraint|string $constraint): ?Version
    {
        return $this->satisfying($constraint)->max();
    }

    /**
     * Get the lowest version satisfying a constraint.
     *
     * @param Constraint|string $constraint The constraint
     */
    public function minSatisfying(Constraint|string $constraint): ?Version
    {
        return $this->satisfying($constraint)->min();
    }

    /**
     * Filter to only stable versions.
     */
    public function stable(): self
    {
        return new self(array_values(array_filter(
            $this->versions,
            static fn (Version $v): bool => $v->isStable(),
        )));
    }

    /**
     * Filter to only pre-release versions.
     */
    public function preReleases(): self
    {
        return new self(array_values(array_filter(
            $this->versions,
            static fn (Version $v): bool => $v->isPreRelease(),
        )));
    }

    /**
     * Filter versions by major version.
     *
     * @param int $major The major version number
     */
    public function major(int $major): self
    {
        return new self(array_values(array_filter(
            $this->versions,
            static fn (Version $v): bool => $v->major === $major,
        )));
    }

    /**
     * Filter versions by major and minor version.
     *
     * @param int $major The major version number
     * @param int $minor The minor version number
     */
    public function minor(int $major, int $minor): self
    {
        return new self(array_values(array_filter(
            $this->versions,
            static fn (Version $v): bool => $v->major === $major && $v->minor === $minor,
        )));
    }

    /**
     * Get unique versions (by precedence, ignoring build metadata).
     */
    public function unique(): self
    {
        $seen = [];
        $unique = [];

        foreach ($this->versions as $version) {
            $key = $version->core().'-'.$version->preRelease;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $version;
        }

        return new self($unique);
    }

    /**
     * Map versions to an array.
     *
     * @template T
     *
     * @param callable(Version): T $callback The mapping function
     *
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->versions);
    }

    /**
     * Filter versions with a callback.
     *
     * @param callable(Version): bool $callback The filter function
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->versions, $callback)));
    }

    /**
     * Get an iterator for the versions.
     *
     * @return Traversable<int, Version>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->versions);
    }

    /**
     * Convert to array of strings.
     *
     * @return list<string>
     */
    public function toStrings(): array
    {
        return array_map(static fn (Version $v): string => (string) $v, $this->versions);
    }
}
