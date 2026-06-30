<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorizedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Authorized::class);
    }

    public function rules(): array
    {
        return [
            'addUuid' => 'required|unique:authorizeds,uuid',
            'addUserId' => 'required|integer|exists:md_users,id|unique:authorizeds,user_id',
            'addGroup' => 'required|in:merah,biru',
            'addQuota' => 'required|numeric',
            'addIsActive' => 'boolean',
        ];
    }
}
