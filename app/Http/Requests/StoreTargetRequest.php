<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['ip', 'cidr', 'range'])],
            'ip' => ['nullable', 'string', 'max:45'],
            'cidr' => ['nullable', 'string', 'max:50'],
            'start_ip' => ['nullable', 'string', 'max:45'],
            'end_ip' => ['nullable', 'string', 'max:45'],
            'ports' => ['nullable', 'string', 'max:120', 'regex:/^[0-9,\-\s]*$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');

            if ($type === 'ip' && ! filled($this->input('ip'))) {
                $validator->errors()->add('ip', 'Tek IP için IP adresi zorunludur.');
            }

            if ($type === 'cidr' && ! filled($this->input('cidr'))) {
                $validator->errors()->add('cidr', 'CIDR alanı zorunludur.');
            }

            if ($type === 'range' && (! filled($this->input('start_ip')) || ! filled($this->input('end_ip')))) {
                $validator->errors()->add('start_ip', 'IP aralığı için başlangıç ve bitiş zorunludur.');
            }
        });
    }
}
