<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\SemVerManager;
use Cline\SemVer\Version;

beforeEach(function (): void {
    $this->manager = new SemVerManager();
});

describe('SemVerManager Parsing', function (): void {
    test('parse returns Version', function (): void {
        $version = $this->manager->parse('1.2.3');

        expect($version)->toBeInstanceOf(Version::class);
        expect((string) $version)->toBe('1.2.3');
    });

    test('tryParse returns null on invalid', function (): void {
        expect($this->manager->tryParse('invalid'))->toBeNull();
    });

    test('valid checks version validity', function (): void {
        expect($this->manager->valid('1.2.3'))->toBeTrue();
        expect($this->manager->valid('invalid'))->toBeFalse();
    });

    test('coerce extracts version from string', function (): void {
        expect((string) $this->manager->coerce('v1.2.3'))->toBe('1.2.3');
        expect((string) $this->manager->coerce('1.2'))->toBe('1.2.0');
        expect((string) $this->manager->coerce('1'))->toBe('1.0.0');
    });

    test('coerce returns null on invalid', function (): void {
        expect($this->manager->coerce('not-a-version'))->toBeNull();
    });
});

describe('SemVerManager Creation', function (): void {
    test('create builds Version from components', function (): void {
        $version = $this->manager->create(1, 2, 3, 'alpha', 'build');

        expect((string) $version)->toBe('1.2.3-alpha+build');
    });
});

describe('SemVerManager Comparison', function (): void {
    test('compare returns comparison result', function (): void {
        expect($this->manager->compare('1.0.0', '2.0.0'))->toBeLessThan(0);
        expect($this->manager->compare('2.0.0', '1.0.0'))->toBeGreaterThan(0);
        expect($this->manager->compare('1.0.0', '1.0.0'))->toBe(0);
    });

    test('eq checks equality', function (): void {
        expect($this->manager->eq('1.0.0', '1.0.0'))->toBeTrue();
        expect($this->manager->eq('1.0.0', '2.0.0'))->toBeFalse();
    });

    test('neq checks inequality', function (): void {
        expect($this->manager->neq('1.0.0', '2.0.0'))->toBeTrue();
        expect($this->manager->neq('1.0.0', '1.0.0'))->toBeFalse();
    });

    test('lt checks less than', function (): void {
        expect($this->manager->lt('1.0.0', '2.0.0'))->toBeTrue();
        expect($this->manager->lt('2.0.0', '1.0.0'))->toBeFalse();
    });

    test('lte checks less than or equal', function (): void {
        expect($this->manager->lte('1.0.0', '2.0.0'))->toBeTrue();
        expect($this->manager->lte('1.0.0', '1.0.0'))->toBeTrue();
        expect($this->manager->lte('2.0.0', '1.0.0'))->toBeFalse();
    });

    test('gt checks greater than', function (): void {
        expect($this->manager->gt('2.0.0', '1.0.0'))->toBeTrue();
        expect($this->manager->gt('1.0.0', '2.0.0'))->toBeFalse();
    });

    test('gte checks greater than or equal', function (): void {
        expect($this->manager->gte('2.0.0', '1.0.0'))->toBeTrue();
        expect($this->manager->gte('1.0.0', '1.0.0'))->toBeTrue();
        expect($this->manager->gte('1.0.0', '2.0.0'))->toBeFalse();
    });

    test('cmp uses operator string', function (): void {
        expect($this->manager->cmp('1.0.0', '=', '1.0.0'))->toBeTrue();
        expect($this->manager->cmp('1.0.0', '!=', '2.0.0'))->toBeTrue();
        expect($this->manager->cmp('1.0.0', '<', '2.0.0'))->toBeTrue();
        expect($this->manager->cmp('1.0.0', '<=', '1.0.0'))->toBeTrue();
        expect($this->manager->cmp('2.0.0', '>', '1.0.0'))->toBeTrue();
        expect($this->manager->cmp('1.0.0', '>=', '1.0.0'))->toBeTrue();
    });
});

