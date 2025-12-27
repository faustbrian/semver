---
title: Getting Started
description: Full-featured Semantic Versioning 2.0.0 implementation for PHP with parsing, comparison, constraints, and version operations.
---

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

- [Version Operations](/semver/versions/) - Parsing, comparison, and manipulation
- [Constraints](/semver/constraints/) - Tilde, caret, ranges, and wildcards
- [Collections](/semver/collections/) - Filtering and sorting version lists
- [Laravel Integration](/semver/laravel/) - Facade, casting, and service container
