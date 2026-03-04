<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer\Casts;

use Cline\SemVer\Version;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

use function is_string;

/**
 * Eloquent cast for Version objects.
 *
 * Usage in models:
 *
 * protected function casts(): array
 * {
 *     return [
 *         'version' => VersionCast::class,
 *     ];
 * }
 *
 * @author Brian Faust <brian@cline.sh>
 * @implements CastsAttributes<Version, string|Version>
 */
final class VersionCast implements CastsAttributes
{
    /**
     * Cast the given value from the database.
     *
     * @param Model                $model      The model
     * @param string               $key        The attribute key
     * @param mixed                $value      The value from database
     * @param array<string, mixed> $attributes All attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Version
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return Version::parse($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model                $model      The model
     * @param string               $key        The attribute key
     * @param mixed                $value      The value to store
     * @param array<string, mixed> $attributes All attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Version) {
            return (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        // Validate the version string
        return (string) Version::parse($value);
    }
}
