<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlidebookDesignRequest extends FormRequest
{
    /**
     * Only an authorized owner may change design on an editable revision.
     */
    public function authorize(): bool
    {
        $slidebook = $this->route('slidebook');

        return $slidebook
            && in_array($slidebook->status, ['draft', 'review'], true)
            && (bool) $this->user()?->can('update', $slidebook);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preset' => ['required', 'string', 'in:indigo-dark,modern-tech,academic-blue,creative-education,fresh-learning,minimalist'],
            'audience' => ['nullable', 'string', 'in:general,smp,sma-smk'],
        ];
    }
}