describe('SemVerManager Constraints', function (): void {
    test('satisfies checks constraint', function (): void {
        expect($this->manager->satisfies('1.5.0', '^1.0.0'))->toBeTrue();
        expect($this->manager->satisfies('2.0.0', '^1.0.0'))->toBeFalse();
    });

    test('parseConstraint returns Constraint', function (): void {
        $constraint = $this->manager->parseConstraint('^1.0.0');

        expect($constraint->isSatisfiedBy('1.5.0'))->toBeTrue();
    });
});

describe('SemVerManager Sorting', function (): void {
    test('sort returns ascending order', function (): void {
        $sorted = $this->manager->sort(['2.0.0', '1.0.0', '3.0.0']);

        expect(array_map(fn ($v): string => (string) $v, $sorted))->toBe(['1.0.0', '2.0.0', '3.0.0']);
    });

    test('rsort returns descending order', function (): void {
        $sorted = $this->manager->rsort(['2.0.0', '1.0.0', '3.0.0']);

        expect(array_map(fn ($v): string => (string) $v, $sorted))->toBe(['3.0.0', '2.0.0', '1.0.0']);
    });

    test('max returns highest version', function (): void {
        expect((string) $this->manager->max(['2.0.0', '1.0.0', '3.0.0']))->toBe('3.0.0');
    });

    test('max returns null for empty array', function (): void {
        expect($this->manager->max([]))->toBeNull();
    });

    test('min returns lowest version', function (): void {
        expect((string) $this->manager->min(['2.0.0', '1.0.0', '3.0.0']))->toBe('1.0.0');
    });

    test('min returns null for empty array', function (): void {
        expect($this->manager->min([]))->toBeNull();
    });

    test('maxSatisfying returns highest matching', function (): void {
        $versions = ['1.0.0', '1.5.0', '2.0.0', '3.0.0'];

        expect((string) $this->manager->maxSatisfying($versions, '^1.0.0'))->toBe('1.5.0');
    });

    test('minSatisfying returns lowest matching', function (): void {
        $versions = ['1.0.0', '1.5.0', '2.0.0', '3.0.0'];

        expect((string) $this->manager->minSatisfying($versions, '>=2.0.0'))->toBe('2.0.0');
    });
});

describe('SemVerManager Incrementing', function (): void {
    test('incMajor increments major', function (): void {
        expect((string) $this->manager->incMajor('1.2.3'))->toBe('2.0.0');
    });

    test('incMinor increments minor', function (): void {
        expect((string) $this->manager->incMinor('1.2.3'))->toBe('1.3.0');
    });

    test('incPatch increments patch', function (): void {
        expect((string) $this->manager->incPatch('1.2.3'))->toBe('1.2.4');
    });

    test('incPreRelease increments pre-release', function (): void {
        expect((string) $this->manager->incPreRelease('1.0.0-alpha.1'))->toBe('1.0.0-alpha.2');
    });
});

describe('SemVerManager Diff', function (): void {
    test('diff returns difference type', function (): void {
        expect($this->manager->diff('1.0.0', '2.0.0'))->toBe('major');
        expect($this->manager->diff('1.0.0', '1.1.0'))->toBe('minor');
        expect($this->manager->diff('1.0.0', '1.0.1'))->toBe('patch');
        expect($this->manager->diff('1.0.0-alpha', '1.0.0-beta'))->toBe('prerelease');
        expect($this->manager->diff('1.0.0', '1.0.0'))->toBeNull();
    });
});

describe('SemVerManager Collection', function (): void {
    test('collection creates VersionCollection', function (): void {
        $collection = $this->manager->collection(['1.0.0', '2.0.0']);

        expect($collection->count())->toBe(2);
    });

    test('collection creates empty when no args', function (): void {
        $collection = $this->manager->collection();

        expect($collection->isEmpty())->toBeTrue();
    });
});
