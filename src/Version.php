<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Cline\SemVer\Contracts\Comparable;
use Cline\SemVer\Exceptions\InvalidVersionException;
use JsonSerializable;
use Override;
use Stringable;

use function is_string;
use function preg_match;
use function sprintf;

/**
 * Represents a semantic version per SemVer 2.0.0.
 *
 * Given a version number MAJOR.MINOR.PATCH, increment the:
 * - MAJOR version when you make incompatible API changes
 * - MINOR version when you add functionality in a backward compatible manner
 * - PATCH version when you make backward compatible bug fixes
 *
 * Additional labels for pre-release and build metadata are available as
 * extensions to the MAJOR.MINOR.PATCH format.
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements Comparable<Version>
 * @psalm-immutable
 */
final readonly class Version implements Comparable, JsonSerializable, Stringable
{
    /**
     * Regex pattern for parsing semantic versions per SemVer 2.0.0 BNF grammar.
     *
     * Captures:
     * 1. Major version (numeric, no leading zeros unless 0)
     * 2. Minor version (numeric, no leading zeros unless 0)
     * 3. Patch version (numeric, no leading zeros unless 0)
     * 4. Pre-release identifiers (optional, dot-separated alphanumerics/hyphens)
     * 5. Build metadata (optional, dot-separated alphanumerics/hyphens)
     */
    private const string SEMVER_REGEX = '/^v?(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<build>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * @param int        $major      The major version number
     * @param int        $minor      The minor version number
     * @param int        $patch      The patch version number
     * @param PreRelease $preRelease The pre-release identifiers
     * @param Build      $build      The build metadata
     */
    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public PreRelease $preRelease,
        public Build $build,
    ) {
        if ($major < 0) {
            throw InvalidVersionException::negativeNumber('major', $major);
        }

        if ($minor < 0) {
            throw InvalidVersionException::negativeNumber('minor', $minor);
        }

        if ($patch < 0) {
            throw InvalidVersionException::negativeNumber('patch', $patch);
        }
    }

    /**
     * Convert to string representation.
     */
    #[Override()]
    public function __toString(): string
    {
        $version = $this->core();

        if (!$this->preRelease->isEmpty()) {
            $version .= '-'.$this->preRelease;
        }

        if (!$this->build->isEmpty()) {
            $version .= '+'.$this->build;
        }

        return $version;
    }

    /**
     * Parse a version string into a Version object.
     *
     * @param string $version The version string to parse
     *
     * @throws InvalidVersionException If the version string is invalid
     */
    public static function parse(string $version): self
    {
        if (preg_match(self::SEMVER_REGEX, $version, $matches) !== 1) {
            throw InvalidVersionException::invalidFormat($version);
        }

        $preRelease = ($matches['prerelease'] ?? '') !== ''
            ? PreRelease::fromString($matches['prerelease'])
            : PreRelease::empty();

        $build = ($matches['build'] ?? '') !== ''
            ? Build::fromString($matches['build'])
            : Build::empty();

        return new self(
            major: (int) $matches['major'],
            minor: (int) $matches['minor'],
            patch: (int) $matches['patch'],
            preRelease: $preRelease,
            build: $build,
        );
    }

    /**
     * Try to parse a version string, returning null on failure.
     *
     * @param string $version The version string to parse
     */
    public static function tryParse(string $version): ?self
    {
        try {
            return self::parse($version);
        } catch (InvalidVersionException) {
            return null;
        }
    }

    /**
     * Check if a string is a valid semantic version.
     *
     * @param string $version The version string to validate
     */
    public static function isValid(string $version): bool
    {
        return preg_match(self::SEMVER_REGEX, $version) === 1;
    }

    /**
     * Create a version from individual components.
     *
     * @param int                      $major      The major version
     * @param int                      $minor      The minor version
     * @param int                      $patch      The patch version
     * @param null|list<string>|string $preRelease Optional pre-release identifiers
     * @param null|list<string>|string $build      Optional build metadata
     */
    public static function create(
        int $major,
        int $minor = 0,
        int $patch = 0,
        array|string|null $preRelease = null,
        array|string|null $build = null,
    ): self {
        $preReleaseObj = match (true) {
            $preRelease === null => PreRelease::empty(),
            is_string($preRelease) => PreRelease::fromString($preRelease),
            default => PreRelease::fromArray($preRelease),
        };

        $buildObj = match (true) {
            $build === null => Build::empty(),
            is_string($build) => Build::fromString($build),
            default => Build::fromArray($build),
        };

        return new self($major, $minor, $patch, $preReleaseObj, $buildObj);
    }

    /**
     * Check if this is a stable (non-pre-release) version.
     */
    public function isStable(): bool
    {
        return $this->preRelease->isEmpty() && $this->major > 0;
    }

    /**
     * Check if this is a pre-release version.
     */
    public function isPreRelease(): bool
    {
        return !$this->preRelease->isEmpty();
    }

    /**
     * Check if this is a development version (0.x.x).
     */
    public function isDevelopment(): bool
    {
        return $this->major === 0;
    }

    /**
     * Check if this version has build metadata.
     */
    public function hasBuild(): bool
    {
        return !$this->build->isEmpty();
    }

    /**
     * Get the core version string (MAJOR.MINOR.PATCH).
     */
    public function core(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }

    /**
     * Compare this version to another per SemVer 2.0.0 precedence rules.
     *
     * Precedence is determined by the first difference when comparing
     * major, minor, patch, and pre-release identifiers in that order.
     * Build metadata does not figure into precedence.
     *
     * @param self $other The other version to compare
     *
     * @return int Returns < 0 if this < other, 0 if equal, > 0 if this > other
     */
    #[Override()]
    public function compareTo(Comparable $other): int
    {
        // Compare major
        if ($this->major !== $other->major) {
            return $this->major <=> $other->major;
        }

        // Compare minor
        if ($this->minor !== $other->minor) {
            return $this->minor <=> $other->minor;
        }

        // Compare patch
        if ($this->patch !== $other->patch) {
            return $this->patch <=> $other->patch;
        }

        // Compare pre-release
        // A version without pre-release has higher precedence than one with pre-release
        return $this->preRelease->compareTo($other->preRelease);
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function equals(Comparable $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function lessThanOrEquals(Comparable $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function greaterThanOrEquals(Comparable $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Check exact equality including build metadata.
     *
     * Unlike equals() which follows SemVer precedence rules (ignoring build),
     * this method checks for exact string equality including build metadata.
     *
     * @param self $other The other version to compare
     */
    public function identical(self $other): bool
    {
        return (string) $this === (string) $other;
    }

    /**
     * Increment the major version.
     *
     * Resets minor, patch, pre-release, and build to their initial values.
     */
    public function incrementMajor(): self
    {
        return new self(
            $this->major + 1,
            0,
            0,
            PreRelease::empty(),
            Build::empty(),
        );
    }

    /**
     * Increment the minor version.
     *
     * Resets patch, pre-release, and build to their initial values.
     */
    public function incrementMinor(): self
    {
        return new self(
            $this->major,
            $this->minor + 1,
            0,
            PreRelease::empty(),
            Build::empty(),
        );
    }

    /**
     * Increment the patch version.
     *
     * Resets pre-release and build to their initial values.
     */
    public function incrementPatch(): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch + 1,
            PreRelease::empty(),
            Build::empty(),
        );
    }

    /**
     * Increment the pre-release version.
     *
     * If no pre-release exists, creates one with "0".
     * Otherwise, increments the last numeric identifier.
     */
    public function incrementPreRelease(): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $this->preRelease->increment(),
            Build::empty(),
        );
    }

    /**
     * Create a new version with the given pre-release.
     *
     * @param list<string>|string $preRelease The pre-release identifiers
     */
    public function withPreRelease(array|string $preRelease): self
    {
        $preReleaseObj = is_string($preRelease)
            ? PreRelease::fromString($preRelease)
            : PreRelease::fromArray($preRelease);

        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $preReleaseObj,
            $this->build,
        );
    }

    /**
     * Create a new version without pre-release.
     */
    public function withoutPreRelease(): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            PreRelease::empty(),
            $this->build,
        );
    }

    /**
     * Create a new version with the given build metadata.
     *
     * @param list<string>|string $build The build metadata
     */
    public function withBuild(array|string $build): self
    {
        $buildObj = is_string($build)
            ? Build::fromString($build)
            : Build::fromArray($build);

        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $this->preRelease,
            $buildObj,
        );
    }

    /**
     * Create a new version without build metadata.
     */
    public function withoutBuild(): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $this->preRelease,
            Build::empty(),
        );
    }

    /**
     * Get the difference type between this version and another.
     *
     * @param self $other The other version to compare
     *
     * @return null|string The difference type: 'major', 'minor', 'patch', 'prerelease', 'build', or null if identical
     */
    public function diff(self $other): ?string
    {
        if ($this->major !== $other->major) {
            return 'major';
        }

        if ($this->minor !== $other->minor) {
            return 'minor';
        }

        if ($this->patch !== $other->patch) {
            return 'patch';
        }

        if (!$this->preRelease->equals($other->preRelease)) {
            return 'prerelease';
        }

        if (!$this->build->equals($other->build)) {
            return 'build';
        }

        return null;
    }

    /**
     * Check if this version satisfies a constraint.
     *
     * @param Constraint $constraint The constraint to check
     */
    public function satisfies(Constraint $constraint): bool
    {
        return $constraint->isSatisfiedBy($this);
    }

    /**
     * Serialize to JSON.
     *
     * @return array{major: int, minor: int, patch: int, prerelease: string, build: string, full: string}
     */
    #[Override()]
    public function jsonSerialize(): array
    {
        return [
            'major' => $this->major,
            'minor' => $this->minor,
            'patch' => $this->patch,
            'prerelease' => (string) $this->preRelease,
            'build' => (string) $this->build,
            'full' => (string) $this,
        ];
    }
}
