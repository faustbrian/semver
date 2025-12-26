<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Cline\SemVer\Contracts\Comparable;
use Cline\SemVer\Exceptions\InvalidPreReleaseException;
use Override;
use Stringable;

use function count;
use function ctype_digit;
use function explode;
use function implode;
use function max;
use function preg_match;
use function str_starts_with;
use function strcmp;

/**
 * Represents a pre-release version identifier per SemVer 2.0.0.
 *
 * Pre-release versions are denoted by appending a hyphen and a series of
 * dot-separated identifiers immediately following the patch version.
 * Identifiers MUST comprise only ASCII alphanumerics and hyphens [0-9A-Za-z-].
 * Identifiers MUST NOT be empty. Numeric identifiers MUST NOT include leading zeroes.
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements Comparable<PreRelease>
 * @psalm-immutable
 */
final readonly class PreRelease implements Comparable, Stringable
{
    /**
     * Pattern for valid pre-release identifiers.
     * Must be alphanumeric with hyphens only.
     */
    private const string IDENTIFIER_PATTERN = '/^[0-9A-Za-z-]+$/';

    /**
     * @param list<string> $identifiers The dot-separated pre-release identifiers
     */
    private function __construct(
        private array $identifiers,
    ) {}

    /**
     * Convert to string representation.
     */
    #[Override()]
    public function __toString(): string
    {
        return implode('.', $this->identifiers);
    }

    /**
     * Create a PreRelease from a string.
     *
     * @param string $preRelease The pre-release string (without leading hyphen)
     *
     * @throws InvalidPreReleaseException If the pre-release string is invalid
     */
    public static function fromString(string $preRelease): self
    {
        if ($preRelease === '') {
            throw InvalidPreReleaseException::emptyIdentifier();
        }

        $identifiers = explode('.', $preRelease);

        foreach ($identifiers as $identifier) {
            self::validateIdentifier($identifier);
        }

        return new self($identifiers);
    }

    /**
     * Create a PreRelease from an array of identifiers.
     *
     * @param list<string> $identifiers The pre-release identifiers
     *
     * @throws InvalidPreReleaseException If any identifier is invalid
     */
    public static function fromArray(array $identifiers): self
    {
        if ($identifiers === []) {
            throw InvalidPreReleaseException::emptyIdentifier();
        }

        foreach ($identifiers as $identifier) {
            self::validateIdentifier($identifier);
        }

        return new self($identifiers);
    }

    /**
     * Create an empty PreRelease instance.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Check if this pre-release is empty.
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
     * Compare this pre-release to another per SemVer 2.0.0 precedence rules.
     *
     * Precedence for two pre-release versions with the same major, minor, and patch
     * version MUST be determined by comparing each dot separated identifier from left
     * to right until a difference is found:
     *
     * 1. Identifiers consisting of only digits are compared numerically.
     * 2. Identifiers with letters or hyphens are compared lexically in ASCII sort order.
     * 3. Numeric identifiers always have lower precedence than non-numeric identifiers.
     * 4. A larger set of pre-release fields has a higher precedence than a smaller set,
     *    if all the preceding identifiers are equal.
     *
     * @param self $other The other pre-release to compare
     *
     * @return int Returns < 0 if this < other, 0 if equal, > 0 if this > other
     */
    #[Override()]
    public function compareTo(Comparable $other): int
    {
        // Empty pre-release has higher precedence than non-empty (stable > pre-release)
        if ($this->isEmpty() && $other->isEmpty()) {
            return 0;
        }

        if ($this->isEmpty()) {
            return 1; // Stable version > pre-release
        }

        if ($other->isEmpty()) {
            return -1; // Pre-release < stable version
        }

        $thisIds = $this->identifiers;
        $otherIds = $other->identifiers;
        $maxLen = max(count($thisIds), count($otherIds));

        for ($i = 0; $i < $maxLen; ++$i) {
            // If one runs out of identifiers first, the one with more has higher precedence
            if (!isset($thisIds[$i])) {
                return -1;
            }

            if (!isset($otherIds[$i])) {
                return 1;
            }

            $comparison = $this->compareIdentifiers($thisIds[$i], $otherIds[$i]);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
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
     * Increment the last numeric identifier or append ".1".
     */
    public function increment(): self
    {
        if ($this->isEmpty()) {
            return self::fromArray(['0']);
        }

        $identifiers = $this->identifiers;
        $lastIndex = count($identifiers) - 1;
        $last = $identifiers[$lastIndex];

        if (ctype_digit($last)) {
            $identifiers[$lastIndex] = (string) ((int) $last + 1);
        } else {
            $identifiers[] = '1';
        }

        /** @var list<string> $identifiers */
        return new self($identifiers);
    }

    /**
     * Validate a single identifier.
     *
     * @throws InvalidPreReleaseException If the identifier is invalid
     */
    private static function validateIdentifier(string $identifier): void
    {
        if ($identifier === '') {
            throw InvalidPreReleaseException::emptyIdentifier();
        }

        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw InvalidPreReleaseException::invalidCharacters($identifier);
        }

        // Numeric identifiers must not have leading zeros
        if (ctype_digit($identifier) && $identifier !== '0' && str_starts_with($identifier, '0')) {
            throw InvalidPreReleaseException::numericLeadingZeros($identifier);
        }
    }

    /**
     * Compare two identifiers per SemVer 2.0.0 rules.
     *
     * @param string $a First identifier
     * @param string $b Second identifier
     *
     * @return int Comparison result
     */
    private function compareIdentifiers(string $a, string $b): int
    {
        $aIsNumeric = ctype_digit($a);
        $bIsNumeric = ctype_digit($b);

        // Numeric identifiers always have lower precedence than non-numeric
        if ($aIsNumeric && !$bIsNumeric) {
            return -1;
        }

        if (!$aIsNumeric && $bIsNumeric) {
            return 1;
        }

        // Both numeric: compare numerically
        if ($aIsNumeric) {
            return (int) $a <=> (int) $b;
        }

        // Both non-numeric: compare lexically (ASCII sort order)
        return strcmp($a, $b);
    }
}
