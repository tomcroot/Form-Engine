<?php

namespace Tapp\FormEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Polymorphic Form Submission model
 *
 * Stores form submissions for any model (Activity, Project, Participation, etc.)
 *
 * @property int $id
 * @property int $form_id
 * @property string $subject_type Model class of the subject
 * @property int $subject_id ID of the subject
 * @property array|null $data Captured field values (JSON) for data_entry forms
 * @property string|null $file_path Uploaded file path for file_upload forms
 * @property int|null $submitted_by User ID who submitted
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'subject_type',
        'subject_id',
        'data',
        'file_path',
        'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Get the form this submission belongs to
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the subject (polymorphic) - the model that submitted the form
     *
     * Could be Activity, Project, Participation, or any model using HasFormSubmissions
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who submitted this form
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', AuthUser::class),
            'submitted_by'
        );
    }

    /**
     * Get data for a specific field
     */
    public function getFieldData(string $fieldName): mixed
    {
        return $this->data[$fieldName] ?? null;
    }

    /**
     * Check if this is a data entry submission
     */
    public function isDataEntry(): bool
    {
        return $this->form->isDataEntry() && $this->data !== null;
    }

    /**
     * Check if this is a file upload submission
     */
    public function isFileUpload(): bool
    {
        return $this->form->isFileUpload() && $this->file_path !== null;
    }
}
