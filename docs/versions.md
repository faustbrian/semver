---
title: Version Operations
description: Parse, compare, and manipulate semantic versions with the immutable Version class.
---

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
