<?php

namespace App\Http\Requests\Api\V1;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = (int) app(TenantContext::class)->id();

        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'type' => ['required', Rule::in(['entrada', 'salida', 'ajuste'])],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:160'],
            'movement_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
