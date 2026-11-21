<?php

namespace Tests\Unit\Domains\Accounts;

use App\Domains\Accounts\Data\PhoneNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validNumbers(): array
    {
        return [
            'local 010' => ['01012345678', '+201012345678'],
            'local 011' => ['01112345678', '+201112345678'],
            'local 012' => ['01212345678', '+201212345678'],
            'local 015' => ['01512345678', '+201512345678'],
            'without leading zero' => ['1012345678', '+201012345678'],
            'country code' => ['201012345678', '+201012345678'],
            'plus country code' => ['+201012345678', '+201012345678'],
            'double zero country code' => ['00201012345678', '+201012345678'],
            'spaces and dashes' => ['010 1234-5678', '+201012345678'],
            'brackets and dots' => ['(010) 1234.5678', '+201012345678'],
            'arabic-indic digits' => ['٠١٠١٢٣٤٥٦٧٨', '+201012345678'],
            'persian digits' => ['۰۱۰۱۲۳۴۵۶۷۸', '+201012345678'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_it_normalizes_egyptian_mobile_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::from($input)->value);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'empty' => [''],
            'too short' => ['0101234567'],
            'too long' => ['010123456789'],
            'unknown prefix 013' => ['01312345678'],
            'landline' => ['0223456789'],
            'other country' => ['+971501234567'],
            'letters' => ['0101234abcd'],
            'null' => [null],
            'array' => [['01012345678']],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_invalid_numbers(mixed $input): void
    {
        $this->assertNull(PhoneNumber::tryFrom($input));
    }

    public function test_from_throws_on_an_invalid_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PhoneNumber::from('12345');
    }

    public function test_it_casts_to_the_stored_format(): void
    {
        $this->assertSame('+201012345678', (string) PhoneNumber::from('01012345678'));
    }
}
