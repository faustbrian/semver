## Table of Contents

1. Overview (`docs/README.md`)
2. Collections (`docs/collections.md`)
3. Constraints (`docs/constraints.md`)
4. Laravel (`docs/laravel.md`)
5. Versions (`docs/versions.md`)
SemVer is a complete implementation of [Semantic Versioning 2.0.0](https://semver.org/) for PHP, providing parsing, comparison, constraints, and version operations with full Laravel integration.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/semver
```

The package auto-registers with Laravel via package discovery.

## Quick Example

```php
use Cline\SemVer\Version;
use Cline\SemVer\Constraint;

// Parse a version string
$version = Version::parse('2.1.0-beta.1+build.456');

echo $version->major;      // 2
echo $version->minor;      // 1
echo $version->patch;      // 0
echo $version->preRelease; // beta.1
echo $version->build;      // build.456

// Check constraints
$constraint = Constraint::parse('^2.0.0');
$constraint->isSatisfiedBy($version); // true

// Compare versions
$v1 = Version::parse('1.0.0');
$v2 = Version::parse('2.0.0');
$v1->lessThan($v2); // true
```

## Laravel Facade

```php
use Cline\SemVer\Facades\SemVer;

// Parse and validate
$version = SemVer::parse('1.2.3');
$isValid = SemVer::valid('1.2.3'); // true

// Compare versions
SemVer::gt('2.0.0', '1.0.0');  // true
SemVer::lt('1.0.0', '2.0.0');  // true
SemVer::eq('1.0.0', '1.0.0');  // true

// Check constraints
SemVer::satisfies('1.5.0', '^1.0.0'); // true

// Increment versions
$next = SemVer::incMajor('1.2.3'); // 2.0.0
$next = SemVer::incMinor('1.2.3'); // 1.3.0
$next = SemVer::incPatch('1.2.3'); // 1.2.4
```

## Core Classes

| Class | Purpose |
|-------|---------|
| `Version` | Immutable version representation with parsing and comparison |
| `Constraint` | Version constraint matching (^, ~, ranges, wildcards) |
| `VersionCollection` | Collection with filtering and sorting |
| `PreRelease` | Pre-release identifier handling |
| `Build` | Build metadata handling |
| `SemVerManager` | Unified API for all operations |

## SemVer 2.0.0 Compliance

This package fully implements the [SemVer 2.0.0 specification](https://semver.org/):

- **Version Format**: `MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]`
- **Pre-release Precedence**: Numeric identifiers compare numerically; alphanumeric compare lexically
- **Build Metadata**: Ignored in version precedence comparisons
- **Leading Zeros**: Not allowed in numeric version components

## Next Steps

- [Version Operations](./versions.md) - Parsing, comparison, and manipulation
- [Constraints](./constraints.md) - Tilde, caret, ranges, and wildcards
- [Collections](./collections.md) - Filtering and sorting version lists
- [Laravel Integration](./laravel.md) - Facade, casting, and service container

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

The `Constraint` class provides powerful version matching with support for npm/Composer-style constraints.

## Parsing Constraints

```php
use Cline\SemVer\Constraint;

$constraint = Constraint::parse('^1.0.0');
$constraint->isSatisfiedBy('1.5.0'); // true
$constraint->isSatisfiedBy('2.0.0'); // false
```

## Exact Versions

```php
// Exact match
Constraint::parse('1.2.3')->isSatisfiedBy('1.2.3'); // true
Constraint::parse('1.2.3')->isSatisfiedBy('1.2.4'); // false

// Explicit equals
Constraint::parse('=1.2.3')->isSatisfiedBy('1.2.3'); // true
```

## Comparison Operators

```php
// Less than
Constraint::parse('<2.0.0')->isSatisfiedBy('1.5.0'); // true
Constraint::parse('<2.0.0')->isSatisfiedBy('2.0.0'); // false

// Less than or equal
Constraint::parse('<=2.0.0')->isSatisfiedBy('2.0.0'); // true
Constraint::parse('<=2.0.0')->isSatisfiedBy('2.0.1'); // false

// Greater than
Constraint::parse('>1.0.0')->isSatisfiedBy('1.5.0'); // true
Constraint::parse('>1.0.0')->isSatisfiedBy('1.0.0'); // false

// Greater than or equal
Constraint::parse('>=1.0.0')->isSatisfiedBy('1.0.0'); // true
Constraint::parse('>=1.0.0')->isSatisfiedBy('0.9.0'); // false

