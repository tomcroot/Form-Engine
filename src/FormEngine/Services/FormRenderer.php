<?php

namespace Tapp\FormEngine\Services;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Tapp\FormEngine\Models\Form;

/**
 * FormRenderer - Convert form schema to Filament components
 *
 * Supports rendering data_entry forms as Filament form components
 * Can be extended for other rendering targets (Vue, React, etc.)
 */
class FormRenderer
{
    /**
     * Render a data entry form to Filament components
     *
     * @param Form $form
     * @return array Filament form components
     */
    public function renderDataEntry(Form $form): array
    {
        if (! $form->schema) {
            return [];
        }

        return array_map(fn (array $field) => $this->renderField($field), $form->schema);
    }

    /**
     * Render a file upload form
     *
     * @param Form $form
     * @return array Filament components
     */
    public function renderFileUpload(Form $form): array
    {
        $components = [];

        if ($form->template_file) {
            $components[] = TextInput::make('template_url')
                ->label('Template')
                ->default($form->template_file)
                ->disabled();
        }

        $components[] = FileUpload::make('file')
            ->label('Upload Form')
            ->required();

        return $components;
    }

    /**
     * Render individual field
     *
     * @param array $field Field definition
     * @return mixed Filament component
     */
    protected function renderField(array $field)
    {
        $name = $field['name'];
        $label = $field['label'] ?? null;
        $required = (bool) ($field['required'] ?? false);
        $helpText = $field['help_text'] ?? null;

        $component = match ($field['type'] ?? 'text') {
            'textarea' => Textarea::make($name)->rows(3),
            'select' => Select::make($name)->options($field['options'] ?? []),
            'number' => TextInput::make($name)->numeric(),
            'date' => DatePicker::make($name),
            'file' => FileUpload::make($name),
            'toggle' => Toggle::make($name),
            'email' => TextInput::make($name)->email(),
            'url' => TextInput::make($name)->url(),
            'tel' => TextInput::make($name)->tel(),
            default => TextInput::make($name),
        };

        $component->label($label);

        if ($required) {
            $component->required();
        }

        if ($helpText) {
            $component->helperText($helpText);
        }

        return $component;
    }
}
