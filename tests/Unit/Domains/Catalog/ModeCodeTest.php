<?php

namespace Tests\Unit\Domains\Catalog;

use App\Domains\Catalog\Enums\ModeCode;
use PHPUnit\Framework\TestCase;

class ModeCodeTest extends TestCase
{
    public function test_each_mode_needs_exactly_one_extra_setting(): void
    {
        $this->assertSame(
            [
                'long_distance' => [false, false, true],
                'zone' => [false, true, false],
                'area' => [true, false, false],
            ],
            collect(ModeCode::cases())
                ->mapWithKeys(fn (ModeCode $code): array => [$code->value => [$code->requiresArea(), $code->requiresRadius(), $code->requiresDestination()]])
                ->all(),
        );
    }

    public function test_values_lists_every_code(): void
    {
        $this->assertSame(['long_distance', 'zone', 'area'], ModeCode::values());
    }
}
