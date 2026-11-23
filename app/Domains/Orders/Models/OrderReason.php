<?php

namespace App\Domains\Orders\Models;

use App\Domains\Orders\Enums\OrderReasonType;
use Database\Factories\OrderReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A reason picked when cancelling an order or when it is not delivered.
 */
#[Fillable(['type', 'name', 'is_active', 'sort_order'])]
#[UseFactory(OrderReasonFactory::class)]
class OrderReason extends Model
{
    /** @use HasFactory<OrderReasonFactory> */
    use HasFactory, HasTranslations;

    /**
     * @var list<string>
     */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderReasonType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, OrderReasonType $type): void
    {
        $query->where('type', $type);
    }
}
