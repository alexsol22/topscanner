<main class="grid min-h-dvh bg-[#f1f8fb] lg:grid-cols-2">
    <section class="relative flex min-h-dvh flex-col justify-center px-6 py-12 sm:px-12 lg:px-10 xl:px-16" aria-labelledby="login-title">
        <div class="mx-auto w-full max-w-md rounded-3xl bg-white p-7 shadow-[0_24px_70px_rgba(21,43,56,0.08)] ring-1 ring-sky-100 sm:p-9">
            <a href="{{ url('/') }}" class="mb-14 inline-flex items-center gap-3" aria-label="Omnia Control">
                <img src="{{ asset('brand/omnia-control.svg') }}?v=2" alt="" class="h-11 w-11">
                <span class="leading-none">
                    <span class="block text-sm font-bold tracking-[0.18em] text-slate-800">OMNIA</span>
                    <span class="mt-1.5 block text-[10px] font-semibold tracking-[0.25em] text-slate-500">CONTROL</span>
                </span>
            </a>

            <p class="mb-2 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-sky-700">
                <span class="h-1.5 w-1.5 rounded-full bg-[#d9784e]"></span>
                Area operativa
            </p>
            <h1 id="login-title" class="text-3xl font-bold tracking-tight text-slate-800">Bentornato</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Turni, sedi e presenze: tutto riparte da qui.</p>

            <form wire:submit="accedi" class="mt-8 space-y-5">
                <div>
                    <label class="label" for="email">Utente</label>
                    <input id="email" type="text" wire:model="email" class="input !rounded-lg !px-3.5 !py-3" autocomplete="username">
                    @error('email') <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password">Password</label>
                    <input id="password" type="password" wire:model="password" class="input !rounded-lg !px-3.5 !py-3" autocomplete="current-password">
                    @error('password') <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" wire:model="remember" class="rounded border-sky-300 text-sky-600 focus:ring-sky-300">
                    Ricordami
                </label>

                <button class="btn-primary w-full !rounded-lg !bg-sky-600 hover:!bg-sky-700" wire:loading.attr="disabled">
                    <span wire:loading.remove>Accedi</span>
                    <span wire:loading>Accesso in corso…</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('password.recupero') }}" wire:navigate class="font-medium text-sky-700 hover:text-sky-800 hover:underline">Password dimenticata?</a>
            </p>
        </div>

        <p class="absolute bottom-6 left-6 text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400 sm:left-12 lg:left-16 xl:left-24">Omnia Control <span class="px-1.5 text-[#d9784e]">·</span> Accesso riservato</p>
    </section>

    <aside class="relative hidden min-h-dvh overflow-hidden px-8 py-10 text-[#152b38] lg:block xl:px-14 xl:py-12" aria-label="Roadmap illustrata del servizio">
        <svg aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full text-sky-200/50" viewBox="0 0 760 1000" preserveAspectRatio="xMidYMid slice" fill="none">
            <path d="M0 164h220l42 42h155M0 276h152l54-52h211M0 642h180l62-62h166M0 760h236l52-48h120M760 144h-184l-48 48h-190M760 286h-154l-48-46h-220M760 618h-190l-48 48h-184M760 752h-225l-50-48h-155" stroke="currentColor" stroke-width="2"/>
            <path d="M94 0v1000M170 0v1000M650 0v1000M730 0v1000" stroke="currentColor"/>
        </svg>

        <div class="relative mx-auto flex min-h-[calc(100dvh-6rem)] w-full max-w-xl flex-col">
            <div class="mt-8">
                <h2 class="mt-3 text-3xl font-semibold leading-tight tracking-tight text-[#152b38] xl:text-4xl">Il servizio, passo dopo passo.</h2>
                <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">Persone, sedi e attività connesse in un unico percorso di lavoro.</p>
            </div>

            <div class="relative mt-7 flex-1">
                <div class="absolute bottom-10 left-[15px] top-10 border-l border-dashed border-[#d9784e]/50"></div>
                <ol class="relative flex h-full flex-col justify-between gap-3" aria-label="Fasi del servizio">
                    <li class="grid grid-cols-[32px_122px_minmax(0,1fr)] items-center gap-3">
                        <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border border-[#d9784e]/50 bg-[#f1f8fb] text-[10px] font-medium tabular-nums text-[#a14f32]">01</span>
                        <svg aria-hidden="true" class="h-24 w-[122px] shrink-0 rounded-xl bg-white shadow-sm ring-1 ring-sky-200" viewBox="0 0 112 88" fill="none">
                            <path d="M0 68h112M14 78h85" stroke="#b7d5e1" stroke-width="2"/>
                            <path d="M23 18h45v43H23z" fill="#fff" stroke="#8db8c8" stroke-width="2"/>
                            <path d="M23 30h45M34 14v9m23-9v9" stroke="#b65d3b" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M31 39h7m7 0h7m-21 9h7m7 0h7m-21 9h7" stroke="#6d909d" stroke-width="2" stroke-linecap="round"/>
                            <path d="M78 50h20m-20 6h13" stroke="#8db8c8" stroke-width="2" stroke-linecap="round"/>
                            <path d="m76 36 5 5 10-12" stroke="#b65d3b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="min-w-0 border-b border-sky-200/70 py-2">
                            <p class="text-[10px] font-medium uppercase tracking-[0.12em] text-[#a14f32]">Pianifica</p>
                            <h3 class="mt-1 text-sm font-medium leading-5 text-[#152b38]">Turni e coperture</h3>
                            <p class="mt-1 text-xs leading-4 text-slate-500">Definisci servizio e orari.</p>
                        </div>
                    </li>
                    <li class="grid grid-cols-[32px_122px_minmax(0,1fr)] items-center gap-3">
                        <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border border-[#d9784e]/50 bg-[#f1f8fb] text-[10px] font-medium tabular-nums text-[#a14f32]">02</span>
                        <svg aria-hidden="true" class="h-24 w-[122px] shrink-0 rounded-xl bg-white shadow-sm ring-1 ring-sky-200" viewBox="0 0 112 88" fill="none">
                            <path d="M0 68h112M14 78h85" stroke="#b7d5e1" stroke-width="2"/>
                            <path d="M15 61V31l20-13 20 13v30" fill="#fff" stroke="#8db8c8" stroke-width="2"/>
                            <path d="M15 34h40M23 39v22m10-22v22m10-22v22" stroke="#a9c2bd" stroke-width="2"/>
                            <path d="M72 34c0-6 4-10 9-10s9 4 9 10-4 9-9 9-9-3-9-9Z" fill="#c99a7c"/>
                            <path d="M66 62c1-10 6-15 15-15s14 5 15 15" fill="#5a8797"/>
                            <path d="M90 48h11v14H90" stroke="#b65d3b" stroke-width="2"/>
                        </svg>
                        <div class="min-w-0 border-b border-sky-200/70 py-2">
                            <p class="text-[10px] font-medium uppercase tracking-[0.12em] text-[#a14f32]">Assegna</p>
                            <h3 class="mt-1 text-sm font-medium leading-5 text-[#152b38]">Persone e squadre</h3>
                            <p class="mt-1 text-xs leading-4 text-slate-500">Abbina operatori e competenze.</p>
                        </div>
                    </li>
                    <li class="grid grid-cols-[32px_122px_minmax(0,1fr)] items-center gap-3">
                        <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border border-[#d9784e]/50 bg-[#f1f8fb] text-[10px] font-medium tabular-nums text-[#a14f32]">03</span>
                        <svg aria-hidden="true" class="h-24 w-[122px] shrink-0 rounded-xl bg-white shadow-sm ring-1 ring-sky-200" viewBox="0 0 112 88" fill="none">
                            <path d="M0 68h112M14 78h85" stroke="#b7d5e1" stroke-width="2"/>
                            <path d="M12 61V38l27-16 27 16v23H12Z" fill="#fff" stroke="#8db8c8" stroke-width="2"/>
                            <path d="M20 42h37M20 49h37M20 56h18" stroke="#6d909d" stroke-width="2" stroke-linecap="round"/>
                            <path d="M78 17c9 0 15 6 15 15 0 10-15 25-15 25S63 42 63 32c0-9 6-15 15-15Z" fill="#d9784e"/>
                            <path d="M75 31h6m-3-3v6" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                            <path d="M91 56h13" stroke="#8db8c8" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <div class="min-w-0 border-b border-sky-200/70 py-2">
                            <p class="text-[10px] font-medium uppercase tracking-[0.12em] text-[#a14f32]">Presidia</p>
                            <h3 class="mt-1 text-sm font-medium leading-5 text-[#152b38]">Sedi e attività</h3>
                            <p class="mt-1 text-xs leading-4 text-slate-500">Segui il servizio sul campo.</p>
                        </div>
                    </li>
                    <li class="grid grid-cols-[32px_122px_minmax(0,1fr)] items-center gap-3">
                        <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border border-[#d9784e]/50 bg-[#f1f8fb] text-[10px] font-medium tabular-nums text-[#a14f32]">04</span>
                        <svg aria-hidden="true" class="h-24 w-[122px] shrink-0 rounded-xl bg-white shadow-sm ring-1 ring-sky-200" viewBox="0 0 112 88" fill="none">
                            <path d="M0 68h112M14 78h85" stroke="#b7d5e1" stroke-width="2"/>
                            <path d="M24 15h46v48H24z" fill="#fff" stroke="#8db8c8" stroke-width="2"/>
                            <path d="M33 25h23m-23 7h29" stroke="#6d909d" stroke-width="2" stroke-linecap="round"/>
                            <path d="M34 55V44m9 11V37m9 18V47m9 8V33" stroke="#b65d3b" stroke-width="3" stroke-linecap="round"/>
                            <path d="m77 45 7 7 15-17" stroke="#5a8797" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M78 61h22" stroke="#8db8c8" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <div class="min-w-0 py-2">
                            <p class="text-[10px] font-medium uppercase tracking-[0.12em] text-[#a14f32]">Verifica</p>
                            <h3 class="mt-1 text-sm font-medium leading-5 text-[#152b38]">Presenze e report</h3>
                            <p class="mt-1 text-xs leading-4 text-slate-500">Controlla risultati e presenze.</p>
                        </div>
                    </li>
                </ol>
            </div>

            <p class="mt-3 border-t border-sky-200/70 pt-4 text-[10px] font-medium uppercase tracking-[0.16em] text-slate-500">Un percorso, ogni servizio sotto controllo</p>
        </div>
    </aside>
</main>