// Not equal
Constraint::parse('!=1.0.0')->isSatisfiedBy('1.0.1'); // true
Constraint::parse('!=1.0.0')->isSatisfiedBy('1.0.0'); // false
```

## Caret Ranges (^)

Caret allows changes that do not modify the left-most non-zero digit:

```php
// ^1.2.3 → >=1.2.3 <2.0.0
Constraint::parse('^1.2.3')->isSatisfiedBy('1.2.3'); // true
Constraint::parse('^1.2.3')->isSatisfiedBy('1.9.9'); // true
Constraint::parse('^1.2.3')->isSatisfiedBy('2.0.0'); // false

// ^0.2.3 → >=0.2.3 <0.3.0 (0.x is special)
Constraint::parse('^0.2.3')->isSatisfiedBy('0.2.9'); // true
Constraint::parse('^0.2.3')->isSatisfiedBy('0.3.0'); // false

// ^0.0.3 → >=0.0.3 <0.0.4 (0.0.x is even more special)
Constraint::parse('^0.0.3')->isSatisfiedBy('0.0.3'); // true
Constraint::parse('^0.0.3')->isSatisfiedBy('0.0.4'); // false
```

## Tilde Ranges (~)

Tilde allows patch-level changes:

```php
// ~1.2.3 → >=1.2.3 <1.3.0
Constraint::parse('~1.2.3')->isSatisfiedBy('1.2.3'); // true
Constraint::parse('~1.2.3')->isSatisfiedBy('1.2.9'); // true
Constraint::parse('~1.2.3')->isSatisfiedBy('1.3.0'); // false

// ~1.2 → >=1.2.0 <1.3.0
Constraint::parse('~1.2')->isSatisfiedBy('1.2.0'); // true
Constraint::parse('~1.2')->isSatisfiedBy('1.2.9'); // true
Constraint::parse('~1.2')->isSatisfiedBy('1.3.0'); // false
```

## Hyphen Ranges

```php
// 1.2.3 - 2.3.4 → >=1.2.3 <=2.3.4
Constraint::parse('1.2.3 - 2.3.4')->isSatisfiedBy('1.2.3'); // true
Constraint::parse('1.2.3 - 2.3.4')->isSatisfiedBy('2.0.0'); // true
Constraint::parse('1.2.3 - 2.3.4')->isSatisfiedBy('2.3.4'); // true
Constraint::parse('1.2.3 - 2.3.4')->isSatisfiedBy('2.3.5'); // false

// Partial versions in ranges
// 1.2 - 2.3 → >=1.2.0 <2.4.0 (partial "to" becomes exclusive next minor)
Constraint::parse('1.2 - 2.3')->isSatisfiedBy('2.3.0'); // true
Constraint::parse('1.2 - 2.3')->isSatisfiedBy('2.3.9'); // true
Constraint::parse('1.2 - 2.3')->isSatisfiedBy('2.4.0'); // false
```

## Wildcards

```php
// 1.x → >=1.0.0 <2.0.0
Constraint::parse('1.x')->isSatisfiedBy('1.0.0'); // true
Constraint::parse('1.x')->isSatisfiedBy('1.9.9'); // true
Constraint::parse('1.x')->isSatisfiedBy('2.0.0'); // false

// 1.2.x → >=1.2.0 <1.3.0
Constraint::parse('1.2.x')->isSatisfiedBy('1.2.0'); // true
Constraint::parse('1.2.x')->isSatisfiedBy('1.2.9'); // true
Constraint::parse('1.2.x')->isSatisfiedBy('1.3.0'); // false

// Also supports * and X
Constraint::parse('1.*')->isSatisfiedBy('1.5.0');   // true
Constraint::parse('1.2.X')->isSatisfiedBy('1.2.5'); // true

// Match all versions
Constraint::parse('*')->isSatisfiedBy('99.99.99'); // true
```

## AND Constraints (Space/Comma)

Multiple constraints separated by space or comma must ALL be satisfied:

```php
// Space-separated (AND)
Constraint::parse('>=1.0.0 <2.0.0')->isSatisfiedBy('1.5.0'); // true
Constraint::parse('>=1.0.0 <2.0.0')->isSatisfiedBy('2.0.0'); // false

