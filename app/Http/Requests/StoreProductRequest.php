<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->productRules('required');
    }

    /**
     * @param  'required'|'sometimes'  $presence
     */
    protected function productRules(string $presence): array
    {
        return [
            'name' => [$presence, 'string', 'max:255'],
            'description' => [$presence, 'string'],
            'price' => [$presence, 'numeric', 'min:0', 'max:9999999999.99'],
            'category' => [$presence, 'string', 'max:255'],
            'status' => [$presence, Rule::enum(ProductStatus::class)],
        ];
    }
}
