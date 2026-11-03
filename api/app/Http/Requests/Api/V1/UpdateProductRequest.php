<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'sometimes', 'required', 'string', 'max:60',
                Rule::unique('products', 'sku')
                    ->where('organization_id', $organizationId)
                    ->ignore($product?->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
