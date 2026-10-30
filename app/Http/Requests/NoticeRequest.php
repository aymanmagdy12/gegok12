<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && (int) $this->user()->usergroup_id === 3 && (bool) $this->user()->school_id;
    }

    public function rules(): array
    {
        $school = $this->user()->school_id;
        $period = app(\App\Services\AcademyContext::class)->period((int) $school);
        return [
            'type' => ['required', Rule::in(['school', 'class', 'teacher'])],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'publish_date' => 'required|date|before_or_equal:expire_date',
            'expire_date' => 'required|date|after_or_equal:publish_date',
            'standardLink_id' => ['exclude_unless:type,class', 'required', 'integer', Rule::exists('standards_link', 'id')->where('school_id', $school)->where('academic_year_id', $period->id)->where('status', 1)->whereNull('deleted_at')],
            'bg_id' => ['nullable', 'integer', Rule::exists('background_images', 'id')->where('school_id', $school)->whereNull('deleted_at')],
            'attachment_file' => 'nullable|file|mimes:pdf|max:8092',
        ];
    }
}
