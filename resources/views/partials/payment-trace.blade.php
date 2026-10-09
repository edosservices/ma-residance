<ol class="payment-trace mt-3">
    <li>
        <strong>Preuve envoyée</strong>
        <span>{{ $payment->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $payment->declarer?->name ?? 'Déclarant' }}</span>
    </li>
    @if ($payment->proof_path)
        <li>
            <strong>Justificatif</strong>
            <span><a href="{{ file_url($payment->proof_path) }}">Voir la preuve</a></span>
        </li>
    @endif
    @if ($payment->status->value === 'approved')
        <li>
            <strong>Approuvé</strong>
            <span>
                {{ $payment->reviewed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                · {{ $payment->reviewer?->name ?? 'Bailleur' }}
                · compté dans les encaissements à cette date
            </span>
        </li>
    @elseif ($payment->status->value === 'rejected')
        <li>
            <strong>Rejeté</strong>
            <span>{{ $payment->reviewed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $payment->rejection_reason }}</span>
        </li>
    @else
        <li>
            <strong>À confirmer</strong>
            <span>Pas encore approuvé, donc pas encore dans les encaissements.</span>
        </li>
    @endif
</ol>
