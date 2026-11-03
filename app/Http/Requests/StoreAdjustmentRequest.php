<?php

namespace App\Http\Requests;

use App\Enums\AdjustmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** F-10: penyesuaian stok (rusak, hilang, koreksi) dengan alasan wajib. */
class StoreAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        $manual = array_map(fn ($t) => $t->value, AdjustmentType::manual());

        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::in($manual)],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cylinder_type_id' => ['required', 'integer', 'exists:cylinder_types,id'],
            'items.*.condition' => ['required', Rule::in(['isi', 'kosong', 'rusak'])],
            'items.*.direction' => ['nullable', Rule::in(['plus', 'minus'])],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('type') !== AdjustmentType::Rusak->value) {
                return;
            }
            foreach ($this->input('items', []) as $i => $row) {
                if (($row['condition'] ?? null) === 'rusak') {
                    $validator->errors()->add("items.{$i}.condition", 'Untuk tabung rusak, pilih kondisi asal isi atau kosong.');
                }
            }
        }];
    }
}
