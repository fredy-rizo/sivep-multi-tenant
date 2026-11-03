<?php

namespace App\Http\Requests\Api\V1;

use App\Models\EventType;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function organizationId(): int
    {
        return (int) app(TenantContext::class)->id();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->organizationId();

        return [
            'event_type_id' => [
                'required',
                Rule::exists('event_types', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'event_date' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'vaccine_id' => [
                'nullable',
                Rule::exists('vaccines', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'dose_ml' => ['nullable', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:80'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'treatment' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.to_lot_id' => [
                'nullable',
                'integer',
                Rule::exists('lots', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $eventType = EventType::find($this->input('event_type_id'));

            if (! $eventType) {
                return;
            }

            if ($eventType->requires_weight && ! $this->filled('weight_kg')) {
                $validator->errors()->add('weight_kg', 'Este tipo de evento requiere peso.');
            }

            if ($eventType->requires_vaccine && ! $this->filled('vaccine_id')) {
                $validator->errors()->add('vaccine_id', 'Este tipo de evento requiere una vacuna.');
            }
        });
    }
}
