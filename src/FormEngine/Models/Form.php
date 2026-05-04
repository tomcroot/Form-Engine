<?php

namespace Tomcroot\FormEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Generic Form model for Form-Engine
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $form_type data_entry | file_upload
 * @property array|null $schema Field definitions (JSON)
 * @property string|null $template_file Template file path for file_upload forms
 * @property int $version Form version
 * @property string $status draft | published
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'form_type',
        'schema',
        'template_file',
        'version',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->name);
            }
        });
    }

    /**
     * Get all submissions for this form
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Get all fields for this form
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    /**
     * Check if form is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Publish this form
     */
    public function publish(): void
    {
        $this->update(['status' => 'published']);
    }

    /**
     * Draft this form
     */
    public function draft(): void
    {
        $this->update(['status' => 'draft']);
    }

    /**
     * Check if form is data entry type
     */
    public function isDataEntry(): bool
    {
        return $this->form_type === 'data_entry';
    }

    /**
     * Check if form is file upload type
     */
    public function isFileUpload(): bool
    {
        return $this->form_type === 'file_upload';
    }

    /**
     * Get submission count for this form
     */
    public function submissionCount(): int
    {
        return $this->submissions()->count();
    }
}
