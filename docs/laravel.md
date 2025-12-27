---
title: Laravel Integration
description: Facade, Eloquent casting, and dependency injection for Laravel applications.
---

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
