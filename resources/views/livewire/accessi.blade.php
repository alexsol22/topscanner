<div class="space-y-5">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-800">Log accessi utenti</h1>
        <p class="mt-1 text-sm text-slate-500">Elenco degli accessi effettuati dagli utenti.</p>
    </div>

    <section aria-label="Registro accessi" class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-sky-100">
                <thead class="bg-sky-50/60">
                    <tr>
                        <th class="th">ID utente</th>
                        <th class="th">Nome</th>
                        <th class="th">Cognome</th>
                        <th class="th">Tipo utente</th>
                        <th class="th">Utente</th>
                        <th class="th">Email</th>
                        <th class="th">Data e ora</th>
                        <th class="th">Indirizzo IP</th>
                        <th class="th">Browser / dispositivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sky-50">
                    @forelse ($accessi as $accesso)
                        <tr wire:key="accesso-{{ $accesso->id }}" class="hover:bg-sky-50/40">
                            <td class="td">{{ $accesso->utente?->id ?? $accesso->id_user ?? '—' }}</td>
                            <td class="td">{{ $accesso->utente?->nome ?? '—' }}</td>
                            <td class="td">{{ $accesso->utente?->cognome ?? '—' }}</td>
                            <td class="td">{{ $accesso->utente?->ruolo?->label() ?? '—' }}</td>
                            <td class="td">{{ $accesso->utente?->user ?? '—' }}</td>
                            <td class="td">{{ $accesso->utente?->email ?? '—' }}</td>
                            <td class="td whitespace-nowrap">{{ $accesso->data_view?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="td">{{ $accesso->addr ?? '—' }}</td>
                            <td class="td max-w-xs truncate" title="{{ $accesso->agent ?? '' }}">{{ $accesso->agent ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="td text-center text-slate-500">Nessun accesso registrato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $accessi->links() }}
</div>
