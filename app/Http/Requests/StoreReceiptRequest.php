<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** F-08: penerimaan dari SPBE. */
class StoreReceiptRequest extends TransactionRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'ref_document' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:1000'],
            ...$this->itemRules(),
        ];
    }

    protected function qtyFields(): array
    {
        return ['qty_full_in', 'qty_empty_out'];
    }

    protected function outgoing(): array
    {
        return ['field' => 'qty_empty_out', 'condition' => 'kosong', 'label' => 'Kosong dikirim'];
    }

    public function headerData(): array
    {
        return $this->safe()->only(['date', 'ref_document', 'supplier_id', 'vehicle_id', 'note']);
    }
}
