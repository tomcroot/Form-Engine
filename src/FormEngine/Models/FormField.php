<?php

namespace Tapp\FormEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Form Field model
 *
 * Stores metadata about individual fields in a form
 *
 * @property int $id
 * @property int $form_id
 * @property string $name Field name (used in schema)
 * @property string $label Display label
 * @property string $type Field type (text, textarea, select, etc.)
 * @property string|null $help_text Help text for the field
 * @property array|null $options Options for select/radio fields (JSON)
 * @property bool $required Whether field is required
 * @property array|null $validation Validation rules (JSON)
 * @property int $order Display order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'name',
        'label',
        'type',
        'help_text',
        'options',
        'required',
        'validation',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'validation' => 'array',
            'order' => 'integer',
        ];
    }

    /**
     * Get the form this field belongs to
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Check if field is required
     */
    public function isRequired(): bool
    {
        return $this->required === true;
    }

    /**
     * Get validation rules for this field
     */
    public function getValidationRules(): array
    {
        $rules = $this->validation ?? [];

        if ($this->isRequired()) {
            array_unshift($rules, 'required');
        }

        return $rules;
    }
}