// Comma-separated (AND)
Constraint::parse('>=1.0.0, <2.0.0')->isSatisfiedBy('1.5.0'); // true
```

## OR Constraints (||)

Constraints separated by `||` match if ANY is satisfied:

```php
// Match 1.x OR 2.x
Constraint::parse('1.x || 2.x')->isSatisfiedBy('1.5.0'); // true
Constraint::parse('1.x || 2.x')->isSatisfiedBy('2.0.0'); // true
Constraint::parse('1.x || 2.x')->isSatisfiedBy('3.0.0'); // false

// Complex OR
Constraint::parse('^1.0.0 || ^2.0.0')->isSatisfiedBy('1.5.0'); // true
Constraint::parse('^1.0.0 || ^2.0.0')->isSatisfiedBy('2.5.0'); // true
```

## Combining Constraints Programmatically

```php
$c1 = Constraint::parse('>=1.0.0');
$c2 = Constraint::parse('<2.0.0');

// Combine with AND
$combined = $c1->and($c2);
$combined->isSatisfiedBy('1.5.0'); // true
$combined->isSatisfiedBy('2.0.0'); // false

// Combine with OR
$either = Constraint::parse('^1.0.0')->or(Constraint::parse('^2.0.0'));
$either->isSatisfiedBy('1.5.0'); // true
$either->isSatisfiedBy('2.5.0'); // true
```

## Factory Methods

```php
use Cline\SemVer\Constraint;
use Cline\SemVer\Enums\Operator;

// Create exact version constraint
$exact = Constraint::exact('1.2.3');

// Create with specific operator
$gte = Constraint::withOperator(Operator::GreaterThanOrEqual, '1.0.0');
$lt = Constraint::withOperator(Operator::LessThan, '2.0.0');
```

## Using with Version Objects

```php
use Cline\SemVer\Version;
use Cline\SemVer\Constraint;

$version = Version::parse('1.5.0');
$constraint = Constraint::parse('^1.0.0');

// Check from constraint
$constraint->isSatisfiedBy($version); // true

// Check from version
$version->satisfies($constraint); // true
```

## Pre-release Handling

Pre-release versions only match if the constraint explicitly includes a pre-release:

```php
// Pre-release doesn't match non-pre-release constraint
Constraint::parse('^1.0.0')->isSatisfiedBy('1.5.0-alpha'); // false (by convention)

// But does satisfy the version comparison
Constraint::parse('>=1.0.0')->isSatisfiedBy('1.5.0-alpha'); // true (alpha < 1.5.0 stable)

// Pre-release constraints
Constraint::parse('>=1.0.0-alpha')->isSatisfiedBy('1.0.0-beta'); // true
```

## Operator Reference

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Exact match | `=1.2.3` |
| `!=` | Not equal | `!=1.2.3` |
| `<` | Less than | `<2.0.0` |
| `<=` | Less than or equal | `<=2.0.0` |
| `>` | Greater than | `>1.0.0` |
| `>=` | Greater than or equal | `>=1.0.0` |
| `~` | Tilde range | `~1.2.3` |
| `^` | Caret range | `^1.2.3` |
| ` ` | AND (space) | `>=1.0.0 <2.0.0` |
| `,` | AND (comma) | `>=1.0.0, <2.0.0` |
| `\|\|` | OR | `1.x \|\| 2.x` |
| `-` | Hyphen range | `1.2.3 - 2.0.0` |
| `x`, `X`, `*` | Wildcard | `1.x`, `1.*` |

SemVer provides first-class Laravel integration with automatic service registration, a facade, and Eloquent attribute casting.

## Installation

```bash
composer require cline/semver
```

The package auto-registers via Laravel's package discovery. No manual service provider registration is needed.

## Using the Facade

The `SemVer` facade provides a clean, static API for all version operations:

```php
use Cline\SemVer\Facades\SemVer;

// Parsing
$version = SemVer::parse('1.2.3');
$version = SemVer::tryParse('invalid'); // null
$isValid = SemVer::valid('1.2.3'); // true

// Creating
$version = SemVer::create(1, 2, 3);
$version = SemVer::create(1, 2, 3, 'alpha', 'build.123');

// Coercing (lenient parsing)
$version = SemVer::coerce('v1.2');   // 1.2.0
$version = SemVer::coerce('1');      // 1.0.0
$version = SemVer::coerce('garbage'); // null
```

### Comparison Methods

```php
use Cline\SemVer\Facades\SemVer;

