<?php

namespace App\Http\Requests\Api\V1\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDatabaseConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', Rule::in(['mysql', 'pgsql', 'sqlite', 'sqlsrv'])],
            'host' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Connection name is required.',
            'driver.required' => 'Database driver is required.',
            'driver.in' => 'Driver must be mysql, pgsql, sqlite, or sqlsrv.',
            'database.required' => 'Database name is required.',
        ];
    }
}
