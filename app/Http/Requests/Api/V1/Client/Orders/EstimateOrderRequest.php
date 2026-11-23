<?php

namespace App\Http\Requests\Api\V1\Client\Orders;

use App\Domains\Orders\Models\Order;
use App\Http\Requests\Concerns\ValidatesOrderRoute;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EstimateOrderRequest extends FormRequest
{
    use ValidatesOrderRoute;

    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('create', Order::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->orderRouteRules();
    }
}
