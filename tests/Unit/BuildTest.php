<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Build;
use Cline\SemVer\Exceptions\InvalidBuildMetadataException;

describe('Build Parsing', function (): void {
    test('parses simple identifier', function (): void {
        $build = Build::fromString('build');

        expect($build->identifiers())->toBe(['build']);
        expect((string) $build)->toBe('build');
    });

    test('parses multiple identifiers', function (): void {
        $build = Build::fromString('build.123.sha.abc');

        expect($build->identifiers())->toBe(['build', '123', 'sha', 'abc']);
        expect((string) $build)->toBe('build.123.sha.abc');
    });

    test('parses numeric identifier', function (): void {
        $build = Build::fromString('001');

        expect($build->identifiers())->toBe(['001']);
    });

    test('allows leading zeros in numeric', function (): void {
        // Unlike pre-release, build metadata allows leading zeros
        $build = Build::fromString('001.002');

        expect($build->identifiers())->toBe(['001', '002']);
    });

    test('parses identifier with hyphen', function (): void {
        $build = Build::fromString('build-info');

        expect($build->identifiers())->toBe(['build-info']);
    });

    test('parses empty string as empty', function (): void {
        $build = Build::fromString('');

        expect($build->isEmpty())->toBeTrue();
    });

    test('throws on empty identifier', function (): void {
        Build::fromString('build..info');
    })->throws(InvalidBuildMetadataException::class);

    test('throws on invalid characters', function (): void {
        Build::fromString('build_info');
    })->throws(InvalidBuildMetadataException::class);

    test('throws on invalid characters in array', function (): void {
        Build::fromArray(['valid', 'invalid_char']);
    })->throws(InvalidBuildMetadataException::class);
});

describe('Build Creation', function (): void {
    test('creates from array', function (): void {
        $build = Build::fromArray(['build', '123']);

        expect($build->identifiers())->toBe(['build', '123']);
    });

    test('creates empty', function (): void {
        $build = Build::empty();

        expect($build->isEmpty())->toBeTrue();
        expect($build->identifiers())->toBe([]);
    });

    test('creates empty from empty array', function (): void {
        $build = Build::fromArray([]);

        expect($build->isEmpty())->toBeTrue();
    });
});

describe('Build Properties', function (): void {
    test('count returns number of identifiers', function (): void {
        $build = Build::fromString('build.123.sha');

        expect($build->count())->toBe(3);
    });

    test('at returns identifier by index', function (): void {
        $build = Build::fromString('build.123');

        expect($build->at(0))->toBe('build');
        expect($build->at(1))->toBe('123');
        expect($build->at(2))->toBeNull();
    });

    test('isEmpty returns true for empty', function (): void {
        expect(Build::empty()->isEmpty())->toBeTrue();
    });

    test('isEmpty returns false for non-empty', function (): void {
        expect(Build::fromString('build')->isEmpty())->toBeFalse();
    });
});

describe('Build Equality', function (): void {
    test('equals compares correctly', function (): void {
        $v1 = Build::fromString('build.123');
        $v2 = Build::fromString('build.123');
        $v3 = Build::fromString('build.456');

        expect($v1->equals($v2))->toBeTrue();
        expect($v1->equals($v3))->toBeFalse();
    });

    test('empty builds are equal', function (): void {
        $v1 = Build::empty();
        $v2 = Build::empty();

        expect($v1->equals($v2))->toBeTrue();
    });
});
