<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'target_id' => [
                'nullable',
                'integer',
                Rule::exists('targets', 'id')->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
            'ip' => ['nullable', 'string', 'max:45'],
            'cidr' => ['nullable', 'string', 'max:50'],
            'start_ip' => ['nullable', 'string', 'max:45'],
            'end_ip' => ['nullable', 'string', 'max:45'],
            'ports' => ['nullable', 'string', 'max:120', 'regex:/^[0-9,\-\s]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ports.regex' => 'Port alanı yalnızca sayı, virgül ve tire içerebilir.',
            'target_id.exists' => 'Seçilen hedef bulunamadı.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasTarget = filled($this->input('target_id'));
            $hasIp = filled($this->input('ip'));
            $hasCidr = filled($this->input('cidr'));
            $hasRange = filled($this->input('start_ip')) && filled($this->input('end_ip'));

            if (! $hasTarget && ! $hasIp && ! $hasCidr && ! $hasRange) {
                $validator->errors()->add(
                    'ip',
                    'Tek IP, CIDR, IP aralığı veya kayıtlı hedef alanlarından birini doldurun.'
                );
            }

            if (filled($this->input('start_ip')) xor filled($this->input('end_ip'))) {
                $validator->errors()->add(
                    'start_ip',
                    'IP aralığı için başlangıç ve bitiş IP birlikte girilmelidir.'
                );
            }
        });
    }
}
