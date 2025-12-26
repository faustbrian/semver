<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Build;
use Cline\SemVer\Exceptions\InvalidVersionException;
use Cline\SemVer\PreRelease;
use Cline\SemVer\Version;

describe('Version Parsing', function (): void {
    test('parses basic version', function (): void {
        $version = Version::parse('1.2.3');

        expect($version->major)->toBe(1);
        expect($version->minor)->toBe(2);
        expect($version->patch)->toBe(3);
        expect($version->preRelease->isEmpty())->toBeTrue();
        expect($version->build->isEmpty())->toBeTrue();
        expect((string) $version)->toBe('1.2.3');
    });

    test('parses version with v prefix', function (): void {
        $version = Version::parse('v1.2.3');

        expect($version->major)->toBe(1);
        expect($version->minor)->toBe(2);
        expect($version->patch)->toBe(3);
    });

    test('parses version with pre-release', function (): void {
        $version = Version::parse('1.2.3-alpha');

        expect($version->major)->toBe(1);
        expect($version->preRelease->isEmpty())->toBeFalse();
        expect((string) $version->preRelease)->toBe('alpha');
        expect((string) $version)->toBe('1.2.3-alpha');
    });

    test('parses version with multi-part pre-release', function (): void {
        $version = Version::parse('1.0.0-alpha.1');

        expect((string) $version->preRelease)->toBe('alpha.1');
        expect($version->preRelease->identifiers())->toBe(['alpha', '1']);
    });

    test('parses version with build metadata', function (): void {
        $version = Version::parse('1.2.3+build.123');

        expect($version->build->isEmpty())->toBeFalse();
        expect((string) $version->build)->toBe('build.123');
        expect((string) $version)->toBe('1.2.3+build.123');
    });

    test('parses version with pre-release and build', function (): void {
        $version = Version::parse('1.0.0-alpha+001');

        expect((string) $version->preRelease)->toBe('alpha');
        expect((string) $version->build)->toBe('001');
        expect((string) $version)->toBe('1.0.0-alpha+001');
    });

    test('parses complex version', function (): void {
        $version = Version::parse('1.0.0-beta.2.rc.1+build.sha.5114f85');

        expect($version->major)->toBe(1);
        expect($version->minor)->toBe(0);
        expect($version->patch)->toBe(0);
        expect($version->preRelease->identifiers())->toBe(['beta', '2', 'rc', '1']);
        expect($version->build->identifiers())->toBe(['build', 'sha', '5114f85']);
    });

    test('throws on invalid version format', function (): void {
        Version::parse('invalid');
    })->throws(InvalidVersionException::class);

    test('throws on version with leading zeros', function (): void {
        Version::parse('01.2.3');
    })->throws(InvalidVersionException::class);

    test('throws on negative version number', function (): void {
        new Version(-1, 0, 0, PreRelease::empty(), Build::empty());
    })->throws(InvalidVersionException::class);

    test('tryParse returns null on invalid version', function (): void {
        expect(Version::tryParse('invalid'))->toBeNull();
    });

    test('tryParse returns version on valid input', function (): void {
        $version = Version::tryParse('1.2.3');
        expect($version)->not->toBeNull();
        expect((string) $version)->toBe('1.2.3');
    });

    test('isValid returns true for valid versions', function (): void {
        expect(Version::isValid('1.2.3'))->toBeTrue();
        expect(Version::isValid('0.0.0'))->toBeTrue();
        expect(Version::isValid('1.0.0-alpha.1+build'))->toBeTrue();
    });

    test('isValid returns false for invalid versions', function (): void {
        expect(Version::isValid('invalid'))->toBeFalse();
        expect(Version::isValid('1.2'))->toBeFalse();
        expect(Version::isValid('1.2.3.4'))->toBeFalse();
    });
});

describe('Version Creation', function (): void {
    test('creates version from components', function (): void {
        $version = Version::create(1, 2, 3);

        expect($version->major)->toBe(1);
        expect($version->minor)->toBe(2);
        expect($version->patch)->toBe(3);
    });

    test('creates version with string pre-release', function (): void {
        $version = Version::create(1, 0, 0, 'alpha.1');

        expect((string) $version->preRelease)->toBe('alpha.1');
    });

    test('creates version with array pre-release', function (): void {
        $version = Version::create(1, 0, 0, ['alpha', '1']);

        expect($version->preRelease->identifiers())->toBe(['alpha', '1']);
    });

    test('creates version with string build', function (): void {
        $version = Version::create(1, 0, 0, null, 'build.123');

        expect((string) $version->build)->toBe('build.123');
    });

    test('creates version with array build', function (): void {
        $version = Version::create(1, 0, 0, null, ['build', '123']);

        expect($version->build->identifiers())->toBe(['build', '123']);
    });
});

