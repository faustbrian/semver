<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer;

use Cline\SemVer\Enums\Operator;
use Cline\SemVer\Exceptions\InvalidConstraintException;
use Stringable;

use const PREG_SPLIT_NO_EMPTY;

use function array_all;
use function array_any;
use function array_map;
use function array_merge;
use function explode;
use function in_array;
use function is_numeric;
use function mb_trim;
use function preg_match;
use function preg_split;
use function str_contains;

/**
 * Represents a version constraint for matching versions.
 *
 * Supports:
 * - Exact versions: 1.0.0, =1.0.0
 * - Comparison operators: <1.0.0, <=1.0.0, >1.0.0, >=1.0.0, !=1.0.0
 * - Tilde ranges: ~1.2.3 (>=1.2.3 <1.3.0)
 * - Caret ranges: ^1.2.3 (>=1.2.3 <2.0.0)
 * - OR constraints: 1.0.0 || 2.0.0
 * - AND constraints: >=1.0.0 <2.0.0
 * - Hyphen ranges: 1.2.3 - 2.3.4 (>=1.2.3 <=2.3.4)
 * - Wildcards: 1.x, 1.2.x, 1.*, 1.2.*
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Constraint implements Stringable
{
    /**
     * Pattern for parsing a single constraint.
     */
    private const string CONSTRAINT_PATTERN = '/^(?P<operator>>=?|<=?|!=|=|\^|~)?(?P<version>.+)$/';

    /**
     * Pattern for detecting hyphen ranges.
     */
    private const string HYPHEN_RANGE_PATTERN = '/^(?P<from>\d+(?:\.\d+)?(?:\.\d+)?(?:-[0-9A-Za-z.-]+)?)\s+-\s+(?P<to>\d+(?:\.\d+)?(?:\.\d+)?(?:-[0-9A-Za-z.-]+)?)$/';

    /**
     * Pattern for detecting wildcards.
     */
    private const string WILDCARD_PATTERN = '/^(?P<major>\d+)(?:\.(?P<minor>\d+|[xX*]))?(?:\.(?P<patch>\d+|[xX*]))?$/';

    /**
     * @param list<list<array{operator: Operator, version: Version, upperBound: ?Version}>> $constraints The OR groups of AND constraints
     * @param string                                                                        $original    The original constraint string
     */
    private function __construct(
        private array $constraints,
        private string $original,
    ) {}

    /**
     * Get the original constraint string.
     */
    public function __toString(): string
    {
        return $this->original;
    }

    /**
     * Parse a constraint string.
     *
     * @param string $constraint The constraint string
     *
     * @throws InvalidConstraintException If the constraint is invalid
     */
    public static function parse(string $constraint): self
    {
        $constraint = mb_trim($constraint);

        if ($constraint === '' || $constraint === '*') {
            return new self([[['operator' => Operator::GreaterThanOrEqual, 'version' => Version::create(0, 0, 0), 'upperBound' => null]]], $constraint);
        }

        // Split by || for OR groups
        $orGroups = array_map(trim(...), explode('||', $constraint));
        $parsedGroups = [];

        foreach ($orGroups as $orGroup) {
            $parsedGroups[] = self::parseAndGroup($orGroup);
        }

        return new self($parsedGroups, $constraint);
    }

    /**
     * Create a constraint for an exact version.
     *
     * @param string|Version $version The version
     */
    public static function exact(Version|string $version): self
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return new self([[['operator' => Operator::Equal, 'version' => $v, 'upperBound' => null]]], (string) $v);
    }

    /**
     * Create a constraint with an operator.
     *
     * @param Operator       $operator The comparison operator
     * @param string|Version $version  The version
     */
    public static function withOperator(Operator $operator, Version|string $version): self
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return new self([[['operator' => $operator, 'version' => $v, 'upperBound' => null]]], $operator->value.$v);
    }

    /**
     * Check if a version satisfies this constraint.
     *
     * @param string|Version $version The version to check
     */
    public function isSatisfiedBy(Version|string $version): bool
    {
        $v = $version instanceof Version ? $version : Version::parse($version);

        return array_any($this->constraints, fn (array $andGroup): bool => $this->satisfiesAndGroup($v, $andGroup));
    }

    /**
     * Combine this constraint with another using AND.
     *
     * @param self $other The other constraint
     */
    public function and(self $other): self
    {
        // Combine each OR group from this with each from other
        $newGroups = [];

        foreach ($this->constraints as $thisGroup) {
            foreach ($other->constraints as $otherGroup) {
                $newGroups[] = array_merge($thisGroup, $otherGroup);
            }
        }

        return new self($newGroups, $this->original.' '.$other->original);
    }

    /**
     * Combine this constraint with another using OR.
     *
     * @param self $other The other constraint
     */
    public function or(self $other): self
    {
        return new self(
            array_merge($this->constraints, $other->constraints),
            $this->original.' || '.$other->original,
        );
    }

    /**
     * Parse an AND group (constraints separated by space or comma).
     *
     * @param string $group The AND group string
     *
     * @throws InvalidConstraintException                                              If parsing fails
     * @return list<array{operator: Operator, version: Version, upperBound: ?Version}>
     */
    private static function parseAndGroup(string $group): array
    {
        // Check for hyphen range first
        if (preg_match(self::HYPHEN_RANGE_PATTERN, $group, $matches)) {
            return self::parseHyphenRange($matches['from'], $matches['to']);
        }

        // Split by space or comma for AND constraints
        $parts = preg_split('/\s+|,\s*/', $group, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            throw InvalidConstraintException::invalidFormat($group);
        }

        $constraints = [];

        foreach ($parts as $part) {
            $constraints = array_merge($constraints, self::parseSingleConstraint($part));
        }

        return $constraints;
    }

    /**
     * Parse a hyphen range (e.g., "1.2.3 - 2.3.4").
     *
     * @param string $from The from version
     * @param string $to   The to version
     *
     * @return list<array{operator: Operator, version: Version, upperBound: ?Version}>
     */
    private static function parseHyphenRange(string $from, string $to): array
    {
        $fromVersion = self::parsePartialVersion($from);
        $toVersion = self::parsePartialVersion($to);

        // If 'to' is partial (e.g., 2.3), it means <2.4.0 not <=2.3.x
        $toHasAllParts = preg_match('/^\d+\.\d+\.\d+/', $to) === 1;

        return [
            ['operator' => Operator::GreaterThanOrEqual, 'version' => $fromVersion, 'upperBound' => null],
            [
                'operator' => $toHasAllParts ? Operator::LessThanOrEqual : Operator::LessThan,
                'version' => $toHasAllParts ? $toVersion : $toVersion->incrementMinor(),
                'upperBound' => null,
            ],
        ];
    }

    /**
     * Parse a single constraint (e.g., ">=1.0.0" or "~1.2.3").
     *
     * @param string $constraint The constraint string
     *
     * @throws InvalidConstraintException                                              If parsing fails
     * @return list<array{operator: Operator, version: Version, upperBound: ?Version}>
     */
    private static function parseSingleConstraint(string $constraint): array
    {
        // Check for wildcard patterns
        if (preg_match(self::WILDCARD_PATTERN, $constraint, $matches)) {
            if (($matches['minor'] ?? null) === null || in_array($matches['minor'], ['x', 'X', '*'], true)) {
                // 1.x or 1.* => >=1.0.0 <2.0.0
                $major = (int) $matches['major'];

                return [
                    [
                        'operator' => Operator::GreaterThanOrEqual,
                        'version' => Version::create($major, 0, 0),
                        'upperBound' => Version::create($major + 1, 0, 0),
                    ],
                ];
            }

            if (!isset($matches['patch']) || in_array($matches['patch'], ['x', 'X', '*'], true)) {
                // 1.2.x or 1.2.* => >=1.2.0 <1.3.0
                $major = (int) $matches['major'];
                $minor = (int) $matches['minor'];

                return [
                    [
                        'operator' => Operator::GreaterThanOrEqual,
                        'version' => Version::create($major, $minor, 0),
                        'upperBound' => Version::create($major, $minor + 1, 0),
                    ],
                ];
            }
        }

        if (preg_match(self::CONSTRAINT_PATTERN, $constraint, $matches) !== 1) {
            throw InvalidConstraintException::invalidFormat($constraint);
        }

        $operatorStr = $matches['operator'];
        $versionStr = $matches['version'];

        $operator = Operator::fromString($operatorStr);

        if (!$operator instanceof Operator) {
            throw InvalidConstraintException::unknownOperator($operatorStr);
        }

        $version = self::parsePartialVersion($versionStr);

        // Handle tilde and caret
        if ($operator === Operator::Tilde) {
            // ~1.2.3 => >=1.2.3 <1.3.0
            return [
                [
                    'operator' => Operator::GreaterThanOrEqual,
                    'version' => $version,
                    'upperBound' => Version::create($version->major, $version->minor + 1, 0),
                ],
            ];
        }

        if ($operator === Operator::Caret) {
            // ^1.2.3 => >=1.2.3 <2.0.0 (if major > 0)
            // ^0.2.3 => >=0.2.3 <0.3.0 (if major = 0)
            // ^0.0.3 => >=0.0.3 <0.0.4 (if major = 0 and minor = 0)
            $upperBound = match (true) {
                $version->major > 0 => Version::create($version->major + 1, 0, 0),
                $version->minor > 0 => Version::create(0, $version->minor + 1, 0),
                default => Version::create(0, 0, $version->patch + 1),
            };

            return [
                [
                    'operator' => Operator::GreaterThanOrEqual,
                    'version' => $version,
                    'upperBound' => $upperBound,
                ],
            ];
        }

        return [
            ['operator' => $operator, 'version' => $version, 'upperBound' => null],
        ];
    }

    /**
     * Parse a partial version string (e.g., "1" or "1.2" or "1.2.3").
     *
     * @param string $version The version string
     *
     * @throws InvalidConstraintException If parsing fails
     */
    private static function parsePartialVersion(string $version): Version
    {
        // Handle partial versions
        $parts = explode('.', $version);

        $major = is_numeric($parts[0]) ? (int) $parts[0] : 0;
        $minor = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 0;

        // Check if we have pre-release in patch part
        $patchPart = $parts[2] ?? '0';
        $patch = 0;
        $preRelease = null;

        if (str_contains($patchPart, '-')) {
            $patchParts = explode('-', $patchPart, 2);
            $patch = (int) $patchParts[0];
            $preRelease = $patchParts[1];
        } else {
            $patch = is_numeric($patchPart) ? (int) $patchPart : 0;
        }

        return Version::create($major, $minor, $patch, $preRelease);
    }

    /**
     * Check if a version satisfies all constraints in an AND group.
     *
     * @param Version                                                                 $version  The version to check
     * @param list<array{operator: Operator, version: Version, upperBound: ?Version}> $andGroup The AND constraints
     */
    private function satisfiesAndGroup(Version $version, array $andGroup): bool
    {
        return array_all($andGroup, fn (array $constraint): bool => $this->satisfiesConstraint($version, $constraint));
    }

    /**
     * Check if a version satisfies a single constraint.
     *
     * @param Version                                                           $version    The version to check
     * @param array{operator: Operator, version: Version, upperBound: ?Version} $constraint The constraint
     */
    private function satisfiesConstraint(Version $version, array $constraint): bool
    {
        $op = $constraint['operator'];
        $target = $constraint['version'];
        $upperBound = $constraint['upperBound'];

        // For tilde/caret with upper bounds
        if ($upperBound !== null) {
            return $version->greaterThanOrEquals($target) && $version->lessThan($upperBound);
        }

        return match ($op) {
            Operator::Equal => $version->equals($target),
            Operator::NotEqual => !$version->equals($target),
            Operator::LessThan => $version->lessThan($target),
            Operator::LessThanOrEqual => $version->lessThanOrEquals($target),
            Operator::GreaterThan => $version->greaterThan($target),
            Operator::GreaterThanOrEqual => $version->greaterThanOrEquals($target),
            Operator::Tilde, Operator::Caret => true, // Should have upperBound set
        };
    }
}
