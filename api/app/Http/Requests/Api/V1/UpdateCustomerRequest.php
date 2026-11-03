<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
        /** @var Customer|null $customer */
        $customer = $this->route('customer');

        return [
            'document_type' => ['sometimes', 'nullable', Rule::in(['CC', 'CE', 'TI', 'NIT', 'PA'])],
            'document_number' => [
                'sometimes', 'required', 'string', 'max:40',
                Rule::unique('customers', 'document_number')
                    ->where('organization_id', $organizationId)
                    ->ignore($customer?->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:160'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
