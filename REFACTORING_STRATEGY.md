# Form-Engine Refactoring Strategy

**Goal:** Evolve from "Filament Form Builder clone" → "Generic Data Collection Engine" while maintaining backward compatibility.

---

## 🎯 Refactoring Principles

1. **Backward Compatibility First**
   - All existing `Tapp\FilamentFormBuilder\*` namespaces remain functional
   - Filament plugins continue working unchanged
   - Existing models (`FilamentForm`, etc.) still available

2. **Parallel Namespace**
   - New `Tapp\FormEngine\*` namespace alongside existing code
   - Forms can be used via either namespace
   - Gradual migration path for users

3. **Generic Core → Filament UI Layer**
   - Core: polymorphic forms, submissions, schema-driven rendering
   - UI: Filament admin panel, plugins, Livewire components

---

## 📊 Architecture Layers

```
┌─────────────────────────────────────┐
│  Filament Admin Panel & Plugins     │  (FilamentFormBuilder)
├─────────────────────────────────────┤
│  Filament Form Rendering (Livewire) │  (FilamentForm*, Filament/*)
├─────────────────────────────────────┤
│  Form-Engine Core                   │  (FormEngine namespace)
│  • Polymorphic submissions          │
│  • Schema-driven rendering          │
│  • File uploads                     │
├─────────────────────────────────────┤
│  Database                           │
│  • forms, form_submissions          │
│  • form_fields (with display_as)    │
└─────────────────────────────────────┘
```

---

## 🔄 Phase Breakdown

### Phase 1: Core Refactor (Now)
**Goal:** Extract generic logic, prepare parallel namespaces

- [ ] Create new migration files for polymorphic `form_submissions`
- [ ] Add `form_type` column (`data_entry` | `file_upload`)
- [ ] Introduce `Tapp\FormEngine\*` namespace aliases
- [ ] Create `FormEngine\Models\Form` (generic facade)
- [ ] Create `FormEngine\Models\Submission` (polymorphic)

**Outcome:** Core tables support polymorphic usage; Filament layer still works

---

### Phase 2: Remove ERP Assumptions (Next)
**Goal:** Strip ERP-specific references

**Search & Remove:**
- [ ] `ActivityForm` references → use generic `Form`
- [ ] `ProjectForm` → use generic `Form`
- [ ] Any hardcoded `participation` logic
- [ ] Activity/Project-specific field types

**What stays:**
- [ ] `FilamentForm` model (for backward compat)
- [ ] Existing migrations (add migration to add new columns, don't break old ones)

---

### Phase 3: Clean API Surface (Phase 2)
**Goal:** Expose simple public API

```php
// In Tapp\FormEngine namespace:

Form::create(['name' => 'Registration'])        // Generic form creation
Form::find($id)->publish()                      // Publish form
Form::find($id)->renderSchema()                 // Get schema for rendering
Form::find($id)->submitData($data, $context)    // Submit with polymorphic context

Submission::forModel($model, $form)->query()   // Get submissions for a model instance
```

---

### Phase 4: Rendering Layer Refactor (Phase 3)
**Goal:** Decouple rendering from admin

- [ ] Separate admin rendering (Filament)
- [ ] Add generic schema renderer
- [ ] Support `form_type`: `data_entry` vs `file_upload`
- [ ] Create rendering traits for reuse

---

### Phase 5: ERP Integration Helpers (Phase 4)
**Goal:** Show how SIF (ERP) connects to Form-Engine

In **sif** app (not in package):
- [ ] Create `FormAssignment` model
- [ ] Create service to link forms to projects/activities
- [ ] Create service to render/submit forms in project context

---

## 📋 Task Checklist

### Immediate Actions

- [x] Update `composer.json` package name → `tapp/form-engine`
- [x] Update `README.md` with fork installation
- [x] Add parallel autoload: `Tapp\FormEngine\*`
- [ ] Create new migrations directory structure
- [ ] Design database schema v2 (polymorphic)

### Migration Files to Create

```
database/
  migrations/
    2024_XX_XX_create_form_engine_tables.php     (new)
    2024_XX_XX_add_polymorphic_to_submissions.php (new)
    2024_XX_XX_add_form_type_to_forms.php        (new)
```

### New Models to Create

```
src/
  FormEngine/
    Models/
      Form.php                  (generic, replaces FilamentForm conceptually)
      Submission.php            (polymorphic)
      Field.php                 (generic, replaces FilamentFormField)
    Services/
      FormRenderer.php
      SubmissionHandler.php
    Traits/
      HasFormSubmissions.php    (add to any model)
```

---

## 🔗 Backward Compatibility Map

| Old Code | New Code | Status |
|----------|----------|--------|
| `Tapp\FilamentFormBuilder\Models\FilamentForm` | `Tapp\FormEngine\Models\Form` | Alias provided |
| `FilamentFormBuilderPlugin` | Still works | No change |
| Admin CRUD | Still works | No change |
| `FilamentForm` relationships | Enhanced | Polymorphic ready |

---

## 🚀 Migration Example (Future SIF Use)

```php
// In Projects module
use Tapp\FormEngine\Models\Form;
use Tapp\FormEngine\Traits\HasFormSubmissions;

class Project extends Model {
    use HasFormSubmissions;  // New trait
}

// Usage:
$form = Form::findBySlug('project-reporting');
$submission = $project->submitForm($form, $data);  // Polymorphic
```

---

## ⚠️ What NOT to Touch

- Filament admin UI (keep working as-is)
- Plugin system (keep working as-is)
- Existing migrations (only extend, don't modify)
- `FilamentForm` model name (keep for compat)

---

## 📝 Success Criteria

1. ✅ Package installs from fork (this PR)
2. ✅ Existing apps still work with no changes
3. ✅ New code can use `FormEngine\*` for generic usage
4. ✅ Polymorphic submissions work for any model
5. ✅ Form-Engine can be used outside Filament context
