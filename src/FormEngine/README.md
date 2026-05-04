# Form-Engine Documentation

## Overview

The Form-Engine is a generic, reusable data collection engine built into the Form-Engine package. It enables polymorphic form submissions across any Laravel model, completely decoupled from domain logic.

**Key Feature:** Forms can be submitted by *any* model (Activity, Project, Participation, User, etc.) without coupling the package to specific ERP concepts.

---

## Database Tables

### `forms`
Stores form definitions.

```
id              - Primary key
name            - Form name
slug            - URL-friendly slug (auto-generated)
form_type       - 'data_entry' or 'file_upload'
schema          - JSON field definitions (for data_entry)
template_file   - Template file path (for file_upload)
version         - Form version
status          - 'draft' or 'published'
created_at      - Timestamp
updated_at      - Timestamp
```

### `form_fields`
Stores metadata about individual fields (optional, for UI builders).

```
id              - Primary key
form_id         - Foreign key to forms
name            - Field key
label           - Display label
type            - 'text', 'textarea', 'select', 'number', 'date', 'file', etc.
help_text       - Help/instruction text
options         - JSON (for select/radio/checkbox)
required        - Boolean
validation      - JSON validation rules
order           - Display order
created_at      - Timestamp
updated_at      - Timestamp
```

### `form_submissions`
Stores form submissions (polymorphic).

```
id              - Primary key
form_id         - Foreign key to forms
subject_type    - Model class (e.g., 'Activity', 'Project', 'Participation')
subject_id      - ID of the subject model
data            - JSON captured field values (for data_entry)
file_path       - File path (for file_upload)
submitted_by    - User ID who submitted
created_at      - Timestamp
updated_at      - Timestamp
```

---

## Models

### `Tapp\FormEngine\Models\Form`

Generic form model with helper methods.

```php
use Tapp\FormEngine\Models\Form;

$form = Form::create([
    'name' => 'Registration',
    'slug' => 'registration',
    'form_type' => 'data_entry',
    'schema' => [...],
    'status' => 'draft',
]);

// Publish form
$form->publish();

// Check status
if ($form->isPublished()) { ... }

// Get submissions
$form->submissions()->get();

// Get field count
$form->submissionCount();
```

### `Tapp\FormEngine\Models\FormSubmission`

Polymorphic submission model.

```php
use Tapp\FormEngine\Models\FormSubmission;

// Get all submissions
$submissions = FormSubmission::where('form_id', $formId)->get();

// Access polymorphic subject
$submission->subject;  // Activity, Project, etc.
$submission->subject()->getModel();

// Get data
$data = $submission->data;  // JSON array
$fieldValue = $submission->getFieldData('name');

// Access submitter
$submission->submitter;  // User who submitted
```

### `Tapp\FormEngine\Models\FormField`

Field metadata (optional, for builders).

```php
use Tapp\FormEngine\Models\FormField;

$field = FormField::create([
    'form_id' => $form->id,
    'name' => 'email',
    'label' => 'Email Address',
    'type' => 'email',
    'required' => true,
    'order' => 1,
]);

// Get validation rules
$field->getValidationRules();  // ['required', 'email']
```

---

## Trait: `HasFormSubmissions`

Add to any model to enable form submissions.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tapp\FormEngine\Traits\HasFormSubmissions;

class Activity extends Model
{
    use HasFormSubmissions;
}
```

Now you can:

```php
$activity = Activity::find(1);

// Submit a form
$submission = $activity->submitForm($form, [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Submit file
$submission = $activity->submitFormFile($form, '/path/to/file.pdf', auth()->id());

// Get submissions
$activity->formSubmissions()->get();

// Get submissions for specific form
$activity->getFormSubmissions($formId);

// Get latest submission
$latest = $activity->getLatestFormSubmission($formId);
```

---

## Usage Examples

### 1. Create a Data Entry Form

```php
use Tapp\FormEngine\Models\Form;

$form = Form::create([
    'name' => 'Beneficiary Registration',
    'slug' => 'beneficiary-registration',
    'form_type' => 'data_entry',
    'schema' => [
        ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'status' => 'draft',
]);

$form->publish();
```

### 2. Submit Form from Activity

```php
$activity = Activity::find(1);

$submission = $activity->submitForm($form, [
    'first_name' => 'John',
    'email' => 'john@example.com',
    'phone' => '555-1234',
    'notes' => 'Important notes',
], auth()->id());
```

### 3. Submit File Upload Form

```php
$project = Project::find(1);

// Assuming file is uploaded to storage
$filePath = $request->file('document')->store('submissions');

$submission = $project->submitFormFile(
    $reportingForm,
    $filePath,
    auth()->id()
);
```

### 4. Query Submissions

```php
// Get all submissions for a form
$submissions = Form::find($formId)->submissions()->get();

// Get all submissions from an activity
$submissions = Activity::find($activityId)->formSubmissions()->get();

// Get submissions from specific date range
$recent = FormSubmission::where('form_id', $formId)
    ->whereBetween('created_at', [$start, $end])
    ->get();

// Get submissions by submitter
$userSubmissions = FormSubmission::where('submitted_by', auth()->id())->get();
```

---

## Service Layer (Phase 3)

*Coming in next phase:*

```php
use Tapp\FormEngine\Services\FormEngineService;

$service = new FormEngineService();

// Render form for a subject
$html = $service->render($form, $subject);

// Validate & submit
$submission = $service->submit($form, $subject, $data, auth()->id());

// Resolve form by context & purpose
$form = $service->resolveAssignment($context, $purpose);
```

---

## Integration with ERP

The Form-Engine is **completely generic**. ERP-specific integration (FormAssignment, FormResolver, flows) happens in the ERP app, not in this package.

**In SIF ERP:**
- `FormAssignment` model (connects forms to projects/activities)
- `FormResolver` service (resolves form by context & purpose)
- Registration flow (Activity → Form → Participation)
- Reporting flow (Project → Form → Submission)
- Evidence flow (Participation → Form → Submission)

---

## Best Practices

1. **Keep the package generic** - No project/activity references in FormEngine
2. **Use traits** - Add `HasFormSubmissions` to any model that needs it
3. **Polymorphic first** - Design around `subject_type` and `subject_id`
4. **Domain logic in ERP** - Flows, assignments, resolution stay in app
5. **Validate on submit** - Use field validation rules when submitting

---

## API Reference

### Form Methods

- `create(array $data)` - Create form
- `publish()` - Publish form
- `draft()` - Draft form
- `isPublished(): bool` - Check if published
- `isDataEntry(): bool` - Check form type
- `isFileUpload(): bool` - Check form type
- `submissions()` - Get HasMany submissions
- `fields()` - Get HasMany fields
- `submissionCount(): int` - Get submission count

### FormSubmission Methods

- `form()` - BelongsTo Form
- `subject()` - MorphTo subject
- `submitter()` - BelongsTo User
- `getFieldData(string): mixed` - Get specific field value
- `isDataEntry(): bool` - Check submission type
- `isFileUpload(): bool` - Check submission type

### HasFormSubmissions Trait Methods

- `formSubmissions()` - MorphMany submissions
- `submitForm($form, $data, $userId): FormSubmission` - Submit data form
- `submitFormFile($form, $path, $userId): FormSubmission` - Submit file form
- `getFormSubmissions($formId)` - Get specific form submissions
- `getLatestFormSubmission($formId = null)` - Get latest submission

---

## Migration Publishing

```bash
php artisan vendor:publish --tag="filament-form-builder-migrations"
php artisan migrate
```
