<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\SemVer\Constraint;
use Cline\SemVer\Enums\Operator;
use Cline\SemVer\Version;

describe('Constraint Parsing', function (): void {
    test('parses exact version', function (): void {
        $constraint = Constraint::parse('1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeFalse();
    });

    test('parses version with equals operator', function (): void {
        $constraint = Constraint::parse('=1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeFalse();
    });

    test('parses not equal operator', function (): void {
        $constraint = Constraint::parse('!=1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeTrue();
    });

    test('parses less than operator', function (): void {
        $constraint = Constraint::parse('<1.2.3');

        expect($constraint->isSatisfiedBy('1.2.2'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.3'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeFalse();
    });

    test('parses less than or equal operator', function (): void {
        $constraint = Constraint::parse('<=1.2.3');

        expect($constraint->isSatisfiedBy('1.2.2'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeFalse();
    });

    test('parses greater than operator', function (): void {
        $constraint = Constraint::parse('>1.2.3');

        expect($constraint->isSatisfiedBy('1.2.2'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.3'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeTrue();
    });

    test('parses greater than or equal operator', function (): void {
        $constraint = Constraint::parse('>=1.2.3');

        expect($constraint->isSatisfiedBy('1.2.2'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeTrue();
    });

    test('parses wildcard constraint', function (): void {
        $constraint = Constraint::parse('*');

        expect($constraint->isSatisfiedBy('0.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('999.999.999'))->toBeTrue();
    });

    test('parses empty constraint as wildcard', function (): void {
        $constraint = Constraint::parse('');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
    });
});

describe('Tilde Ranges', function (): void {
    test('tilde allows patch updates', function (): void {
        // ~1.2.3 := >=1.2.3 <1.3.0
        $constraint = Constraint::parse('~1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.3.0'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.2'))->toBeFalse();
    });

    test('tilde with partial version', function (): void {
        // ~1.2 := >=1.2.0 <1.3.0
        $constraint = Constraint::parse('~1.2');

        expect($constraint->isSatisfiedBy('1.2.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.3.0'))->toBeFalse();
    });
});

describe('Caret Ranges', function (): void {
    test('caret allows minor and patch updates for stable', function (): void {
        // ^1.2.3 := >=1.2.3 <2.0.0
        $constraint = Constraint::parse('^1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.3.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.99.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.2.2'))->toBeFalse();
    });

    test('caret for 0.x allows patch updates', function (): void {
        // ^0.2.3 := >=0.2.3 <0.3.0
        $constraint = Constraint::parse('^0.2.3');

        expect($constraint->isSatisfiedBy('0.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.2.4'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.3.0'))->toBeFalse();
    });

    test('caret for 0.0.x is exact', function (): void {
        // ^0.0.3 := >=0.0.3 <0.0.4
        $constraint = Constraint::parse('^0.0.3');

        expect($constraint->isSatisfiedBy('0.0.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.0.4'))->toBeFalse();
        expect($constraint->isSatisfiedBy('0.0.2'))->toBeFalse();
    });
});

describe('Wildcard Ranges', function (): void {
    test('major.x matches any minor/patch', function (): void {
        $constraint = Constraint::parse('1.x');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.99.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeFalse();
        expect($constraint->isSatisfiedBy('0.99.99'))->toBeFalse();
    });

    test('major.minor.x matches any patch', function (): void {
        $constraint = Constraint::parse('1.2.x');

        expect($constraint->isSatisfiedBy('1.2.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.3.0'))->toBeFalse();
        expect($constraint->isSatisfiedBy('1.1.99'))->toBeFalse();
    });

    test('major.* is equivalent to major.x', function (): void {
        $constraint = Constraint::parse('1.*');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.99.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeFalse();
    });
});

describe('Hyphen Ranges', function (): void {
    test('hyphen range is inclusive', function (): void {
        // 1.2.3 - 2.3.4 := >=1.2.3 <=2.3.4
        $constraint = Constraint::parse('1.2.3 - 2.3.4');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.3.4'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.2'))->toBeFalse();
        expect($constraint->isSatisfiedBy('2.3.5'))->toBeFalse();
    });

    test('hyphen with partial upper bound', function (): void {
        // 1.2.3 - 2.3 := >=1.2.3 <2.4.0
        $constraint = Constraint::parse('1.2.3 - 2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.3.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.4.0'))->toBeFalse();
    });
});

describe('AND Constraints', function (): void {
    test('space-separated constraints are AND', function (): void {
        $constraint = Constraint::parse('>=1.0.0 <2.0.0');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.5.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.99.99'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.99.99'))->toBeFalse();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeFalse();
    });

    test('comma-separated constraints are AND', function (): void {
        $constraint = Constraint::parse('>=1.0.0, <2.0.0');

        expect($constraint->isSatisfiedBy('1.5.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.99.99'))->toBeFalse();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeFalse();
    });
});

describe('OR Constraints', function (): void {
    test('double pipe is OR', function (): void {
        $constraint = Constraint::parse('1.0.0 || 2.0.0');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.5.0'))->toBeFalse();
    });

    test('complex OR constraint', function (): void {
        $constraint = Constraint::parse('^1.0.0 || ^2.0.0');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.5.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.5.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('3.0.0'))->toBeFalse();
    });
});

describe('Constraint Factory Methods', function (): void {
    test('exact creates exact constraint', function (): void {
        $constraint = Constraint::exact('1.2.3');

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.2.4'))->toBeFalse();
    });

    test('exact accepts Version object', function (): void {
        $version = Version::parse('1.2.3');
        $constraint = Constraint::exact($version);

        expect($constraint->isSatisfiedBy('1.2.3'))->toBeTrue();
    });

    test('withOperator creates constraint with operator', function (): void {
        $constraint = Constraint::withOperator(Operator::GreaterThanOrEqual, '1.0.0');

        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('2.0.0'))->toBeTrue();
        expect($constraint->isSatisfiedBy('0.99.99'))->toBeFalse();
    });
});

describe('Constraint Combination', function (): void {
    test('and combines constraints', function (): void {
        $c1 = Constraint::parse('>=1.0.0');
        $c2 = Constraint::parse('<2.0.0');
        $combined = $c1->and($c2);

        expect($combined->isSatisfiedBy('1.5.0'))->toBeTrue();
        expect($combined->isSatisfiedBy('0.99.99'))->toBeFalse();
        expect($combined->isSatisfiedBy('2.0.0'))->toBeFalse();
    });

    test('or combines constraints', function (): void {
        $c1 = Constraint::exact('1.0.0');
        $c2 = Constraint::exact('2.0.0');
        $combined = $c1->or($c2);

        expect($combined->isSatisfiedBy('1.0.0'))->toBeTrue();
        expect($combined->isSatisfiedBy('2.0.0'))->toBeTrue();
        expect($combined->isSatisfiedBy('1.5.0'))->toBeFalse();
    });
});

describe('Pre-release Handling', function (): void {
    test('pre-release satisfies constraint with pre-release', function (): void {
        $constraint = Constraint::parse('>=1.0.0-alpha');

        expect($constraint->isSatisfiedBy('1.0.0-alpha'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.0.0-beta'))->toBeTrue();
        expect($constraint->isSatisfiedBy('1.0.0'))->toBeTrue();
    });
});

describe('Constraint String Representation', function (): void {
    test('toString returns original constraint', function (): void {
        $constraint = Constraint::parse('>=1.0.0 <2.0.0');

        expect((string) $constraint)->toBe('>=1.0.0 <2.0.0');
    });
});
