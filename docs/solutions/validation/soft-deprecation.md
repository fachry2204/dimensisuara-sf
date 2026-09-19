# Soft Deprecated System Options in Laravel & Alpine.js

## Context & Problem
When synchronizing dropdown options (like genres, versions, roles) from an external config, some old options are deprecated. We cannot hard-delete these options because historical records refer to them. However, we must prevent new records from selecting them.

## Solution

### 1. Database Column
Add an `is_active` column to the reference tables:
```php
Schema::table('system_options', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->index();
});
```

### 2. Context-Aware FormRequest Validation
Pass the model instance to the `rules()` method of the Request class, and use a custom validation closure:
```php
public function rules(?Model $model = null): array
{
    return [
        'option_id' => [
            'required',
            'integer',
            function ($attribute, $value, $fail) use ($model) {
                $item = Option::find($value);
                if (!$item) {
                    $fail('The selected option is invalid.');
                    return;
                }
                // Allow if option is active OR matches the model's currently saved option
                if ($item->is_active || ($model && $model->option_id == $value)) {
                    return;
                }
                $fail('The selected option is a legacy choice and is no longer available.');
            }
        ]
    ];
}
```

### 3. Dynamic Dropdown Rendering (Alpine.js & Blade)
Conditionally hide inactive options in the front-end unless they are currently selected.
```html
<select name="option_id" x-model="state.option_id">
    @foreach(\App\Models\Option::all() as $opt)
        @if($opt->is_active)
            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
        @else
            <option value="{{ $opt->id }}" x-show="state.option_id == {{ $opt->id }}">{{ $opt->name }} (Legacy)</option>
        @endif
    @endforeach
</select>
```

### 4. Reactive AJAX Endpoint Check
If options are loaded dynamically via AJAX (like subgenres filtering based on genre), pass the currently selected value as a query parameter (e.g. `?current=X`) to the AJAX endpoint, so it is temporarily appended to the active list:
```php
public function get_suboptions(Request $request, $parentId)
{
    $currentId = $request->query('current');
    return Option::where('parent_id', $parentId)
        ->where(function ($query) use ($currentId) {
            $query->where('is_active', true);
            if ($currentId) {
                $query->orWhere('id', $currentId);
            }
        })
        ->get();
}
```