// Individual comparisons
SemVer::eq('1.0.0', '1.0.0');   // true (equal)
SemVer::neq('1.0.0', '2.0.0'); // true (not equal)
SemVer::lt('1.0.0', '2.0.0');   // true (less than)
SemVer::lte('1.0.0', '1.0.0'); // true (less than or equal)
SemVer::gt('2.0.0', '1.0.0');   // true (greater than)
SemVer::gte('2.0.0', '2.0.0'); // true (greater than or equal)

// Compare with result
SemVer::compare('1.0.0', '2.0.0'); // -1
SemVer::compare('2.0.0', '1.0.0'); // 1
SemVer::compare('1.0.0', '1.0.0'); // 0

// Compare with operator
SemVer::cmp('1.0.0', '>=', '1.0.0'); // true
SemVer::cmp('1.0.0', '<', '2.0.0');  // true
SemVer::cmp('1.0.0', '!=', '2.0.0'); // true
```

### Increment Methods

```php
use Cline\SemVer\Facades\SemVer;

$next = SemVer::incMajor('1.2.3');      // 2.0.0
$next = SemVer::incMinor('1.2.3');      // 1.3.0
$next = SemVer::incPatch('1.2.3');      // 1.2.4
$next = SemVer::incPreRelease('1.2.3'); // 1.2.3-0
```

### Constraint Checking

```php
use Cline\SemVer\Facades\SemVer;

// Check satisfaction
SemVer::satisfies('1.5.0', '^1.0.0'); // true
SemVer::satisfies('2.0.0', '^1.0.0'); // false

// Parse constraint
$constraint = SemVer::parseConstraint('^1.0.0 || ^2.0.0');
$constraint->isSatisfiedBy('1.5.0'); // true
$constraint->isSatisfiedBy('2.5.0'); // true
```

### Sorting and Finding

```php
use Cline\SemVer\Facades\SemVer;

$versions = ['2.0.0', '1.0.0', '1.5.0'];

// Sort
SemVer::sort($versions);  // [1.0.0, 1.5.0, 2.0.0]
SemVer::rsort($versions); // [2.0.0, 1.5.0, 1.0.0]

// Find extremes
SemVer::max($versions); // 2.0.0
SemVer::min($versions); // 1.0.0

// Find with constraints
SemVer::maxSatisfying($versions, '^1.0.0'); // 1.5.0
SemVer::minSatisfying($versions, '^1.0.0'); // 1.0.0
```

### Difference Detection

```php
use Cline\SemVer\Facades\SemVer;

SemVer::diff('1.0.0', '2.0.0');       // 'major'
SemVer::diff('1.0.0', '1.1.0');       // 'minor'
SemVer::diff('1.0.0', '1.0.1');       // 'patch'
SemVer::diff('1.0.0', '1.0.0-alpha'); // 'prerelease'
SemVer::diff('1.0.0+a', '1.0.0+b');   // 'build'
SemVer::diff('1.0.0', '1.0.0');       // null
```

### Collections

```php
use Cline\SemVer\Facades\SemVer;

$collection = SemVer::collection(['1.0.0', '2.0.0', '3.0.0']);

$collection->satisfying('^1.0.0 || ^2.0.0');
$collection->stable();
$collection->sorted();
```

## Eloquent Casting

Use `VersionCast` to automatically cast database columns to `Version` objects:

```php
use Cline\SemVer\Casts\VersionCast;
use Cline\SemVer\Version;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected function casts(): array
    {
        return [
            'version' => VersionCast::class,
            'min_version' => VersionCast::class,
            'max_version' => VersionCast::class,
        ];
    }
}
```

### Usage

```php
// Create with string
$package = Package::create([
    'name' => 'my-package',
    'version' => '1.2.3',
]);

// The version is automatically a Version object
$package->version;            // Version object
$package->version->major;     // 1
$package->version->isStable(); // true

// Set with Version object
$package->version = Version::parse('2.0.0');
$package->save();

// Compare versions
if ($package->version->greaterThan(Version::parse('1.0.0'))) {
    // Newer version
}

// Check constraints
if ($package->version->satisfies(Constraint::parse('^1.0.0'))) {
    // Compatible
}
```

### Database Storage

Versions are stored as strings in the database:

```php
Schema::create('packages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('version');        // Stored as "1.2.3-alpha+build"
    $table->string('min_version')->nullable();
    $table->string('max_version')->nullable();
    $table->timestamps();
});
```

### Null Handling

The cast handles null values gracefully:

```php
$package = Package::create([
    'name' => 'my-package',
    'version' => '1.0.0',
    'min_version' => null, // Stays null
]);

