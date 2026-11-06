<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->organization() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_string($items)) {
            $decoded = json_decode($items, true);

            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->organization()?->id;

        return [
            'customer_name' => ['required', 'string', 'max:160'],
            'document_type' => ['nullable', Rule::in(['CC', 'CE', 'TI', 'NIT', 'PA'])],
            'document_number' => ['required', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', Rule::in(['transferencia', 'nequi', 'daviplata', 'efectivo'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt.required' => 'Adjunta el comprobante de pago.',
            'receipt.mimes' => 'El comprobante debe ser una imagen o un PDF.',
            'receipt.max' => 'El comprobante no puede superar 10 MB.',
            'items.required' => 'Agrega al menos un producto al pedido.',
        ];
    }

    public function organization(): ?Organization
    {
        return Organization::query()
            ->where('slug', (string) $this->route('slug'))
            ->where('is_active', true)
            ->first();
    }
}
