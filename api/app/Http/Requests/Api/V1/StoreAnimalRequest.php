<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Breed;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalRequest extends FormRequest
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
            'code' => [
                'required', 'string', 'max:60',
                Rule::unique('animals', 'code')->where('organization_id', $organizationId),
            ],
            'tag' => ['nullable', 'string', 'max:60'],
            'rfid' => [
                'nullable', 'string', 'max:80',
                Rule::unique('animals', 'rfid')->where('organization_id', $organizationId),
            ],
            'name' => ['nullable', 'string', 'max:120'],
            'species_id' => [
                'required',
                Rule::exists('species', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'breed_id' => [
                'nullable',
                Rule::exists('breeds', 'id')->where(function ($query) use ($organizationId) {
                    $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
                }),
            ],
            'lot_id' => [
                'nullable',
                Rule::exists('lots', 'id')->where('organization_id', $organizationId),
            ],
            'sex' => ['required', Rule::in(['macho', 'hembra'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'dam_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where('organization_id', $organizationId),
            ],
            'sire_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where('organization_id', $organizationId),
            ],
            'status' => [
                'sometimes',
                Rule::in(['activo', 'preñada', 'lactando', 'vendido', 'fallecido', 'trasladado']),
            ],
            'photo_path' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            if (! empty($data['dam_id']) && (int) $data['dam_id'] === (int) $data['sire_id']) {
                $validator->errors()->add('sire_id', 'La madre y el padre no pueden ser el mismo animal.');
            }

            if (! empty($data['breed_id']) && ! empty($data['species_id'])) {
                $breed = Breed::find($data['breed_id']);
                if ($breed && (int) $breed->species_id !== (int) $data['species_id']) {
                    $validator->errors()->add('breed_id', 'La raza no pertenece a la especie seleccionada.');
                }
            }
        });
    }
}
