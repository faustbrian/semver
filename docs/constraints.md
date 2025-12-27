---
title: Version Constraints
description: Match versions against constraints using tilde, caret, ranges, wildcards, and logical operators.
---

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
