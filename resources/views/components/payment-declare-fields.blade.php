@props([
    'prefix' => 'payment',
    'amountPlaceholder' => 'Ex. 150.00',
    'amountHint' => 'Écrivez le montant comme dans l\'exemple : 150.00.',
    'notePlaceholder' => 'Ex. Loyer d\'octobre, reçu en espèces au bureau',
    'proofRequired' => false,
    'methods' => ['cash' => 'Espèces', 'transfer' => 'Transfert', 'other' => 'Autre'],
])
<div class="field-group mb-2">
    <label for="{{ $prefix }}-amount">Montant</label>
    <input id="{{ $prefix }}-amount" class="field" name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="{{ $amountPlaceholder }}" required>
    <span class="field-hint">{{ $amountHint }}</span>
</div>
<div class="field-group mb-2">
    <label for="{{ $prefix }}-method">Moyen</label>
    <select id="{{ $prefix }}-method" class="field" name="method" required>
        <option value="" disabled @selected(old('method') === null)>Ex. Espèces</option>
        @foreach ($methods as $value => $label)
            <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="field-hint">Ex. Espèces si l'argent est remis en main propre, Transfert s'il est envoyé.</span>
</div>
<div class="field-group mb-2">
    <label for="{{ $prefix }}-note">Note</label>
    <input id="{{ $prefix }}-note" class="field" name="note" value="{{ old('note') }}" maxlength="500" placeholder="{{ $notePlaceholder }}">
    <span class="field-hint">{{ $notePlaceholder }}.</span>
</div>
<div class="field-group mb-3">
    <label for="{{ $prefix }}-proof">Preuve</label>
    <label class="field file-example" for="{{ $prefix }}-proof">
        <input id="{{ $prefix }}-proof" type="file" name="proof" accept="image/*,.pdf" @required($proofRequired)>
        <span data-file-label>Ex. photo du reçu ou capture du transfert</span>
    </label>
    <span class="field-hint">Ex. photo du reçu ou capture du transfert.{{ $proofRequired ? ' Obligatoire pour la confirmation.' : ' Facultatif pour un encaissement au bureau.' }}</span>
</div>
<script>
    document.querySelectorAll('.file-example input[type="file"]').forEach((input) => {
        if (input.dataset.bound) {
            return;
        }
        input.dataset.bound = '1';
        input.addEventListener('change', () => {
            const label = input.parentElement.querySelector('[data-file-label]');
            const name = input.files && input.files[0] ? input.files[0].name : '';
            if (!label) {
                return;
            }
            label.textContent = name || 'Ex. photo du reçu ou capture du transfert';
            input.parentElement.classList.toggle('is-filled', name !== '');
        });
    });
</script>
