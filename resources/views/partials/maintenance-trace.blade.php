<ol class="payment-trace mt-3">
    <li>
        <strong>Déclaration</strong>
        <span>
            {{ $item->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
            · {{ $item->reporter?->name ?? 'Déclarant' }}
            · {{ $item->unit?->name }}
            @if ($item->tenant) · {{ $item->tenant->name }} @endif
            · {{ $item->urgency->label() }}
        </span>
    </li>
    @foreach ($item->updates as $update)
        <li>
            <strong>{{ $update->status_to ? (\App\Enums\MaintenanceStatus::tryFrom($update->status_to)?->label() ?? 'Étape') : 'Note' }}</strong>
            <span>
                {{ $update->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                · {{ $update->user?->name ?? 'Équipe' }}
                @if ($update->note) · {{ $update->note }} @endif
            </span>
        </li>
    @endforeach
    @if ($item->handled_at)
        <li>
            <strong>Bien géré</strong>
            <span>
                {{ $item->handled_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                · {{ $item->handler?->name ?? 'Bailleur' }}
                @if ($item->handled_note) · {{ $item->handled_note }} @endif
            </span>
        </li>
    @endif
</ol>
