<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class EgyptianNationalIdNumber implements ValidationRule
{
    /**
     * The first digit is the birth century (2 for the 1900s, 3 for the 2000s).
     */
    private const array CENTURIES = ['2' => 1900, '3' => 2000];

    /**
     * 14 digits, starting with the birth date as CYYMMDD; the date must be real and not in the future.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^([23])(\d{2})(\d{2})(\d{2})\d{7}$/', $value, $parts) !== 1 || ! $this->isPastBirthDate($parts)) {
            $fail('validation.egyptian_national_id_number')->translate();
        }
    }

    /**
     * @param  array<int, string>  $parts  the century digit, YY, MM and DD
     */
    private function isPastBirthDate(array $parts): bool
    {
        [, $century, $year, $month, $day] = $parts;
        $year = self::CENTURIES[$century] + (int) $year;

        return checkdate((int) $month, (int) $day, $year)
            && Carbon::create($year, (int) $month, (int) $day)->lte(now()->startOfDay());
    }
}