describe('Version Properties', function (): void {
    test('isStable returns true for stable versions', function (): void {
        expect(Version::parse('1.0.0')->isStable())->toBeTrue();
        expect(Version::parse('2.3.4')->isStable())->toBeTrue();
    });

    test('isStable returns false for pre-release versions', function (): void {
        expect(Version::parse('1.0.0-alpha')->isStable())->toBeFalse();
    });

    test('isStable returns false for 0.x.x versions', function (): void {
        expect(Version::parse('0.1.0')->isStable())->toBeFalse();
    });

    test('isPreRelease identifies pre-release versions', function (): void {
        expect(Version::parse('1.0.0-alpha')->isPreRelease())->toBeTrue();
        expect(Version::parse('1.0.0')->isPreRelease())->toBeFalse();
    });

    test('isDevelopment identifies 0.x.x versions', function (): void {
        expect(Version::parse('0.1.0')->isDevelopment())->toBeTrue();
        expect(Version::parse('1.0.0')->isDevelopment())->toBeFalse();
    });

    test('hasBuild identifies versions with build metadata', function (): void {
        expect(Version::parse('1.0.0+build')->hasBuild())->toBeTrue();
        expect(Version::parse('1.0.0')->hasBuild())->toBeFalse();
    });

    test('core returns major.minor.patch', function (): void {
        $version = Version::parse('1.2.3-alpha+build');
        expect($version->core())->toBe('1.2.3');
    });
});

describe('Version Comparison', function (): void {
    test('compares major versions', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('2.0.0');

        expect($v1->compareTo($v2))->toBeLessThan(0);
        expect($v2->compareTo($v1))->toBeGreaterThan(0);
    });

    test('compares minor versions', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.1.0');

        expect($v1->compareTo($v2))->toBeLessThan(0);
    });

    test('compares patch versions', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.0.1');

        expect($v1->compareTo($v2))->toBeLessThan(0);
    });

    test('pre-release has lower precedence than release', function (): void {
        $prerelease = Version::parse('1.0.0-alpha');
        $release = Version::parse('1.0.0');

        expect($prerelease->compareTo($release))->toBeLessThan(0);
        expect($release->compareTo($prerelease))->toBeGreaterThan(0);
    });

    test('compares pre-release versions', function (): void {
        $versions = [
            '1.0.0-alpha',
            '1.0.0-alpha.1',
            '1.0.0-alpha.beta',
            '1.0.0-beta',
            '1.0.0-beta.2',
            '1.0.0-beta.11',
            '1.0.0-rc.1',
            '1.0.0',
        ];

        for ($i = 0; $i < count($versions) - 1; ++$i) {
            $v1 = Version::parse($versions[$i]);
            $v2 = Version::parse($versions[$i + 1]);
            expect($v1->lessThan($v2))->toBeTrue(sprintf('Expected %s < %s', $versions[$i], $versions[$i + 1]));
        }
    });

    test('equals compares versions correctly', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.0.0');
        $v3 = Version::parse('1.0.1');

        expect($v1->equals($v2))->toBeTrue();
        expect($v1->equals($v3))->toBeFalse();
    });

    test('build metadata is ignored in comparison', function (): void {
        $v1 = Version::parse('1.0.0+build1');
        $v2 = Version::parse('1.0.0+build2');

        expect($v1->equals($v2))->toBeTrue();
        expect($v1->compareTo($v2))->toBe(0);
    });

    test('identical checks exact equality including build', function (): void {
        $v1 = Version::parse('1.0.0+build1');
        $v2 = Version::parse('1.0.0+build2');
        $v3 = Version::parse('1.0.0+build1');

        expect($v1->identical($v2))->toBeFalse();
        expect($v1->identical($v3))->toBeTrue();
    });

    test('comparison operators work correctly', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('2.0.0');

        expect($v1->lessThan($v2))->toBeTrue();
        expect($v1->lessThanOrEquals($v2))->toBeTrue();
        expect($v1->lessThanOrEquals($v1))->toBeTrue();
        expect($v2->greaterThan($v1))->toBeTrue();
        expect($v2->greaterThanOrEquals($v1))->toBeTrue();
        expect($v2->greaterThanOrEquals($v2))->toBeTrue();
    });
});

