<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChangeClientRequestStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_request_id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        $base = [
            'client_request_id' => ['required', 'uuid', Rule::exists('client_requests', 'id')],
        ];

        if ($this->filled('process_step_id') || $this->filled('process_step_action')) {
            return array_merge($base, [
                'process_step_id'     => ['required', 'uuid', 'exists:process_steps,id'],
                'process_step_action' => ['required', 'string', 'in:approve,reject'],
            ]);
        }

        return array_merge($base, [
            'status_client_request' => 'required|string|in:pending,accepted,rejected,draft',
            'reject_cause'          => 'nullable|string|required_if:status_client_request,rejected',
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('process_step_id')) {
                return;
            }

            $ok = DB::table('process_steps')
                ->join('processes', 'process_steps.process_id', '=', 'processes.id')
                ->where('process_steps.id', $this->input('process_step_id'))
                ->where('processes.client_request_id', $this->route('id'))
                ->where('processes.type', 'client_request')
                ->exists();

            if (! $ok) {
                $validator->errors()->add(
                    'process_step_id',
                    'The selected process step does not belong to this client request.',
                );
            }
        });
    }
}
