<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer\Facades;

use Cline\SemVer\Constraint;
use Cline\SemVer\SemVerManager;
use Cline\SemVer\Version;
use Cline\SemVer\VersionCollection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the SemVer manager.
 *
 * @method static bool              cmp(Version|string $a, string $op, Version|string $b)
 * @method static Version|null      coerce(string $version)
 * @method static VersionCollection collection(array<string|Version> $versions = [])
 * @method static int               compare(Version|string $a, Version|string $b)
 * @method static Version           create(int $major, int $minor = 0, int $patch = 0, list<string>|string|null $preRelease = null, list<string>|string|null $build = null)
 * @method static string|null       diff(Version|string $a, Version|string $b)
 * @method static bool              eq(Version|string $a, Version|string $b)
 * @method static bool              gt(Version|string $a, Version|string $b)
 * @method static bool              gte(Version|string $a, Version|string $b)
 * @method static Version           incMajor(Version|string $version)
 * @method static Version           incMinor(Version|string $version)
 * @method static Version           incPatch(Version|string $version)
 * @method static Version           incPreRelease(Version|string $version)
 * @method static bool              lt(Version|string $a, Version|string $b)
 * @method static bool              lte(Version|string $a, Version|string $b)
 * @method static Version|null      max(array<string|Version> $versions)
 * @method static Version|null      maxSatisfying(array<string|Version> $versions, string $constraint)
 * @method static Version|null      min(array<string|Version> $versions)
 * @method static Version|null      minSatisfying(array<string|Version> $versions, string $constraint)
 * @method static bool              neq(Version|string $a, Version|string $b)
 * @method static Version           parse(string $version)
 * @method static Constraint        parseConstraint(string $constraint)
 * @method static list<Version>     rsort(array<string|Version> $versions)
 * @method static bool              satisfies(Version|string $version, string $constraint)
 * @method static list<Version>     sort(array<string|Version> $versions)
 * @method static Version|null      tryParse(string $version)
 * @method static bool              valid(string $version)
 *
 * @author Brian Faust <brian@cline.sh>
 * @see SemVerManager
 */
final class SemVer extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SemVerManager::class;
    }
}
