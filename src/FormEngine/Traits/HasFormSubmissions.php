<?php

namespace Tomcroot\FormEngine\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tomcroot\FormEngine\Models\FormSubmission;

/**
 * Trait to add form submission capabilities to any model
 *
 * Usage:
 * class Activity extends Model {
 *     use HasFormSubmissions;
 * }
 *
 * Then use:
 * $activity->formSubmissions()
 * $activity->submitForm($form, $data)
 */
trait HasFormSubmissions
{
    /**
     * Get all form submissions for this model
     */
    public function formSubmissions(): MorphMany
    {
        return $this->morphMany(FormSubmission::class, 'subject');
    }

    /**
     * Submit a form for this model
     *
     * @param mixed $form Form model instance
     * @param array $data Submission data
     * @param int|null $submittedBy User ID of submitter
     * @return FormSubmission
     */
    public function submitForm($form, array $data, ?int $submittedBy = null): FormSubmission
    {
        return $this->formSubmissions()->create([
            'form_id' => $form->id ?? $form,
            'data' => $data,
            'submitted_by' => $submittedBy,
        ]);
    }

    /**
     * Submit a file upload form for this model
     *
     * @param mixed $form Form model instance
     * @param string $filePath Path to uploaded file
     * @param int|null $submittedBy User ID of submitter
     * @return FormSubmission
     */
    public function submitFormFile($form, string $filePath, ?int $submittedBy = null): FormSubmission
    {
        return $this->formSubmissions()->create([
            'form_id' => $form->id ?? $form,
            'file_path' => $filePath,
            'submitted_by' => $submittedBy,
        ]);
    }

    /**
     * Get form submissions of a specific type
     *
     * @param int|string $formId Form ID or slug
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFormSubmissions($formId)
    {
        return $this->formSubmissions()
            ->where('form_id', $formId)
            ->get();
    }

    /**
     * Get latest form submission
     *
     * @param int|string|null $formId Optional form filter
     * @return FormSubmission|null
     */
    public function getLatestFormSubmission($formId = null)
    {
        $query = $this->formSubmissions();

        if ($formId) {
            $query->where('form_id', $formId);
        }

        return $query->latest()->first();
    }
}
