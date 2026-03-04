<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Version;
use Cline\SemVer\VersionCollection;

describe('VersionCollection Creation', function (): void {
    test('creates empty collection', function (): void {
        $collection = new VersionCollection();

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    test('creates from version strings', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0', '3.0.0']);

        expect($collection->count())->toBe(3);
    });

    test('creates from version objects', function (): void {
        $versions = [
            Version::parse('1.0.0'),
            Version::parse('2.0.0'),
        ];
        $collection = VersionCollection::fromVersions($versions);

        expect($collection->count())->toBe(2);
    });
});

describe('VersionCollection Adding', function (): void {
    test('add creates new collection with version', function (): void {
        $collection = new VersionCollection();
        $newCollection = $collection->add('1.0.0');

        expect($collection->count())->toBe(0);
        expect($newCollection->count())->toBe(1);
    });

    test('add accepts Version object', function (): void {
        $collection = new VersionCollection();
        $newCollection = $collection->add(Version::parse('1.0.0'));

        expect($newCollection->count())->toBe(1);
    });
});

describe('VersionCollection Access', function (): void {
    test('all returns all versions', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0']);

        expect(count($collection->all()))->toBe(2);
    });

    test('first returns first version', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0']);

        expect((string) $collection->first())->toBe('1.0.0');
    });

    test('first returns null for empty', function (): void {
        $collection = new VersionCollection();

        expect($collection->first())->toBeNull();
    });

    test('last returns last version', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0']);

        expect((string) $collection->last())->toBe('2.0.0');
    });

    test('last returns null for empty', function (): void {
        $collection = new VersionCollection();

        expect($collection->last())->toBeNull();
    });
});

describe('VersionCollection Sorting', function (): void {
    test('sorted returns ascending order', function (): void {
        $collection = VersionCollection::fromStrings(['2.0.0', '1.0.0', '3.0.0']);
        $sorted = $collection->sorted();

        expect($sorted->toStrings())->toBe(['1.0.0', '2.0.0', '3.0.0']);
    });

    test('rsorted returns descending order', function (): void {
        $collection = VersionCollection::fromStrings(['2.0.0', '1.0.0', '3.0.0']);
        $sorted = $collection->rsorted();

        expect($sorted->toStrings())->toBe(['3.0.0', '2.0.0', '1.0.0']);
    });

    test('max returns highest version', function (): void {
        $collection = VersionCollection::fromStrings(['2.0.0', '1.0.0', '3.0.0']);

        expect((string) $collection->max())->toBe('3.0.0');
    });

    test('max returns null for empty', function (): void {
        $collection = new VersionCollection();

        expect($collection->max())->toBeNull();
    });

    test('min returns lowest version', function (): void {
        $collection = VersionCollection::fromStrings(['2.0.0', '1.0.0', '3.0.0']);

        expect((string) $collection->min())->toBe('1.0.0');
    });

    test('min returns null for empty', function (): void {
        $collection = new VersionCollection();

        expect($collection->min())->toBeNull();
    });
});

describe('VersionCollection Filtering', function (): void {
    test('satisfying filters by constraint', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.5.0', '2.0.0', '3.0.0']);
        $filtered = $collection->satisfying('^1.0.0');

        expect($filtered->toStrings())->toBe(['1.0.0', '1.5.0']);
    });

    test('maxSatisfying returns highest matching', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.5.0', '2.0.0', '3.0.0']);

        expect((string) $collection->maxSatisfying('^1.0.0'))->toBe('1.5.0');
    });

    test('minSatisfying returns lowest matching', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.5.0', '2.0.0', '3.0.0']);

        expect((string) $collection->minSatisfying('^1.0.0'))->toBe('1.0.0');
    });

    test('stable filters to stable versions', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.0.0-alpha', '2.0.0']);
        $filtered = $collection->stable();

        expect($filtered->toStrings())->toBe(['1.0.0', '2.0.0']);
    });

    test('preReleases filters to pre-release versions', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.0.0-alpha', '2.0.0-beta']);
        $filtered = $collection->preReleases();

        expect($filtered->toStrings())->toBe(['1.0.0-alpha', '2.0.0-beta']);
    });

    test('major filters by major version', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.5.0', '2.0.0', '2.5.0']);
        $filtered = $collection->major(1);

        expect($filtered->toStrings())->toBe(['1.0.0', '1.5.0']);
    });

    test('minor filters by major and minor version', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '1.0.5', '1.1.0', '2.0.0']);
        $filtered = $collection->minor(1, 0);

        expect($filtered->toStrings())->toBe(['1.0.0', '1.0.5']);
    });

    test('unique removes duplicates by precedence', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0+build1', '1.0.0+build2', '2.0.0']);
        $unique = $collection->unique();

        expect($unique->count())->toBe(2);
    });

    test('filter applies custom callback', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0', '3.0.0']);
        $filtered = $collection->filter(fn (Version $v): bool => $v->major >= 2);

        expect($filtered->toStrings())->toBe(['2.0.0', '3.0.0']);
    });
});

describe('VersionCollection Mapping', function (): void {
    test('map transforms versions', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0']);
        $majors = $collection->map(fn (Version $v): int => $v->major);

        expect($majors)->toBe([1, 2]);
    });

    test('toStrings converts to string array', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0-alpha', '2.0.0+build']);

        expect($collection->toStrings())->toBe(['1.0.0-alpha', '2.0.0+build']);
    });
});

describe('VersionCollection Iteration', function (): void {
    test('collection is iterable', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0']);
        $versions = [];

        foreach ($collection as $version) {
            $versions[] = (string) $version;
        }

        expect($versions)->toBe(['1.0.0', '2.0.0']);
    });

    test('collection is countable', function (): void {
        $collection = VersionCollection::fromStrings(['1.0.0', '2.0.0', '3.0.0']);

        expect(count($collection))->toBe(3);
    });
});
