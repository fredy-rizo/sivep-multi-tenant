<?php

namespace App\Http\Requests\Api\V1;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'sale_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['sometimes', Rule::in(['pagada', 'pendiente'])],
            'payment_method' => ['sometimes', Rule::in(['efectivo', 'transferencia', 'nequi', 'daviplata', 'credito'])],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.animal_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.description' => ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $hasProduct = ! empty($item['product_id']);
                $hasAnimal = ! empty($item['animal_id']);

                if ($hasProduct === $hasAnimal) {
                    $validator->errors()->add(
                        "items.{$index}",
                        'Cada línea debe incluir un producto o un animal, pero no ambos.',
                    );
                }

                if ($hasAnimal && empty($item['unit_price'])) {
                    $validator->errors()->add(
                        "items.{$index}.unit_price",
                        'Indica el precio del animal.',
                    );
                }
            }
        });
    }
}