$package->min_version; // null
```

## Dependency Injection

The `SemVerManager` is registered as a singleton and can be injected:

```php
use Cline\SemVer\SemVerManager;

class VersionService
{
    public function __construct(
        private SemVerManager $semver,
    ) {}

    public function isCompatible(string $installed, string $required): bool
    {
        return $this->semver->satisfies($installed, $required);
    }

    public function getLatestCompatible(array $versions, string $constraint): ?Version
    {
        return $this->semver->maxSatisfying($versions, $constraint);
    }
}
```

## Real-World Examples

### Package Compatibility Checking

```php
use Cline\SemVer\Facades\SemVer;

class DependencyResolver
{
    public function canInstall(string $requiredVersion, string $installedVersion): bool
    {
        return SemVer::satisfies($installedVersion, $requiredVersion);
    }

    public function findBestVersion(array $available, string $constraint): ?Version
    {
        return SemVer::maxSatisfying($available, $constraint);
    }

    public function needsUpgrade(string $current, string $latest): bool
    {
        return SemVer::lt($current, $latest);
    }

    public function isBreakingChange(string $from, string $to): bool
    {
        return SemVer::diff($from, $to) === 'major';
    }
}
```

### Version Filtering in Queries

```php
use App\Models\Package;
use Cline\SemVer\Facades\SemVer;

// Get all packages, filter in PHP
$compatiblePackages = Package::all()
    ->filter(fn ($p) => SemVer::satisfies((string) $p->version, '^2.0.0'));

// Or use the collection
$versions = Package::pluck('version')->toArray();
$latest = SemVer::maxSatisfying($versions, '^2.0.0');
```

### Changelog Generation

```php
use Cline\SemVer\Facades\SemVer;

function categorizeChanges(array $releases): array
{
    $categorized = ['major' => [], 'minor' => [], 'patch' => []];

    $sorted = SemVer::sort($releases);

    for ($i = 1; $i < count($sorted); $i++) {
        $diff = SemVer::diff($sorted[$i - 1], $sorted[$i]);
        if ($diff && isset($categorized[$diff])) {
            $categorized[$diff][] = (string) $sorted[$i];
        }
    }

    return $categorized;
}
```

The `Version` class is an immutable representation of a semantic version, providing parsing, comparison, and manipulation methods.

## Parsing Versions

```php
use Cline\SemVer\Version;

// Parse a version string (throws on invalid)
$version = Version::parse('1.2.3');
$version = Version::parse('1.2.3-alpha.1');
$version = Version::parse('1.2.3-beta+build.456');
$version = Version::parse('v1.2.3'); // Leading 'v' is allowed

// Safe parsing (returns null on invalid)
$version = Version::tryParse('invalid'); // null
$version = Version::tryParse('1.2.3');   // Version

// Validate without parsing
Version::isValid('1.2.3');   // true
Version::isValid('1.2');     // false
Version::isValid('v1.2.3');  // true
```

## Creating Versions

```php
use Cline\SemVer\Version;

// Create from components
$version = Version::create(1, 2, 3);
$version = Version::create(1, 2, 3, 'alpha.1');
$version = Version::create(1, 2, 3, 'beta', 'build.456');

// With array identifiers
$version = Version::create(1, 2, 3, ['alpha', '1']);
$version = Version::create(1, 2, 3, ['rc', '1'], ['build', '789']);
```

## Accessing Components

```php
$version = Version::parse('2.1.0-beta.1+build.456');

// Core components
$version->major;      // 2
$version->minor;      // 1
$version->patch;      // 0
$version->core();     // "2.1.0"

// Pre-release (PreRelease object)
$version->preRelease;               // PreRelease object
(string) $version->preRelease;      // "beta.1"
$version->preRelease->isEmpty();    // false
$version->preRelease->identifiers(); // ['beta', '1']
$version->preRelease->at(0);        // 'beta'

// Build metadata (Build object)
$version->build;                    // Build object
(string) $version->build;           // "build.456"
$version->build->isEmpty();         // false
$version->build->identifiers();     // ['build', '456']
```

## Version State Checks

```php
$version = Version::parse('1.0.0-alpha');

// Stability checks
$version->isStable();       // false (has pre-release)
$version->isPreRelease();   // true
$version->isDevelopment();  // false (major > 0)
$version->hasBuild();       // false

// Examples
Version::parse('1.0.0')->isStable();       // true
Version::parse('0.1.0')->isStable();       // false (development)
Version::parse('1.0.0-rc.1')->isStable();  // false (pre-release)
```

## Comparing Versions

```php
$v1 = Version::parse('1.2.3');
$v2 = Version::parse('1.3.0');

