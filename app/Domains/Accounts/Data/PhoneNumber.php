<?php

namespace App\Domains\Accounts\Data;

use InvalidArgumentException;
use Stringable;

/**
 * An Egyptian mobile number, always stored as +201XXXXXXXXX.
 */
final readonly class PhoneNumber implements Stringable
{
    private const string COUNTRY_CODE = '+20';

    private function __construct(public string $value) {}

    /**
     * Accepts 01XXXXXXXXX, 1XXXXXXXXX, 201XXXXXXXXX, +201XXXXXXXXX and 00201XXXXXXXXX,
     * with spaces, dashes, dots or brackets, and Arabic-Indic or Persian digits.
     */
    public static function tryFrom(mixed $input): ?self
    {
        if (! is_string($input) && ! is_int($input)) {
            return null;
        }

        $digits = self::westernDigits((string) $input);

        $digits = preg_replace('/[\s\-.()]/u', '', $digits) ?? '';

        if (str_starts_with($digits, '+')) {
            $digits = substr($digits, 1);
        } elseif (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/^(?:20|0)?(1[0125]\d{8})$/', $digits, $matches) !== 1) {
            return null;
        }

        return new self(self::COUNTRY_CODE.$matches[1]);
    }

    /**
     * Converts Arabic-Indic and Persian digits to 0-9.
     */
    public static function westernDigits(string $input): string
    {
        return strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    public static function from(mixed $input): self
    {
        return self::tryFrom($input) ?? throw new InvalidArgumentException('Invalid Egyptian mobile number.');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
