<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Facades\SemVer;
use Cline\SemVer\Version;

describe('SemVer Facade', function (): void {
    test('parse returns Version', function (): void {
        $version = SemVer::parse('1.2.3');

        expect($version)->toBeInstanceOf(Version::class);
        expect((string) $version)->toBe('1.2.3');
    });

    test('tryParse returns null on invalid', function (): void {
        expect(SemVer::tryParse('invalid'))->toBeNull();
    });

    test('valid checks version validity', function (): void {
        expect(SemVer::valid('1.2.3'))->toBeTrue();
        expect(SemVer::valid('invalid'))->toBeFalse();
    });

    test('create builds Version from components', function (): void {
        $version = SemVer::create(1, 2, 3, 'alpha', 'build');

        expect((string) $version)->toBe('1.2.3-alpha+build');
    });

    test('coerce extracts version from string', function (): void {
        expect((string) SemVer::coerce('v1.2.3'))->toBe('1.2.3');
    });

    test('satisfies checks constraint', function (): void {
        expect(SemVer::satisfies('1.5.0', '^1.0.0'))->toBeTrue();
    });

    test('compare compares versions', function (): void {
        expect(SemVer::compare('1.0.0', '2.0.0'))->toBeLessThan(0);
    });

    test('eq checks equality', function (): void {
        expect(SemVer::eq('1.0.0', '1.0.0'))->toBeTrue();
    });

    test('gt checks greater than', function (): void {
        expect(SemVer::gt('2.0.0', '1.0.0'))->toBeTrue();
    });

    test('lt checks less than', function (): void {
        expect(SemVer::lt('1.0.0', '2.0.0'))->toBeTrue();
    });

    test('sort returns sorted versions', function (): void {
        $sorted = SemVer::sort(['2.0.0', '1.0.0', '3.0.0']);

        expect(array_map(fn (Version $v): string => (string) $v, $sorted))->toBe(['1.0.0', '2.0.0', '3.0.0']);
    });

    test('max returns highest version', function (): void {
        expect((string) SemVer::max(['2.0.0', '1.0.0', '3.0.0']))->toBe('3.0.0');
    });

    test('min returns lowest version', function (): void {
        expect((string) SemVer::min(['2.0.0', '1.0.0', '3.0.0']))->toBe('1.0.0');
    });

    test('incMajor increments major version', function (): void {
        expect((string) SemVer::incMajor('1.2.3'))->toBe('2.0.0');
    });

    test('incMinor increments minor version', function (): void {
        expect((string) SemVer::incMinor('1.2.3'))->toBe('1.3.0');
    });

    test('incPatch increments patch version', function (): void {
        expect((string) SemVer::incPatch('1.2.3'))->toBe('1.2.4');
    });

    test('diff returns difference type', function (): void {
        expect(SemVer::diff('1.0.0', '2.0.0'))->toBe('major');
    });

    test('collection creates VersionCollection', function (): void {
        $collection = SemVer::collection(['1.0.0', '2.0.0']);

        expect($collection->count())->toBe(2);
    });
});
