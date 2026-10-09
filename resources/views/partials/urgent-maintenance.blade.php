@if (($urgentMaintenance ?? collect())->isNotEmpty())
    <div class="modal fade" id="urgentMaintenance" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="urgentMaintenanceLabel">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content urgent-modal">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="urgentMaintenanceLabel">Maintenance urgente</h2>
                </div>
                <div class="modal-body">
                    <p>Cette alerte s'affiche chez le bailleur et le gérant à chaque visite, jusqu'à la solution ou jusqu'à l'acceptation que c'est bien géré.</p>
                    @foreach ($urgentMaintenance as $alert)
                        <article class="card mb-2">
                            <p class="fw-semibold mb-1">{{ $alert->title }}</p>
                            <p class="text-muted small mb-1">{{ $alert->unit?->name }}@if ($alert->tenant) · {{ $alert->tenant->name }}@endif</p>
                            <p class="small mb-1">Déclarée le {{ $alert->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} par {{ $alert->reporter?->name ?? 'Inconnu' }}</p>
                            <p class="mb-2">{{ $alert->description }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-primary" href="{{ route('office.maintenance.show', $alert) }}">Traiter</a>
                                <form method="POST" action="{{ route('office.maintenance.accept', $alert) }}">
                                    @csrf
                                    <button class="btn btn-ghost">C'est bien géré</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const node = document.getElementById('urgentMaintenance');
            if (node && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(node).show();
            }
        });
    </script>
@endif
