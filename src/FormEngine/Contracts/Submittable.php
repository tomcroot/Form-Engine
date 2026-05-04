<?php

namespace Tomcroot\FormEngine\Contracts;

use Tomcroot\FormEngine\Models\FormSubmission;

/**
 * Contract for models that can submit forms
 *
 * Implement this in models that use HasFormSubmissions trait
 */
interface Submittable
{
    /**
     * Get all form submissions for this model
     */
    public function formSubmissions();

    /**
     * Submit a form
     */
    public function submitForm($form, array $data, ?int $submittedBy = null): FormSubmission;
}
