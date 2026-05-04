<?php

namespace Tapp\FormEngine\Services;

use Tapp\FormEngine\Models\Form;
use Tapp\FormEngine\Models\FormSubmission;

/**
 * FormEngineService - Main service for form operations
 *
 * Provides:
 * - Rendering forms to UI
 * - Submitting forms with validation
 * - Querying submissions
 */
class FormEngineService
{
    protected FormRenderer $renderer;

    public function __construct(FormRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new FormRenderer();
    }

    /**
     * Render a form for a subject
     *
     * @param Form $form
     * @param mixed $subject Optional model instance for context
     * @return array Rendered components
     */
    public function render(Form $form, $subject = null): array
    {
        if ($form->isDataEntry()) {
            return $this->renderer->renderDataEntry($form);
        }

        return $this->renderer->renderFileUpload($form);
    }

    /**
     * Submit form data
     *
     * @param Form $form
     * @param mixed $subject Model instance (Activity, Project, etc.)
     * @param array $data Submission data
     * @param int|null $submittedBy User ID
     * @return FormSubmission
     * @throws \Exception
     */
    public function submit(Form $form, $subject, array $data, ?int $submittedBy = null): FormSubmission
    {
        if (! $form->isPublished()) {
            throw new \Exception('Form is not published');
        }

        // Validate data against schema
        $validated = $this->validate($form, $data);

        // Use subject's submission method if available
        if (method_exists($subject, 'submitForm')) {
            return $subject->submitForm($form, $validated, $submittedBy);
        }

        // Fallback: create directly
        return FormSubmission::create([
            'form_id' => $form->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'data' => $validated,
            'submitted_by' => $submittedBy,
        ]);
    }

    /**
     * Submit file upload form
     *
     * @param Form $form
     * @param mixed $subject
     * @param string $filePath
     * @param int|null $submittedBy
     * @return FormSubmission
     * @throws \Exception
     */
    public function submitFile(Form $form, $subject, string $filePath, ?int $submittedBy = null): FormSubmission
    {
        if (! $form->isPublished()) {
            throw new \Exception('Form is not published');
        }

        if (! $form->isFileUpload()) {
            throw new \Exception('Form does not accept file uploads');
        }

        // Use subject's submission method if available
        if (method_exists($subject, 'submitFormFile')) {
            return $subject->submitFormFile($form, $filePath, $submittedBy);
        }

        // Fallback: create directly
        return FormSubmission::create([
            'form_id' => $form->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'file_path' => $filePath,
            'submitted_by' => $submittedBy,
        ]);
    }

    /**
     * Validate form data against schema
     *
     * @param Form $form
     * @param array $data
     * @return array Validated data
     */
    public function validate(Form $form, array $data): array
    {
        if (! $form->schema) {
            return $data;
        }

        $rules = $this->buildValidationRules($form->schema);

        return validator()->validate($data, $rules);
    }

    /**
     * Build Laravel validation rules from form schema
     *
     * @param array $schema
     * @return array
     */
    protected function buildValidationRules(array $schema): array
    {
        $rules = [];

        foreach ($schema as $field) {
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            }

            $type = $field['type'] ?? 'string';

            switch ($type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'number':
                case 'integer':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    break;
            }

            if (! empty($fieldRules)) {
                $rules[$field['name']] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Get all submissions for a form
     *
     * @param Form $form
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubmissions(Form $form)
    {
        return $form->submissions()->get();
    }

    /**
     * Get submissions from a specific subject
     *
     * @param Form $form
     * @param mixed $subject
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubmissionsFor(Form $form, $subject)
    {
        return FormSubmission::where([
            'form_id' => $form->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
        ])->get();
    }
}