describe('Version Incrementing', function (): void {
    test('incrementMajor resets minor and patch', function (): void {
        $version = Version::parse('1.2.3-alpha+build');
        $incremented = $version->incrementMajor();

        expect((string) $incremented)->toBe('2.0.0');
    });

    test('incrementMinor resets patch', function (): void {
        $version = Version::parse('1.2.3-alpha+build');
        $incremented = $version->incrementMinor();

        expect((string) $incremented)->toBe('1.3.0');
    });

    test('incrementPatch clears pre-release and build', function (): void {
        $version = Version::parse('1.2.3-alpha+build');
        $incremented = $version->incrementPatch();

        expect((string) $incremented)->toBe('1.2.4');
    });

    test('incrementPreRelease increments numeric identifier', function (): void {
        $version = Version::parse('1.0.0-alpha.1');
        $incremented = $version->incrementPreRelease();

        expect((string) $incremented)->toBe('1.0.0-alpha.2');
    });

    test('incrementPreRelease appends 1 to non-numeric', function (): void {
        $version = Version::parse('1.0.0-alpha');
        $incremented = $version->incrementPreRelease();

        expect((string) $incremented)->toBe('1.0.0-alpha.1');
    });

    test('incrementPreRelease creates 0 from empty', function (): void {
        $version = Version::parse('1.0.0');
        $incremented = $version->incrementPreRelease();

        expect((string) $incremented)->toBe('1.0.0-0');
    });
});

describe('Version Modification', function (): void {
    test('withPreRelease sets pre-release from string', function (): void {
        $version = Version::parse('1.0.0');
        $modified = $version->withPreRelease('beta.1');

        expect((string) $modified)->toBe('1.0.0-beta.1');
    });

    test('withPreRelease sets pre-release from array', function (): void {
        $version = Version::parse('1.0.0');
        $modified = $version->withPreRelease(['rc', '1']);

        expect((string) $modified)->toBe('1.0.0-rc.1');
    });

    test('withoutPreRelease removes pre-release', function (): void {
        $version = Version::parse('1.0.0-alpha');
        $modified = $version->withoutPreRelease();

        expect((string) $modified)->toBe('1.0.0');
    });

    test('withBuild sets build from string', function (): void {
        $version = Version::parse('1.0.0');
        $modified = $version->withBuild('build.123');

        expect((string) $modified)->toBe('1.0.0+build.123');
    });

    test('withBuild sets build from array', function (): void {
        $version = Version::parse('1.0.0');
        $modified = $version->withBuild(['build', '123']);

        expect((string) $modified)->toBe('1.0.0+build.123');
    });

    test('withoutBuild removes build metadata', function (): void {
        $version = Version::parse('1.0.0+build');
        $modified = $version->withoutBuild();

        expect((string) $modified)->toBe('1.0.0');
    });
});

describe('Version Diff', function (): void {
    test('diff returns major for major difference', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('2.0.0');

        expect($v1->diff($v2))->toBe('major');
    });

    test('diff returns minor for minor difference', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.1.0');

        expect($v1->diff($v2))->toBe('minor');
    });

    test('diff returns patch for patch difference', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.0.1');

        expect($v1->diff($v2))->toBe('patch');
    });

    test('diff returns prerelease for pre-release difference', function (): void {
        $v1 = Version::parse('1.0.0-alpha');
        $v2 = Version::parse('1.0.0-beta');

        expect($v1->diff($v2))->toBe('prerelease');
    });

    test('diff returns build for build difference', function (): void {
        $v1 = Version::parse('1.0.0+build1');
        $v2 = Version::parse('1.0.0+build2');

        expect($v1->diff($v2))->toBe('build');
    });

    test('diff returns null for identical versions', function (): void {
        $v1 = Version::parse('1.0.0');
        $v2 = Version::parse('1.0.0');

        expect($v1->diff($v2))->toBeNull();
    });
});

describe('Version JSON Serialization', function (): void {
    test('jsonSerialize returns expected structure', function (): void {
        $version = Version::parse('1.2.3-alpha+build');
        $json = $version->jsonSerialize();

        expect($json)->toBe([
            'major' => 1,
            'minor' => 2,
            'patch' => 3,
            'prerelease' => 'alpha',
            'build' => 'build',
            'full' => '1.2.3-alpha+build',
        ]);
    });

    test('version can be JSON encoded', function (): void {
        $version = Version::parse('1.0.0');
        $json = json_encode($version);

        expect($json)->toContain('"major":1');
        expect($json)->toContain('"full":"1.0.0"');
    });
});
