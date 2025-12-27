---
title: Version Collections
description: Filter, sort, and query collections of versions with constraint matching and fluent methods.
---

The `VersionCollection` class provides a fluent interface for working with multiple versions, including filtering, sorting, and constraint matching.

## Creating Collections

```php
use Cline\SemVer\VersionCollection;
use Cline\SemVer\Version;

// From version strings
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.1.0',
    '2.0.0',
    '2.0.0-alpha',
]);

// From Version objects
$collection = VersionCollection::fromVersions([
    Version::parse('1.0.0'),
    Version::parse('2.0.0'),
]);

// Empty collection
$collection = new VersionCollection();

// Via facade
use Cline\SemVer\Facades\SemVer;
$collection = SemVer::collection(['1.0.0', '2.0.0']);
```

## Adding Versions

```php
$collection = new VersionCollection();

// Add returns a new collection (immutable)
$updated = $collection->add('1.0.0');
$updated = $updated->add(Version::parse('2.0.0'));
```

## Accessing Versions

```php
$collection = VersionCollection::fromStrings(['1.0.0', '2.0.0', '3.0.0']);

// Get all versions
$versions = $collection->all(); // [Version, Version, Version]

// Count
$collection->count(); // 3
count($collection);   // 3

// Check if empty
$collection->isEmpty(); // false

// First and last
$collection->first(); // Version 1.0.0
$collection->last();  // Version 3.0.0

// Iterate
foreach ($collection as $version) {
    echo $version; // 1.0.0, 2.0.0, 3.0.0
}
```

## Sorting

```php
$collection = VersionCollection::fromStrings(['2.0.0', '1.0.0', '1.5.0']);

// Ascending order
$sorted = $collection->sorted();
$sorted->toStrings(); // ['1.0.0', '1.5.0', '2.0.0']

// Descending order
$reversed = $collection->rsorted();
$reversed->toStrings(); // ['2.0.0', '1.5.0', '1.0.0']
```

## Min and Max

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '2.5.0',
    '1.5.0-beta',
    '2.0.0',
]);

$collection->max(); // Version 2.5.0
$collection->min(); // Version 1.5.0-beta (pre-release < stable)
```

## Filtering by Constraint

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.5.0',
    '2.0.0',
    '2.5.0',
    '3.0.0',
]);

// Filter versions satisfying a constraint
$filtered = $collection->satisfying('^1.0.0');
$filtered->toStrings(); // ['1.0.0', '1.5.0']

$filtered = $collection->satisfying('>=2.0.0 <3.0.0');
$filtered->toStrings(); // ['2.0.0', '2.5.0']

// Get max/min satisfying a constraint
$collection->maxSatisfying('^1.0.0'); // Version 1.5.0
$collection->minSatisfying('^1.0.0'); // Version 1.0.0

$collection->maxSatisfying('>=2.0.0'); // Version 3.0.0
$collection->minSatisfying('>=2.0.0'); // Version 2.0.0
```

## Filtering by Stability

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.5.0-alpha',
    '2.0.0-beta',
    '2.0.0',
]);

// Only stable versions
$stable = $collection->stable();
$stable->toStrings(); // ['1.0.0', '2.0.0']

// Only pre-release versions
$preReleases = $collection->preReleases();
$preReleases->toStrings(); // ['1.5.0-alpha', '2.0.0-beta']
```

## Filtering by Version Components

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.1.0',
    '2.0.0',
    '2.1.0',
    '2.1.1',
]);

// Filter by major version
$v1 = $collection->major(1);
$v1->toStrings(); // ['1.0.0', '1.1.0']

$v2 = $collection->major(2);
$v2->toStrings(); // ['2.0.0', '2.1.0', '2.1.1']

// Filter by major and minor version
$v21 = $collection->minor(2, 1);
$v21->toStrings(); // ['2.1.0', '2.1.1']
```

## Removing Duplicates

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.0.0+build.1',
    '1.0.0+build.2',
    '2.0.0',
]);

// Unique by precedence (ignores build metadata)
$unique = $collection->unique();
$unique->count(); // 2 (1.0.0 and 2.0.0)
```

## Custom Filtering

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.5.0',
    '2.0.0-alpha',
    '2.0.0',
]);

// Filter with custom callback
$filtered = $collection->filter(
    fn (Version $v) => $v->major >= 2
);
$filtered->toStrings(); // ['2.0.0-alpha', '2.0.0']

// Complex filtering
$filtered = $collection->filter(
    fn (Version $v) => $v->isStable() && $v->major === 1
);
$filtered->toStrings(); // ['1.0.0', '1.5.0']
```

## Mapping

```php
$collection = VersionCollection::fromStrings(['1.0.0', '2.0.0', '3.0.0']);

// Map to strings
$strings = $collection->map(fn (Version $v) => (string) $v);
// ['1.0.0', '2.0.0', '3.0.0']

// Map to core versions
$cores = $collection->map(fn (Version $v) => $v->core());
// ['1.0.0', '2.0.0', '3.0.0']

// Map to major versions
$majors = $collection->map(fn (Version $v) => $v->major);
// [1, 2, 3]
```

## Converting to Strings

```php
$collection = VersionCollection::fromStrings([
    '1.0.0-alpha',
    '2.0.0+build',
]);

$strings = $collection->toStrings();
// ['1.0.0-alpha', '2.0.0+build']
```

## Chaining Methods

All filtering and sorting methods return new `VersionCollection` instances, allowing fluent chaining:

```php
$collection = VersionCollection::fromStrings([
    '1.0.0',
    '1.5.0-alpha',
    '2.0.0',
    '2.5.0',
    '3.0.0-rc.1',
]);

$result = $collection
    ->satisfying('>=1.0.0 <3.0.0')  // 1.0.0, 1.5.0-alpha, 2.0.0, 2.5.0
    ->stable()                       // 1.0.0, 2.0.0, 2.5.0
    ->rsorted()                      // 2.5.0, 2.0.0, 1.0.0
    ->first();                       // Version 2.5.0
```

## Using with SemVer Facade

```php
use Cline\SemVer\Facades\SemVer;

$versions = ['1.0.0', '1.5.0', '2.0.0'];

// Get max/min directly
SemVer::max($versions);  // Version 2.0.0
SemVer::min($versions);  // Version 1.0.0

// Sort
SemVer::sort($versions);  // [Version 1.0.0, Version 1.5.0, Version 2.0.0]
SemVer::rsort($versions); // [Version 2.0.0, Version 1.5.0, Version 1.0.0]

// Max/min satisfying constraint
SemVer::maxSatisfying($versions, '^1.0.0'); // Version 1.5.0
SemVer::minSatisfying($versions, '^1.0.0'); // Version 1.0.0
```
