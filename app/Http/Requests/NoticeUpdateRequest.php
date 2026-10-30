<?php

namespace App\Http\Requests;

class NoticeUpdateRequest extends NoticeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        // Legacy edit forms send the existing attachment URL when no new file is selected.
        // The controller only reads UploadedFile, never stores the submitted URL.
        if (!$this->hasFile('attachment_file') && is_string($this->input('attachment_file'))) {
            $rules['attachment_file'] = 'nullable|string|max:2048';
        }
        return $rules;
    }
}
