@props([
    'name' => 'password',
    'label' => 'Mot de passe',
    'id' => null,
    'autocomplete' => 'current-password',
])
@php($fieldId = $id ?? $name)
<div class="field-group">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <div class="field-icon has-toggle">
        <i class="bi bi-lock" aria-hidden="true"></i>
        <input id="{{ $fieldId }}" class="field" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" required>
        <button type="button" class="field-toggle" data-toggle-password="{{ $fieldId }}" aria-label="Afficher le mot de passe">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>
</div>
