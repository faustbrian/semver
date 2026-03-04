<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Cline\SemVer\Exceptions\InvalidBuildMetadataException;
use Stringable;

use function count;
use function explode;
use function implode;
use function preg_match;

/**
 * Represents build metadata per SemVer 2.0.0.
 *
 * Build metadata MAY be denoted by appending a plus sign and a series of
 * dot-separated identifiers immediately following the patch or pre-release version.
 * Identifiers MUST comprise only ASCII alphanumerics and hyphens [0-9A-Za-z-].
 * Identifiers MUST NOT be empty.
 *
 * Build metadata MUST be ignored when determining version precedence.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Build implements Stringable
{
    /**
     * Pattern for valid build metadata identifiers.
     * Must be alphanumeric with hyphens only.
     */
    private const string IDENTIFIER_PATTERN = '/^[0-9A-Za-z-]+$/';

    /**
     * @param list<string> $identifiers The dot-separated build metadata identifiers
     */
    private function __construct(
        private array $identifiers,
    ) {}

    /**
     * Convert to string representation.
     */
    public function __toString(): string
    {
        return implode('.', $this->identifiers);
    }

    /**
     * Create a Build from a string.
     *
     * @param string $build The build metadata string (without leading plus)
     *
     * @throws InvalidBuildMetadataException If the build metadata is invalid
     */
    public static function fromString(string $build): self
    {
        if ($build === '') {
            return new self([]);
        }

        $identifiers = explode('.', $build);

        foreach ($identifiers as $identifier) {
            self::validateIdentifier($identifier);
        }

        return new self($identifiers);
    }

    /**
     * Create a Build from an array of identifiers.
     *
     * @param list<string> $identifiers The build metadata identifiers
     *
     * @throws InvalidBuildMetadataException If any identifier is invalid
     */
    public static function fromArray(array $identifiers): self
    {
        foreach ($identifiers as $identifier) {
            self::validateIdentifier($identifier);
        }

        return new self($identifiers);
    }

    /**
     * Create an empty Build instance.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Check if this build metadata is empty.
     */
    public function isEmpty(): bool
    {
        return $this->identifiers === [];
    }

    /**
     * Get the identifiers.
     *
     * @return list<string>
     */
    public function identifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * Get the number of identifiers.
     */
    public function count(): int
    {
        return count($this->identifiers);
    }

    /**
     * Get an identifier by index.
     *
     * @param int $index The zero-based index
     */
    public function at(int $index): ?string
    {
        return $this->identifiers[$index] ?? null;
    }

    /**
     * Check if this build metadata equals another.
     *
     * Note: Build metadata is ignored for version precedence, but this
     * method compares the actual metadata strings for equality.
     *
     * @param self $other The other build metadata to compare
     */
    public function equals(self $other): bool
    {
        return $this->identifiers === $other->identifiers;
    }

    /**
     * Validate a single identifier.
     *
     * @throws InvalidBuildMetadataException If the identifier is invalid
     */
    private static function validateIdentifier(string $identifier): void
    {
        if ($identifier === '') {
            throw InvalidBuildMetadataException::emptyIdentifier();
        }

        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw InvalidBuildMetadataException::invalidCharacters($identifier);
        }
    }
}
