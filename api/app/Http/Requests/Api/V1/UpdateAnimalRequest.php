<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Animal;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnimalRequest extends FormRequest
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
        /** @var Animal|null $animal */
        $animal = $this->route('animal');
        $organizationId = $this->organizationId();

        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:60',
                Rule::unique('animals', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($animal?->id),
            ],
            'tag' => ['sometimes', 'nullable', 'string', 'max:60'],
            'rfid' => [
                'sometimes', 'nullable', 'string', 'max:80',
                Rule::unique('animals', 'rfid')
                    ->where('organization_id', $organizationId)
                    ->ignore($animal?->id),
            ],
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'species_id' => [
                'sometimes', 'required',
                Rule::exists('species', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'breed_id' => [
                'sometimes', 'nullable',
                Rule::exists('breeds', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'lot_id' => [
                'sometimes', 'nullable',
                Rule::exists('lots', 'id')->where('organization_id', $organizationId),
            ],
            'sex' => ['sometimes', Rule::in(['macho', 'hembra'])],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'birth_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:2000'],
            'dam_id' => [
                'sometimes', 'nullable',
                Rule::exists('animals', 'id')->where('organization_id', $organizationId),
            ],
            'sire_id' => [
                'sometimes', 'nullable',
                Rule::exists('animals', 'id')->where('organization_id', $organizationId),
            ],
            'status' => [
                'sometimes',
                Rule::in(['activo', 'preñada', 'lactando', 'vendido', 'fallecido', 'trasladado']),
            ],
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
