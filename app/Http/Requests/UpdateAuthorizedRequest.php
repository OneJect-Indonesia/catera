<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorizedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', \App\Models\Authorized::class);
    }

    public function rules(): array
    {
        return [
            'editGroup' => 'required|in:merah,biru',
            'editQuota' => 'required|numeric',
            'editIsActive' => 'boolean',
        ];
    }
}
