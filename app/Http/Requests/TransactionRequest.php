<?php

namespace App\Http\Requests;

use App\Models\CylinderType;
use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Dasar form penerimaan & distribusi: satu baris per jenis tabung dengan dua kolom jumlah.
 * Baris yang kedua jumlahnya 0 diabaikan. Jumlah keluar dicek terhadap stok (F-11).
 */
abstract class TransactionRequest extends FormRequest
{
    /** @return array{0: string, 1: string} nama kolom [masuk/keluar pertama, kedua] */
    abstract protected function qtyFields(): array;

    /** @return array{field: string, condition: string, label: string} kolom yang mengurangi stok dan kondisinya */
    abstract protected function outgoing(): array;

    protected function itemRules(): array
    {
        [$a, $b] = $this->qtyFields();

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.cylinder_type_id' => ['required', 'integer', 'distinct', 'exists:cylinder_types,id'],
            "items.*.{$a}" => ['nullable', 'integer', 'min:0', 'max:100000'],
            "items.*.{$b}" => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (empty($this->lineItems())) {
                $validator->errors()->add('items', 'Isi minimal satu jumlah tabung.');

                return;
            }

            ['field' => $field, 'condition' => $condition, 'label' => $label] = $this->outgoing();
            $stocks = Stock::where('condition', $condition)->pluck('quantity', 'cylinder_type_id');
            $names = CylinderType::pluck('name', 'id');

            foreach ($this->input('items', []) as $index => $row) {
                $qty = (int) ($row[$field] ?? 0);
                $available = (int) ($stocks[$row['cylinder_type_id']] ?? 0);
                if ($qty > $available) {
                    $validator->errors()->add(
                        "items.{$index}.{$field}",
                        sprintf('%s %s melebihi stok (tersedia %s).', $label, $names[$row['cylinder_type_id']] ?? '', number_format($available, 0, ',', '.')),
                    );
                }
            }
        }];
    }

    /** Baris item yang jumlahnya tidak nol, dengan nilai kosong diubah menjadi 0. */
    public function lineItems(): array
    {
        [$a, $b] = $this->qtyFields();

        return collect($this->input('items', []))
            ->map(fn ($row) => [
                'cylinder_type_id' => (int) $row['cylinder_type_id'],
                $a => (int) ($row[$a] ?? 0),
                $b => (int) ($row[$b] ?? 0),
            ])
            ->filter(fn ($row) => $row[$a] > 0 || $row[$b] > 0)
            ->values()
            ->all();
    }
}
