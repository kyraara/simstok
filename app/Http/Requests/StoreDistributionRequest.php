<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** F-09: distribusi ke pangkalan. */
class StoreDistributionRequest extends TransactionRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'outlet_id' => ['required', Rule::exists('outlets', 'id')->where('is_active', true)],
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:1000'],
            ...$this->itemRules(),
        ];
    }

    protected function qtyFields(): array
    {
        return ['qty_full_out', 'qty_empty_in'];
    }

    protected function outgoing(): array
    {
        return ['field' => 'qty_full_out', 'condition' => 'isi', 'label' => 'Isi dikirim'];
    }

    public function headerData(): array
    {
        return $this->safe()->only(['date', 'outlet_id', 'vehicle_id', 'note']);
    }
}