// Comparison methods
$v1->compareTo($v2);          // -1 (v1 < v2)
$v1->equals($v2);             // false
$v1->lessThan($v2);           // true
$v1->greaterThan($v2);        // false
$v1->lessThanOrEquals($v2);   // true
$v1->greaterThanOrEquals($v2); // false

// Exact equality (including build metadata)
$a = Version::parse('1.0.0+build.1');
$b = Version::parse('1.0.0+build.2');
$a->equals($b);     // true (build ignored per SemVer)
$a->identical($b);  // false (build differs)
```

## Pre-release Precedence

Pre-release versions follow SemVer 2.0.0 precedence rules:

```php
// Pre-release < stable
Version::parse('1.0.0-alpha')->lessThan(Version::parse('1.0.0')); // true

// Numeric identifiers compare numerically
Version::parse('1.0.0-alpha.1')->lessThan(Version::parse('1.0.0-alpha.2')); // true
Version::parse('1.0.0-alpha.9')->lessThan(Version::parse('1.0.0-alpha.10')); // true

// Alphanumeric compare lexically
Version::parse('1.0.0-alpha')->lessThan(Version::parse('1.0.0-beta')); // true

// Numeric < alphanumeric
Version::parse('1.0.0-1')->lessThan(Version::parse('1.0.0-alpha')); // true

// More identifiers = higher precedence (when equal prefix)
Version::parse('1.0.0-alpha')->lessThan(Version::parse('1.0.0-alpha.1')); // true
```

## Incrementing Versions

All increment operations return a new `Version` instance (immutable):

```php
$version = Version::parse('1.2.3-alpha+build');

// Increment major (resets minor, patch, pre-release, build)
$version->incrementMajor(); // 2.0.0

// Increment minor (resets patch, pre-release, build)
$version->incrementMinor(); // 1.3.0

// Increment patch (resets pre-release, build)
$version->incrementPatch(); // 1.2.4

// Increment pre-release
$version->incrementPreRelease(); // 1.2.3-alpha.1

// Pre-release increment examples
Version::parse('1.0.0')->incrementPreRelease();        // 1.0.0-0
Version::parse('1.0.0-alpha')->incrementPreRelease();  // 1.0.0-alpha.1
Version::parse('1.0.0-alpha.1')->incrementPreRelease(); // 1.0.0-alpha.2
Version::parse('1.0.0-0')->incrementPreRelease();      // 1.0.0-1
```

## Modifying Pre-release and Build

```php
$version = Version::parse('1.2.3');

// Add/change pre-release
$version->withPreRelease('alpha.1');     // 1.2.3-alpha.1
$version->withPreRelease(['rc', '1']);   // 1.2.3-rc.1

// Remove pre-release
Version::parse('1.2.3-alpha')->withoutPreRelease(); // 1.2.3

// Add/change build metadata
$version->withBuild('build.456');        // 1.2.3+build.456
$version->withBuild(['sha', 'abc123']); // 1.2.3+sha.abc123

// Remove build metadata
Version::parse('1.2.3+build')->withoutBuild(); // 1.2.3
```

## Detecting Differences

```php
$v1 = Version::parse('1.2.3');
$v2 = Version::parse('2.0.0');

$v1->diff($v2); // 'major'

// All difference types
Version::parse('1.0.0')->diff(Version::parse('2.0.0')); // 'major'
Version::parse('1.0.0')->diff(Version::parse('1.1.0')); // 'minor'
Version::parse('1.0.0')->diff(Version::parse('1.0.1')); // 'patch'
Version::parse('1.0.0')->diff(Version::parse('1.0.0-alpha')); // 'prerelease'
Version::parse('1.0.0+a')->diff(Version::parse('1.0.0+b')); // 'build'
Version::parse('1.0.0')->diff(Version::parse('1.0.0')); // null (identical)
```

## JSON Serialization

```php
$version = Version::parse('1.2.3-alpha+build');

json_encode($version);
// {
//   "major": 1,
//   "minor": 2,
//   "patch": 3,
//   "prerelease": "alpha",
//   "build": "build",
//   "full": "1.2.3-alpha+build"
// }
```

## String Conversion

```php
$version = Version::parse('1.2.3-alpha+build');

(string) $version;    // "1.2.3-alpha+build"
$version->core();     // "1.2.3"
```
