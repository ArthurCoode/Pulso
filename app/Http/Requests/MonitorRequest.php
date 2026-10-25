<?php

namespace App\Http\Requests;

use App\Exceptions\UnsafeUrlException;
use App\Services\UrlGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A proteção de acesso é feita pelo middleware AdminAccess.
        return true;
    }

    /**
     * Checkbox desmarcado não é enviado pelo navegador. Convertemos para
     * true/false aqui, senão desmarcar "público" nunca teria efeito.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_public' => $this->boolean('is_public')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'url' => [
                'required',
                'url:http,https',
                'max:2000',
                // Mesma proteção SSRF do checker, aplicada já no cadastro:
                // o erro aparece no formulário em vez de só no primeiro check.
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        app(UrlGuard::class)->assertSafe((string) $value);
                    } catch (UnsafeUrlException $e) {
                        $fail($e->getMessage());
                    }
                },
            ],
            'method' => ['required', Rule::in(['GET', 'HEAD'])],
            'expected_status' => ['required', 'integer', 'between:100,599'],
            // Máximo 25 s: o Job tem limite de 30 s e precisa de folga.
            'timeout' => ['required', 'integer', 'between:1,25'],
            'interval_minutes' => ['required', 'integer', 'between:1,1440'],
            // HEAD não devolve corpo, então não há onde procurar a palavra-chave.
            'keyword' => ['nullable', 'string', 'max:255', 'prohibited_if:method,HEAD'],
            'is_public' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'url' => 'URL',
            'method' => 'método',
            'expected_status' => 'status esperado',
            'timeout' => 'timeout',
            'interval_minutes' => 'intervalo',
            'keyword' => 'palavra-chave',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'url' => 'Informe uma URL válida começando com http:// ou https://.',
            'max' => 'O campo :attribute excede o tamanho máximo permitido.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'between' => 'O campo :attribute deve estar entre :min e :max.',
            'in' => 'O valor escolhido para :attribute não é válido.',
            'prohibited_if' => 'A palavra-chave só pode ser usada com o método GET.',
        ];
    }
}