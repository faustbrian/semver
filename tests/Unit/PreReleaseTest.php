<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Exceptions\InvalidPreReleaseException;
use Cline\SemVer\PreRelease;

describe('PreRelease Parsing', function (): void {
    test('parses simple identifier', function (): void {
        $preRelease = PreRelease::fromString('alpha');

        expect($preRelease->identifiers())->toBe(['alpha']);
        expect((string) $preRelease)->toBe('alpha');
    });

    test('parses multiple identifiers', function (): void {
        $preRelease = PreRelease::fromString('alpha.1.beta.2');

        expect($preRelease->identifiers())->toBe(['alpha', '1', 'beta', '2']);
        expect((string) $preRelease)->toBe('alpha.1.beta.2');
    });

    test('parses numeric identifier', function (): void {
        $preRelease = PreRelease::fromString('0');

        expect($preRelease->identifiers())->toBe(['0']);
    });

    test('parses identifier with hyphen', function (): void {
        $preRelease = PreRelease::fromString('alpha-beta');

        expect($preRelease->identifiers())->toBe(['alpha-beta']);
    });

    test('throws on empty string', function (): void {
        PreRelease::fromString('');
    })->throws(InvalidPreReleaseException::class);

    test('throws on empty identifier', function (): void {
        PreRelease::fromString('alpha..beta');
    })->throws(InvalidPreReleaseException::class);

    test('throws on invalid characters', function (): void {
        PreRelease::fromString('alpha_beta');
    })->throws(InvalidPreReleaseException::class);

    test('throws on numeric with leading zeros', function (): void {
        PreRelease::fromString('01');
    })->throws(InvalidPreReleaseException::class);

    test('allows alphanumeric with leading zeros', function (): void {
        // "01a" is alphanumeric, not numeric, so leading zeros are allowed
        $preRelease = PreRelease::fromString('01a');
        expect($preRelease->identifiers())->toBe(['01a']);
    });
});

describe('PreRelease Creation', function (): void {
    test('creates from array', function (): void {
        $preRelease = PreRelease::fromArray(['alpha', '1']);

        expect($preRelease->identifiers())->toBe(['alpha', '1']);
    });

    test('creates empty', function (): void {
        $preRelease = PreRelease::empty();

        expect($preRelease->isEmpty())->toBeTrue();
        expect($preRelease->identifiers())->toBe([]);
    });

    test('throws on empty array', function (): void {
        PreRelease::fromArray([]);
    })->throws(InvalidPreReleaseException::class);
});

describe('PreRelease Properties', function (): void {
    test('count returns number of identifiers', function (): void {
        $preRelease = PreRelease::fromString('alpha.1.beta');

        expect($preRelease->count())->toBe(3);
    });

    test('at returns identifier by index', function (): void {
        $preRelease = PreRelease::fromString('alpha.1');

        expect($preRelease->at(0))->toBe('alpha');
        expect($preRelease->at(1))->toBe('1');
        expect($preRelease->at(2))->toBeNull();
    });

    test('isEmpty returns true for empty', function (): void {
        expect(PreRelease::empty()->isEmpty())->toBeTrue();
    });

    test('isEmpty returns false for non-empty', function (): void {
        expect(PreRelease::fromString('alpha')->isEmpty())->toBeFalse();
    });
});

describe('PreRelease Comparison', function (): void {
    test('empty has higher precedence than non-empty', function (): void {
        $empty = PreRelease::empty();
        $alpha = PreRelease::fromString('alpha');

        expect($empty->compareTo($alpha))->toBeGreaterThan(0);
        expect($alpha->compareTo($empty))->toBeLessThan(0);
    });

    test('numeric identifiers compared numerically', function (): void {
        $v2 = PreRelease::fromString('2');
        $v11 = PreRelease::fromString('11');

        expect($v2->compareTo($v11))->toBeLessThan(0);
    });

    test('alphanumeric identifiers compared lexically', function (): void {
        $alpha = PreRelease::fromString('alpha');
        $beta = PreRelease::fromString('beta');

        expect($alpha->compareTo($beta))->toBeLessThan(0);
    });

    test('numeric has lower precedence than alphanumeric', function (): void {
        $numeric = PreRelease::fromString('1');
        $alpha = PreRelease::fromString('alpha');

        expect($numeric->compareTo($alpha))->toBeLessThan(0);
    });

    test('larger set has higher precedence with equal prefix', function (): void {
        $short = PreRelease::fromString('alpha');
        $long = PreRelease::fromString('alpha.1');

        expect($short->compareTo($long))->toBeLessThan(0);
    });

    test('follows semver precedence order', function (): void {
        $versions = [
            'alpha',
            'alpha.1',
            'alpha.beta',
            'beta',
            'beta.2',
            'beta.11',
            'rc.1',
        ];

        for ($i = 0; $i < count($versions) - 1; ++$i) {
            $v1 = PreRelease::fromString($versions[$i]);
            $v2 = PreRelease::fromString($versions[$i + 1]);
            expect($v1->lessThan($v2))->toBeTrue(sprintf('Expected %s < %s', $versions[$i], $versions[$i + 1]));
        }
    });

    test('equals compares correctly', function (): void {
        $v1 = PreRelease::fromString('alpha.1');
        $v2 = PreRelease::fromString('alpha.1');
        $v3 = PreRelease::fromString('alpha.2');

        expect($v1->equals($v2))->toBeTrue();
        expect($v1->equals($v3))->toBeFalse();
    });

    test('comparison operators work correctly', function (): void {
        $v1 = PreRelease::fromString('alpha');
        $v2 = PreRelease::fromString('beta');

        expect($v1->lessThan($v2))->toBeTrue();
        expect($v1->lessThanOrEquals($v2))->toBeTrue();
        expect($v1->lessThanOrEquals($v1))->toBeTrue();
        expect($v2->greaterThan($v1))->toBeTrue();
        expect($v2->greaterThanOrEquals($v1))->toBeTrue();
        expect($v2->greaterThanOrEquals($v2))->toBeTrue();
    });
});

describe('PreRelease Increment', function (): void {
    test('increments numeric identifier', function (): void {
        $preRelease = PreRelease::fromString('alpha.1');
        $incremented = $preRelease->increment();

        expect((string) $incremented)->toBe('alpha.2');
    });

    test('appends 1 to non-numeric', function (): void {
        $preRelease = PreRelease::fromString('alpha');
        $incremented = $preRelease->increment();

        expect((string) $incremented)->toBe('alpha.1');
    });

    test('increments from empty', function (): void {
        $preRelease = PreRelease::empty();
        $incremented = $preRelease->increment();

        expect((string) $incremented)->toBe('0');
    });

    test('increments zero to one', function (): void {
        $preRelease = PreRelease::fromString('0');
        $incremented = $preRelease->increment();

        expect((string) $incremented)->toBe('1');
    });
});
