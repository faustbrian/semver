<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\SemVer\Enums;

/**
 * Version constraint operators.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum Operator: string
{
    case Equal = '=';
    case NotEqual = '!=';
    case LessThan = '<';
    case LessThanOrEqual = '<=';
    case GreaterThan = '>';
    case GreaterThanOrEqual = '>=';
    case Tilde = '~';
    case Caret = '^';

    /**
     * Get the operator from a string.
     *
     * @param string $operator The operator string
     */
    public static function fromString(string $operator): ?self
    {
        return match ($operator) {
            '', '=' => self::Equal,
            '!=' => self::NotEqual,
            '<' => self::LessThan,
            '<=' => self::LessThanOrEqual,
            '>' => self::GreaterThan,
            '>=' => self::GreaterThanOrEqual,
            '~' => self::Tilde,
            '^' => self::Caret,
            default => null,
        };
    }
}
