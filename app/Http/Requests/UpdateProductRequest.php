<?php

namespace App\Http\Requests;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * PUT: semua field wajib. PATCH: hanya field yang dikirim yang divalidasi.
     */
    public function rules(): array
    {
        return $this->productRules($this->isMethod('PATCH') ? 'sometimes' : 'required');
    }
}
