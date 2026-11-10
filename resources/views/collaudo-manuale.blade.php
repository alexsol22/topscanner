<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f9fc">
    <title>Checklist collaudo manuale (accesso riservato) · Omnia Control</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #263648;
            --muted: #64748b;
            --line: #dce8f0;
            --blue: #1677a8;
            --blue-dark: #125d83;
            --blue-pale: #eff8fc;
            --rust: #b65d3b;
            --green: #18794e;
            --amber: #9a5b08;
            --red: #b42318;
            --surface: #fff;
            --page: #f4f9fc;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            line-height: 1.5;
        }

        button, input, select { font: inherit; }
        button, select { cursor: pointer; }
        button:focus-visible, input:focus-visible, select:focus-visible, summary:focus-visible {
            outline: 3px solid #70c3ea;
            outline-offset: 2px;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 64px;
            padding: 10px clamp(16px, 4vw, 48px);
            border-bottom: 1px solid var(--line);
            background: rgb(255 255 255 / 94%);
            backdrop-filter: blur(12px);
        }

        .brand { display: flex; align-items: center; gap: 11px; font-weight: 750; letter-spacing: -.03em; }
        .brand-mark {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 12px;
            background: var(--blue-pale);
            color: var(--blue);
            font-size: 19px;
        }
        .brand-accent { color: var(--rust); }
        .topbar-label { color: var(--muted); font-size: 13px; }

        main { width: min(1380px, calc(100% - 32px)); margin: 28px auto 64px; }
        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: clamp(22px, 4vw, 36px);
            border: 1px solid #dcecf5;
            border-radius: 24px;
            background: linear-gradient(125deg, #fff 15%, #edf8fd 100%);
            box-shadow: 0 8px 26px rgb(34 82 110 / 5%);
        }
        .eyebrow { margin: 0 0 6px; color: var(--blue); font-size: 12px; font-weight: 750; letter-spacing: .1em; text-transform: uppercase; }
        h1 { margin: 0; color: #172b3b; font-size: clamp(26px, 4vw, 38px); line-height: 1.15; letter-spacing: -.04em; }
        .hero-copy { max-width: 760px; margin: 12px 0 0; color: var(--muted); }
        .hero-tag {
            flex: 0 0 auto;
            padding: 8px 12px;
            border: 1px solid #cfe7f3;
            border-radius: 999px;
            background: white;
            color: var(--blue-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .notice {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            margin: 18px 0;
            padding: 16px 18px;
            border: 1px solid #f1d8a8;
            border-radius: 16px;
            background: #fff9eb;
            color: #69450e;
        }
        .notice-icon { font-size: 20px; }
        .notice strong { display: block; margin-bottom: 3px; }
        .notice p { margin: 0; font-size: 14px; }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(210px, 1fr) minmax(180px, .7fr) auto auto;
            align-items: end;
            gap: 12px;
            margin: 18px 0;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--surface);
            box-shadow: 0 3px 12px rgb(34 82 110 / 3%);
        }
        .field { display: grid; gap: 5px; }
        .field label { color: #526579; font-size: 12px; font-weight: 700; }
        .field input, .field select, .note-input, .status-select {
            min-height: 40px;
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd9e3;
            border-radius: 10px;
            background: white;
            color: var(--ink);
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 8px 13px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: white;
            color: #405367;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }
        .button:hover { border-color: #9cc9df; background: #f6fbfe; }
        .button-primary { border-color: var(--blue); background: var(--blue); color: white; }
        .button-primary:hover { border-color: var(--blue-dark); background: var(--blue-dark); }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0;
        }
        .summary-card {
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: white;
        }
        .summary-card span { display: block; color: var(--muted); font-size: 12px; }
        .summary-card strong { display: block; margin-top: 2px; color: #223b50; font-size: 24px; letter-spacing: -.04em; }
        .progress-track { height: 7px; margin-top: 8px; overflow: hidden; border-radius: 99px; background: #e8eff4; }
        .progress-fill { width: 0; height: 100%; border-radius: inherit; background: #2e9c70; transition: width .2s ease; }

        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin: 30px 2px 12px; }
        .section-heading h2 { margin: 0; font-size: 19px; letter-spacing: -.02em; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 13px; }
        .department {
            margin: 12px 0;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: white;
            box-shadow: 0 4px 15px rgb(34 82 110 / 3%);
        }
        .department summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 20px;
            background: linear-gradient(90deg, #fff, #f8fcfe);
            cursor: pointer;
            list-style: none;
        }
        .department summary::-webkit-details-marker { display: none; }
        .department summary::after { content: "+"; color: var(--blue); font-size: 22px; font-weight: 500; }
        .department[open] summary::after { content: "−"; }
        .dept-title { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .dept-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 12px;
            background: var(--blue-pale);
            font-size: 18px;
        }
        .dept-title strong { display: block; color: #263b4e; font-size: 15px; }
        .dept-title small { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
        .dept-count { flex: 0 0 auto; color: var(--muted); font-size: 12px; }

        .table-wrap { overflow-x: auto; border-top: 1px solid #e8eff4; }
        table { width: 100%; min-width: 1040px; border-collapse: collapse; text-align: left; }
        thead { background: #f4f9fc; }
        th { padding: 10px 12px; color: #566a7e; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        td { padding: 12px; border-top: 1px solid #edf2f6; vertical-align: top; font-size: 12px; }
        tbody tr:hover { background: #f8fcfe; }
        .id-cell { width: 78px; color: var(--blue-dark); font-weight: 800; white-space: nowrap; }
        .scenario { width: 30%; }
        .expected { width: 32%; color: #526579; }
        .prod { width: 150px; }
        .result { width: 155px; }
        .scenario strong { display: block; color: #31485d; font-size: 12px; }
        .scenario span { display: block; margin-top: 4px; color: var(--muted); }
        .safety {
            display: inline-flex;
            margin-top: 6px;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }
        .safe-read { background: #eaf6ef; color: #17613f; }
        .safe-caution { background: #fff3d8; color: #85500a; }
        .safe-stop { background: #fdecea; color: #a12b24; }
        .status-select { min-height: 34px; padding: 5px 7px; font-size: 12px; }
        .note-input { min-height: 34px; padding: 5px 8px; font-size: 12px; }
        .empty { display: none; padding: 24px; color: var(--muted); text-align: center; }
        .filtered-empty .empty { display: block; }
        .footnote { margin: 18px 2px; color: var(--muted); font-size: 12px; }
        .no-results { display: none; margin: 20px 0; padding: 24px; border: 1px dashed #b6c8d6; border-radius: 16px; color: var(--muted); text-align: center; }
        .no-results.visible { display: block; }

        @media (max-width: 760px) {
            main { width: min(100% - 20px, 1380px); margin-top: 16px; }
            .topbar { min-height: 58px; padding-inline: 14px; }
            .topbar-label { display: none; }
            .hero { align-items: flex-start; flex-direction: column; border-radius: 18px; }
            .toolbar { grid-template-columns: 1fr 1fr; }
            .field-search { grid-column: 1 / -1; }
            .summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .summary-card { padding: 11px 12px; }
            .summary-card strong { font-size: 21px; }
            .section-heading { align-items: flex-start; flex-direction: column; }
            .department summary { padding: 14px; }
            .dept-count { display: none; }
        }

        @media (max-width: 430px) {
            .toolbar { grid-template-columns: 1fr; }
            .field-search { grid-column: auto; }
            .toolbar .button { width: 100%; }
            .hero-tag { white-space: normal; }
        }

        @media print {
            @page { margin: 12mm; }
            body { background: white; color: #111827; font-size: 10pt; }
            .topbar { position: static; padding: 0 0 10px; border-bottom: 1px solid #bbb; }
            main { width: 100%; margin: 12px 0; }
            .hero { padding: 16px; box-shadow: none; }
            .notice { break-inside: avoid; }
            .toolbar, .hero-tag, .button, .dept-count, .department summary::after { display: none !important; }
            .summary { gap: 6px; }
            .summary-card { padding: 8px; }
            .department { overflow: visible; break-inside: auto; box-shadow: none; }
            .department:not([open]) > .table-wrap { display: block !important; }
            .department summary { padding: 10px; }
            .table-wrap { overflow: visible; }
            table { min-width: 0; }
            th, td { padding: 6px; font-size: 8pt; }
            .note-input, .status-select { min-height: 0; padding: 0; border: 0; appearance: none; background: transparent; color: #111; }
            .note-input { width: 100%; }
            .status-select { font-weight: 700; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">✓</span>
            <span>Omnia <span class="brand-accent">Control</span></span>
        </div>
        <span class="topbar-label">Strumento operativo · Collaudo manuale</span>
    </header>

    <main>
        <section class="hero" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Verifica di regressione</p>
                <h1 id="page-title">Checklist collaudo manuale</h1>
                <p class="hero-copy">Confronta nuova e vecchia app con lo stesso ruolo, gli stessi filtri e lo stesso record. Registra le differenze per reparto e conserva gli esiti sul dispositivo.</p>
            </div>
            <span class="hero-tag">Accesso riservato · Salvataggio locale automatico</span>
        </section>

        <aside class="notice" aria-label="Avvertenza per i test in produzione">
            <span class="notice-icon" aria-hidden="true">⚠️</span>
            <div>
                <strong>In produzione: prima di tutto, non alterare dati o avvisare utenti reali.</strong>
                <p>Consulta pagine, applica filtri e scarica documenti autorizzati. Timbrature, modifiche, invii, cancellazioni o azioni che marcano notifiche come lette richiedono un ambiente di test oppure un record di prova concordato. Fermati se un’azione potrebbe avere effetti reali.</p>
            </div>
        </aside>

        <section class="toolbar" aria-label="Filtri e azioni checklist">
            <div class="field field-search">
                <label for="search">Cerca per ID, reparto o attività</label>
                <input id="search" type="search" placeholder="Es. PRE-03, esportazione, QR">
            </div>
            <div class="field">
                <label for="department-filter">Reparto</label>
                <select id="department-filter">
                    <option value="all">Tutti i reparti</option>
                </select>
            </div>
            <button class="button" id="expand-all" type="button">Apri reparti</button>
            <button class="button button-primary" id="print" type="button">Stampa / PDF</button>
        </section>

        <section class="summary" aria-label="Riepilogo avanzamento">
            <div class="summary-card"><span>Test in checklist</span><strong id="total-count">0</strong></div>
            <div class="summary-card"><span>Passati</span><strong id="passed-count">0</strong></div>
            <div class="summary-card"><span>Da verificare / bloccati</span><strong id="pending-count">0</strong></div>
            <div class="summary-card">
                <span>Avanzamento</span>
                <strong id="progress-label">0%</strong>
                <div class="progress-track" role="progressbar" aria-label="Avanzamento collaudo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </section>

        <div class="section-heading">
            <h2>Test suddivisi per reparto</h2>
            <p>Per ogni riga annota l’esito sia sulla vecchia sia sulla nuova app, poi registra l’eventuale differenza.</p>
        </div>

        <div id="departments">
            <details class="department" open data-department="comuni" data-title="Tutti i reparti · accesso e navigazione">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">🧭</span><span><strong>Tutti i reparti — accesso e navigazione</strong><small>Controlli comuni a tutti gli utenti</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="COM-01"><td class="id-cell">COM-01</td><td class="scenario"><strong>Ingresso nell’app</strong><span>Esci da eventuali sessioni precedenti; accedi con un account del reparto e annota la pagina iniziale.</span></td><td class="expected">Accesso riuscito e pagina iniziale corretta per ruolo, come nella vecchia app. Non annotare la password.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-02"><td class="id-cell">COM-02</td><td class="scenario"><strong>Menu e navigazione abituale</strong><span>Apri ogni voce usata dal reparto; torna indietro e riapri la pagina.</span></td><td class="expected">Voci previste per ruolo, pagine caricate e navigazione coerente; nessuna schermata vuota o errore.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-03"><td class="id-cell">COM-03</td><td class="scenario"><strong>Record noto</strong><span>Apri un cliente, sede, operatore o turno identificato in anticipo; verifica intestazione, stato, date e dettagli principali.</span></td><td class="expected">Record giusto, stato e dati principali coerenti con la vecchia app e con la fonte aziendale.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-04"><td class="id-cell">COM-04</td><td class="scenario"><strong>Ricerca positiva e senza risultati</strong><span>Cerca prima un elemento noto, poi un termine che non dovrebbe corrispondere a nessun elemento.</span></td><td class="expected">Il record atteso compare; con zero risultati appare uno stato vuoto comprensibile, senza righe estranee o errore tecnico.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-05"><td class="id-cell">COM-05</td><td class="scenario"><strong>Filtri, paginazione e ritorno</strong><span>Applica un filtro noto, passa a un dettaglio o pagina successiva e torna all’elenco.</span></td><td class="expected">Filtro e contesto sono conservati o ripristinati come previsto; conteggi e risultati non si duplicano.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-06"><td class="id-cell">COM-06</td><td class="scenario"><strong>Controllo dei permessi</strong><span>Con il referente IT, apri una pagina non prevista per il ruolo usando l’URL diretto, senza provare azioni di modifica.</span></td><td class="expected">Accesso negato o reindirizzato; nessun dato riservato è visibile.</td><td class="prod"><span class="safety safe-read">✅ Consultazione soltanto</span></td><td class="result"></td></tr>
                            <tr data-id="COM-07"><td class="id-cell">COM-07</td><td class="scenario"><strong>Download autorizzato</strong><span>Scarica un documento già autorizzato e aprilo localmente senza modificarlo né inoltrarlo.</span></td><td class="expected">File leggibile, integro e riferito esattamente al record selezionato.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="COM-08"><td class="id-cell">COM-08</td><td class="scenario"><strong>Schermo e dispositivo</strong><span>Ripeti navigazione e lettura delle informazioni principali su computer e dispositivo mobile aziendale, se previsto.</span></td><td class="expected">Contenuti essenziali, menu e pulsanti rimangono leggibili e raggiungibili.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="amministrazione" data-title="Amministrazione · clienti operatori amministratori impostazioni">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">🗂️</span><span><strong>Amministrazione — anagrafiche e impostazioni</strong><small>Clienti, operatori, amministratori, profilo, messaggi</small></span></span>
                    <span class="dept-count">9 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="AMM-01"><td class="id-cell">AMM-01</td><td class="scenario"><strong>Elenco clienti e stato</strong><span>Apri l’elenco, cerca un cliente attivo e uno non attivo, se esistono.</span></td><td class="expected">Nominativi, stato e risultati coincidono con la vecchia app; il filtro non include clienti non pertinenti.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-02"><td class="id-cell">AMM-02</td><td class="scenario"><strong>Dettaglio cliente e sedi</strong><span>Apri un cliente noto e controlla i dati identificativi e tutte le sedi associate.</span></td><td class="expected">Dati e associazioni corretti; nessuna sede appartenente a un altro cliente compare.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-03"><td class="id-cell">AMM-03</td><td class="scenario"><strong>Elenco operatori e stato</strong><span>Cerca un operatore attivo e uno non attivo, se disponibile.</span></td><td class="expected">Nominativo, codice e stato coerenti con la vecchia app e con la situazione attuale.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-04"><td class="id-cell">AMM-04</td><td class="scenario"><strong>Dettaglio e assegnazioni operatore</strong><span>Apri il dettaglio e controlla contatti, assegnazioni e documenti visibili.</span></td><td class="expected">Informazioni coerenti; nessun dato o documento di un altro operatore è esposto.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-05"><td class="id-cell">AMM-05</td><td class="scenario"><strong>Utenti amministratori e registro accessi</strong><span>Apri le pagine utenti e accessi con account autorizzato; confronta una registrazione nota.</span></td><td class="expected">Pagine visibili solo ai ruoli previsti; utente e data/ora della registrazione corretti.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-06"><td class="id-cell">AMM-06</td><td class="scenario"><strong>Impostazioni e modelli</strong><span>Apri configurazioni e modelli di messaggio; confronta i valori senza salvarli.</span></td><td class="expected">Valori approvati e testi attuali presenti; non vengono modificati durante il controllo.</td><td class="prod"><span class="safety safe-read">✅ Consultazione, non salvare</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-07"><td class="id-cell">AMM-07</td><td class="scenario"><strong>Profilo del proprio account</strong><span>Apri il profilo e verifica nome, recapiti e foto, se presente.</span></td><td class="expected">Il profilo corrisponde all’account autenticato; nessun dato di un altro utente viene mostrato.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-08"><td class="id-cell">AMM-08</td><td class="scenario"><strong>Creazione/modifica di anagrafica</strong><span>Verifica la procedura di creazione o modifica usando esclusivamente un record di test approvato. Controlla campi, conferma e associazioni.</span></td><td class="expected">Salvataggio completo; validazioni e associazioni corrette; permessi dell’account di test solo quelli approvati.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo record di test</span></td><td class="result"></td></tr>
                            <tr data-id="AMM-09"><td class="id-cell">AMM-09</td><td class="scenario"><strong>Campo obbligatorio/formato non valido</strong><span>Prova la validazione soltanto su un record di test; verifica che l’errore non persista dati parziali.</span></td><td class="expected">Messaggio chiaro sul campo; nessuna modifica parziale o alterazione di anagrafiche reali.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo record di test</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="operativo" data-title="Operativo · pianificazione turni giornale attività GDA">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">🗓️</span><span><strong>Operativo — pianificazione turni e GDA</strong><small>Agenda, filtri, turni completati, giornale delle attività</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="OPS-01"><td class="id-cell">OPS-01</td><td class="scenario"><strong>Calendario turni noto</strong><span>Apri un periodo già verificato e controlla giorno, sede, operatore e orario di alcuni turni.</span></td><td class="expected">Turni e orari coincidono con la vecchia app e con il piano approvato.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-02"><td class="id-cell">OPS-02</td><td class="scenario"><strong>Filtro cliente e intervallo date</strong><span>Seleziona un cliente e un intervallo noto; registra quanti risultati mostra prima e dopo.</span></td><td class="expected">Sono inclusi solo turni di quel cliente e in quel periodo; totali coerenti e riproducibili.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-03"><td class="id-cell">OPS-03</td><td class="scenario"><strong>Filtro e legenda stato turno</strong><span>Prova gli stati disponibili confrontandoli con la legenda e un turno identificato.</span></td><td class="expected">Lo stato visualizzato è quello atteso; nessun turno aperto è contato come completato o viceversa.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-04"><td class="id-cell">OPS-04</td><td class="scenario"><strong>Dettaglio di turno completato</strong><span>Apri un turno noto e leggi i passaggi, gli orari, le note e le informazioni associate.</span></td><td class="expected">Cliente, sede, operatore, inizio/fine e passaggi corrispondono alla registrazione approvata.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-05"><td class="id-cell">OPS-05</td><td class="scenario"><strong>GDA senza filtro e con criterio</strong><span>Apri il GDA senza selezioni; poi applica un singolo criterio noto e confronta l’elenco.</span></td><td class="expected">Senza criterio i totali sono mostrati/nascosti come previsto; con criterio, turni completati e riepiloghi corretti.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-06"><td class="id-cell">OPS-06</td><td class="scenario"><strong>Stampa / anteprima GDA</strong><span>Apri l’anteprima di stampa su un periodo noto; controlla intestazione, cliente, sede e passaggi senza distribuire il documento.</span></td><td class="expected">Dati completi e corretti, senza righe mancanti o appartenenti a un altro cliente.</td><td class="prod"><span class="safety safe-read">✅ Anteprima soltanto</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-07"><td class="id-cell">OPS-07</td><td class="scenario"><strong>Coerenza dei conteggi</strong><span>Confronta stato e numero dei turni in calendario, elenco e GDA per un intervallo ristretto e noto.</span></td><td class="expected">Nessun duplicato; stesso turno classificato coerentemente su tutte le schermate.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPS-08"><td class="id-cell">OPS-08</td><td class="scenario"><strong>Apertura/chiusura turno manuale</strong><span>Solo su un turno esplicitamente predisposto per il collaudo: inserisci la nota richiesta e verifica data e ora salvate.</span></td><td class="expected">Registrazione completa e visibile nel punto corretto; nessuna modifica a un turno operativo.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo turno di prova</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="presenze" data-title="Presenze · timbrature turni operatori ed esportazioni">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">⏱️</span><span><strong>Presenze — timbrature e conteggio ore</strong><small>Presenze clienti/operatori, turni personali, stampe ed esportazioni</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="PRE-01"><td class="id-cell">PRE-01</td><td class="scenario"><strong>Presenze clienti</strong><span>Apri la vista clienti per un giorno verificato; confronta cliente, sede, data e orari.</span></td><td class="expected">Presenze e totali corrispondono al registro approvato e alla vecchia app.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-02"><td class="id-cell">PRE-02</td><td class="scenario"><strong>Presenze operatori</strong><span>Apri la vista operatori per lo stesso periodo e controlla le assegnazioni.</span></td><td class="expected">Operatore, turno e orari corretti; dati visibili solo ai ruoli autorizzati.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-03"><td class="id-cell">PRE-03</td><td class="scenario"><strong>Esportazione presenze</strong><span>Esporta un intervallo breve già verificato; apri il file localmente e controlla righe, intestazioni, date e totali.</span></td><td class="expected">Formato apribile e contenuto identico ai risultati filtrati; nessuna riga fuori ambito.</td><td class="prod"><span class="safety safe-read">✅ Non inoltrare il file</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-04"><td class="id-cell">PRE-04</td><td class="scenario"><strong>Riepilogo ore cliente/operatore</strong><span>Apri il riepilogo di un soggetto e periodo già controllati; confronta il totale con il calcolo approvato.</span></td><td class="expected">Periodo e ore previste/lavorate/saldo coerenti con i dati sorgente.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-05"><td class="id-cell">PRE-05</td><td class="scenario"><strong>“Miei turni” operatore</strong><span>Con account operatore apri i turni futuri e un turno passato noto.</span></td><td class="expected">L’utente vede solo i propri turni, con sede, orario e istruzioni corrette.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-06"><td class="id-cell">PRE-06</td><td class="scenario"><strong>Scansione QR di prova</strong><span>Conferma prima il codice, la sede e il turno col referente; scansiona solo il QR designato per test e fermati prima della conferma timbratura.</span></td><td class="expected">Sede corretta e indicazione chiara della possibilità di timbrare; nessuna presenza reale creata.</td><td class="prod"><span class="safety safe-caution">⚠️ QR/account di test</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-07"><td class="id-cell">PRE-07</td><td class="scenario"><strong>Sede senza coordinate</strong><span>Consulta l’avviso o il comportamento previsto per sede senza coordinate; non tentare di forzare la scansione.</span></td><td class="expected">Operazione rifiutata o bloccata secondo regola; nessuna presenza registrata.</td><td class="prod"><span class="safety safe-read">✅ Solo osservazione</span></td><td class="result"></td></tr>
                            <tr data-id="PRE-08"><td class="id-cell">PRE-08</td><td class="scenario"><strong>Timbratura inizio e fine</strong><span>Esegui una timbratura completa soltanto su un turno di test concordato e verifica che non si duplichi.</span></td><td class="expected">Un solo inizio e una sola fine con date, ore e sede corrette; nessun effetto su servizio reale.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo turno di prova</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="clienti" data-title="Clienti · sedi postazioni e QR">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">📍</span><span><strong>Clienti — sedi, postazioni e codici QR</strong><small>Consultazione delle sedi e verifica dei codici senza timbrare</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="CLI-01"><td class="id-cell">CLI-01</td><td class="scenario"><strong>Elenco sedi cliente</strong><span>Apri le sedi di un cliente noto e controlla nome, stato e quantità.</span></td><td class="expected">Sono elencate solo le sedi del cliente selezionato; i dati coincidono con la vecchia app.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-02"><td class="id-cell">CLI-02</td><td class="scenario"><strong>Dettaglio sede, indirizzo e foto</strong><span>Apri una sede nota, verifica indirizzo e visualizza/scarica la foto senza sostituirla.</span></td><td class="expected">Indirizzo e immagine riferiti alla sede corretta; foto leggibile se presente.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-03"><td class="id-cell">CLI-03</td><td class="scenario"><strong>Postazioni della sede</strong><span>Apri le postazioni della sede e confronta i nomi/identificativi con quelli attesi.</span></td><td class="expected">Postazioni e relazioni alla sede sono corrette; nessuna postazione di altra sede appare.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-04"><td class="id-cell">CLI-04</td><td class="scenario"><strong>QR sede senza timbratura</strong><span>Visualizza o scarica il QR; verifica a quale sede è associato senza avviare una scansione operativa.</span></td><td class="expected">Codice leggibile e associato alla sede prevista; etichetta non ambigua.</td><td class="prod"><span class="safety safe-read">✅ Non scansionare operativamente</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-05"><td class="id-cell">CLI-05</td><td class="scenario"><strong>QR postazione e destinazione</strong><span>Apri il QR postazione e controlla l’identificativo/destinazione; non confermare un accesso o una presenza.</span></td><td class="expected">Destinazione legata alla postazione corretta; la sola visualizzazione non registra un evento.</td><td class="prod"><span class="safety safe-read">✅ Solo visualizzazione</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-06"><td class="id-cell">CLI-06</td><td class="scenario"><strong>Generatore QR: selezione e anteprima</strong><span>Seleziona un elemento autorizzato e controlla anteprima e dati senza sostituire/stampare codici ufficiali.</span></td><td class="expected">Anteprima e riferimento corrispondono all’elemento scelto; nessuna associazione inattesa.</td><td class="prod"><span class="safety safe-read">✅ Anteprima soltanto</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-07"><td class="id-cell">CLI-07</td><td class="scenario"><strong>Nuova sede/postazione</strong><span>Simula o esegui creazione solo con un record di test approvato; verifica associazioni, indirizzo e QR.</span></td><td class="expected">Record collegato al cliente giusto; dati e QR coerenti, senza impattare sedi operative.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo record di test</span></td><td class="result"></td></tr>
                            <tr data-id="CLI-08"><td class="id-cell">CLI-08</td><td class="scenario"><strong>Isolamento dati tra clienti</strong><span>Con referente autorizzato, verifica che un account cliente non possa consultare una sede di un altro cliente via menu o URL.</span></td><td class="expected">Accesso negato e nessuna informazione o immagine del cliente diverso esposta.</td><td class="prod"><span class="safety safe-read">✅ Solo consultazione</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="operatori" data-title="Operatori · bacheca documenti profilo notifiche">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">👷</span><span><strong>Operatori — bacheca, documenti e profilo</strong><small>Controlli di visibilità, lettura e notifiche personali</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="OPE-01"><td class="id-cell">OPE-01</td><td class="scenario"><strong>Bacheca e destinatari</strong><span>Apri un avviso noto e confronta testo, data e destinatari previsti.</span></td><td class="expected">Testo corretto e visibile solo agli operatori previsti, come nella vecchia app.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-02"><td class="id-cell">OPE-02</td><td class="scenario"><strong>Conteggio avvisi non letti</strong><span>Confronta il badge con il numero di avvisi non letti già noto, senza aprire o marcare altri avvisi.</span></td><td class="expected">Conteggio coerente e limitato all’account dell’operatore.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-03"><td class="id-cell">OPE-03</td><td class="scenario"><strong>Elenco e download documenti</strong><span>Apri e scarica un documento autorizzato riferito all’account; controlla stato e leggibilità.</span></td><td class="expected">Documento corretto, integro e accessibile solo al destinatario previsto.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-04"><td class="id-cell">OPE-04</td><td class="scenario"><strong>Campanella e badge notifiche</strong><span>Concorda prima se l’apertura di una notifica reale la segna come letta; confronta l’elenco con gli avvisi attesi.</span></td><td class="expected">Notifiche pertinenti e conteggio coerente; nessun dato appartenente ad altri operatori.</td><td class="prod"><span class="safety safe-caution">⚠️ Aprire può segnare letto</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-05"><td class="id-cell">OPE-05</td><td class="scenario"><strong>Profilo e fotografia</strong><span>Visualizza i dati del proprio profilo e la foto senza modificarli.</span></td><td class="expected">Profilo e foto dell’account autenticato; nessun profilo altrui consultabile.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-06"><td class="id-cell">OPE-06</td><td class="scenario"><strong>Turni e istruzioni personali</strong><span>Apri i turni assegnati e verifica orario, sede e istruzioni operative.</span></td><td class="expected">Solo turni propri; dettagli aggiornati e coerenti con il piano approvato.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-07"><td class="id-cell">OPE-07</td><td class="scenario"><strong>Documento scaduto/mancante</strong><span>Se esiste un caso già noto, consulta stato e messaggio; non caricare o sostituire file.</span></td><td class="expected">Stato e istruzioni espliciti; non viene mostrato un documento diverso per errore.</td><td class="prod"><span class="safety safe-read">✅ Sola lettura</span></td><td class="result"></td></tr>
                            <tr data-id="OPE-08"><td class="id-cell">OPE-08</td><td class="scenario"><strong>Marcare come letto</strong><span>Esegui solo se il responsabile ha concordato che il singolo avviso/documento reale può essere marcato letto.</span></td><td class="expected">Cambia esclusivamente lo stato di lettura dell’elemento previsto; testo e destinatari restano invariati.</td><td class="prod"><span class="safety safe-caution">⚠️ Modifica una registrazione</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="comunicazioni" data-title="Comunicazioni · SMS email messaggi notifiche">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">✉️</span><span><strong>Comunicazioni — SMS, email e notifiche</strong><small>Modelli, anteprime, stato consegna e invii controllati</small></span></span>
                    <span class="dept-count">8 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="COMMS-01"><td class="id-cell">COMMS-01</td><td class="scenario"><strong>Modelli, canali e limiti invio</strong><span>Consulta i modelli e il numero invii configurato per SMS/email; non salvare modifiche.</span></td><td class="expected">Modelli, canali e limiti coincidono con le impostazioni approvate.</td><td class="prod"><span class="safety safe-read">✅ Non salvare</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-02"><td class="id-cell">COMMS-02</td><td class="scenario"><strong>Anteprima e segnaposto</strong><span>Usa un dato di prova per visualizzare il messaggio prima dell’invio.</span></td><td class="expected">Segnaposto sostituiti correttamente; nessun valore vuoto o informazione appartenente ad altri.</td><td class="prod"><span class="safety safe-read">✅ Solo anteprima</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-03"><td class="id-cell">COMMS-03</td><td class="scenario"><strong>Destinatario e contenuti della prova</strong><span>Verifica con anteprima che selezione operatore, turni e documenti si riferiscano allo stesso destinatario.</span></td><td class="expected">Dati inclusi solo se pertinenti all’operatore selezionato; destinatario atteso.</td><td class="prod"><span class="safety safe-read">✅ Non inviare</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-04"><td class="id-cell">COMMS-04</td><td class="scenario"><strong>Registro invii e stato consegna</strong><span>Consulta un invio storico noto e confronta canale, destinatario e stato.</span></td><td class="expected">Stato e destinatario associati al messaggio corretto; nessun falso “consegnato”.</td><td class="prod"><span class="safety safe-read">✅ Consultazione</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-05"><td class="id-cell">COMMS-05</td><td class="scenario"><strong>Invio email/SMS di prova</strong><span>Solo dopo autorizzazione, invia a un recapito interno di test confermato; verifica testo e destinatario.</span></td><td class="expected">Ricezione al solo recapito di test; nessun cliente o operatore reale riceve il messaggio.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo recapito interno test</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-06"><td class="id-cell">COMMS-06</td><td class="scenario"><strong>Esito errore del provider</strong><span>Controlla una registrazione d’errore già presente; non provocare un errore tramite invio reale.</span></td><td class="expected">Esito fallito/non consegnato registrato chiaramente e non presentato come successo.</td><td class="prod"><span class="safety safe-read">✅ Consultazione</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-07"><td class="id-cell">COMMS-07</td><td class="scenario"><strong>SMS disattivati</strong><span>Consulta configurazione e comportamento previsto senza modificare lo switch o la configurazione.</span></td><td class="expected">Nessun SMS parte quando disattivato; altri canali seguono la regola approvata.</td><td class="prod"><span class="safety safe-read">✅ Non modificare configurazione</span></td><td class="result"></td></tr>
                            <tr data-id="COMMS-08"><td class="id-cell">COMMS-08</td><td class="scenario"><strong>Azioni di invio non autorizzate</strong><span>Non usare invia, ripeti, testa invio o azioni analoghe su destinatari reali durante il collaudo.</span></td><td class="expected">Nessun messaggio indesiderato, duplicato o inviato al destinatario sbagliato.</td><td class="prod"><span class="safety safe-stop">⛔ Non eseguire su utenti reali</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="department" open data-department="accesso" data-title="Accesso · login logout recupero password sessione">
                <summary>
                    <span class="dept-title"><span class="dept-icon" aria-hidden="true">🔐</span><span><strong>Accesso — login, recupero password e sessione</strong><small>Verifiche con account di test e casella controllata</small></span></span>
                    <span class="dept-count">5 controlli</span>
                </summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Scenario e passaggi</th><th>Risultato atteso / confronto</th><th>Produzione</th><th>Esito / note</th></tr></thead>
                        <tbody>
                            <tr data-id="AUTH-01"><td class="id-cell">AUTH-01</td><td class="scenario"><strong>Login valido</strong><span>Accedi con il tuo account attivo e verifica ruolo e pagina iniziale.</span></td><td class="expected">Accesso riuscito; pagina iniziale e menu corrispondono al ruolo previsto.</td><td class="prod"><span class="safety safe-read">✅ Account proprio</span></td><td class="result"></td></tr>
                            <tr data-id="AUTH-02"><td class="id-cell">AUTH-02</td><td class="scenario"><strong>Logout e protezione sessione</strong><span>Esci e prova il pulsante indietro del browser su una pagina riservata.</span></td><td class="expected">Sessione terminata; i dati riservati non sono consultabili senza una nuova autenticazione.</td><td class="prod"><span class="safety safe-read">✅ Account proprio</span></td><td class="result"></td></tr>
                            <tr data-id="AUTH-03"><td class="id-cell">AUTH-03</td><td class="scenario"><strong>Recupero password</strong><span>Usa soltanto l’account test e una casella email di cui il tester ha il controllo; verifica il link ricevuto.</span></td><td class="expected">Risposta chiara, email solo alla casella test e procedura di reimpostazione funzionante.</td><td class="prod"><span class="safety safe-caution">⚠️ Solo account/casella di test</span></td><td class="result"></td></tr>
                            <tr data-id="AUTH-04"><td class="id-cell">AUTH-04</td><td class="scenario"><strong>Account disattivato</strong><span>Con IT, prova l’accesso usando un account test già predisposto come inattivo; non disattivare un account operativo.</span></td><td class="expected">Accesso negato senza mostrare contenuti o informazioni riservate.</td><td class="prod"><span class="safety safe-caution">⚠️ Account di test predisposto</span></td><td class="result"></td></tr>
                            <tr data-id="AUTH-05"><td class="id-cell">AUTH-05</td><td class="scenario"><strong>Protezione da tentativi ripetuti</strong><span>Non provare password errate ripetutamente su un account reale: controlla solo la regola con IT o in staging.</span></td><td class="expected">Nessun account operativo viene bloccato o sottoposto a limitazioni durante il collaudo.</td><td class="prod"><span class="safety safe-stop">⛔ Non testare su account reali</span></td><td class="result"></td></tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
        <div class="no-results" id="no-results">Nessun test corrisponde ai filtri. Prova a cambiare reparto o parola chiave.</div>

        <div class="section-heading">
            <h2>Come registrare un risultato utile</h2>
        </div>
        <p class="footnote">Se trovi una differenza, annota ID test, ambiente, ruolo (mai la password), record di riferimento non sensibile, filtri, risultato sulla vecchia app, risultato sulla nuova app e impatto operativo. Non correggere dati reali durante il collaudo: interrompi il test e avvisa il referente. Gli esiti e le note inseriti in questa pagina sono salvati solo nel browser/dispositivo corrente.</p>
    </main>

    <script>
        (() => {
            const storageKey = "omnia-manual-regression-checklist-v1";
            const departments = [...document.querySelectorAll(".department")];
            const tests = [...document.querySelectorAll("tbody tr[data-id]")];
            const departmentFilter = document.querySelector("#department-filter");
            const search = document.querySelector("#search");
            const noResults = document.querySelector("#no-results");
            const progress = document.querySelector('[role="progressbar"]');

            let saved = {};
            try {
                saved = JSON.parse(localStorage.getItem(storageKey) || "{}");
                if (!saved || typeof saved !== "object" || Array.isArray(saved)) saved = {};
            } catch (error) {
                saved = {};
            }

            const safeLoad = (test) => {
                const state = saved[test.dataset.id] || {};
                const select = document.createElement("select");
                select.className = "status-select";
                select.setAttribute("aria-label", `Esito ${test.dataset.id}`);
                select.innerHTML = '<option value="">Da verificare</option><option value="pass">✅ PASS</option><option value="fail">❌ FAIL</option><option value="blocked">⏸ BLOCCATO</option><option value="na">— N/A</option>';
                select.value = state.status || "";

                const note = document.createElement("input");
                note.className = "note-input";
                note.type = "text";
                note.maxLength = 240;
                note.placeholder = "Differenza / riferimento";
                note.setAttribute("aria-label", `Nota ${test.dataset.id}`);
                note.value = typeof state.note === "string" ? state.note : "";

                const cell = test.querySelector(".result");
                cell.append(select, note);
                select.addEventListener("change", () => saveTest(test, select, note));
                note.addEventListener("input", () => saveTest(test, select, note));
            };

            const saveTest = (test, select, note) => {
                saved[test.dataset.id] = { status: select.value, note: note.value };
                try {
                    localStorage.setItem(storageKey, JSON.stringify(saved));
                } catch (error) {
                    // The checklist remains usable if browser storage is unavailable.
                }
                updateSummary();
            };

            const updateSummary = () => {
                const states = tests.map((test) => saved[test.dataset.id]?.status || "");
                const passed = states.filter((status) => status === "pass").length;
                const decided = states.filter((status) => ["pass", "fail", "blocked", "na"].includes(status)).length;
                const pending = states.filter((status) => status === "fail" || status === "blocked").length;
                const percentage = tests.length ? Math.round((decided / tests.length) * 100) : 0;
                document.querySelector("#total-count").textContent = String(tests.length);
                document.querySelector("#passed-count").textContent = String(passed);
                document.querySelector("#pending-count").textContent = String(pending);
                document.querySelector("#progress-label").textContent = `${percentage}%`;
                document.querySelector("#progress-fill").style.width = `${percentage}%`;
                progress.setAttribute("aria-valuenow", String(percentage));

                departments.forEach((department) => {
                    const rows = [...department.querySelectorAll("tbody tr[data-id]")];
                    const done = rows.filter((row) => ["pass", "fail", "blocked", "na"].includes(saved[row.dataset.id]?.status)).length;
                    const count = department.querySelector(".dept-count");
                    count.textContent = `${done}/${rows.length} completati`;
                });
            };

            departments.forEach((department) => {
                const option = document.createElement("option");
                option.value = department.dataset.department;
                option.textContent = department.querySelector("summary strong").textContent;
                departmentFilter.append(option);
            });
            tests.forEach(safeLoad);

            const filterRows = () => {
                const query = search.value.trim().toLocaleLowerCase("it");
                const selectedDepartment = departmentFilter.value;
                let visibleTests = 0;
                departments.forEach((department) => {
                    let visibleInDepartment = 0;
                    const departmentMatches = selectedDepartment === "all" || department.dataset.department === selectedDepartment;
                    department.querySelectorAll("tbody tr[data-id]").forEach((row) => {
                        const matchesText = !query || `${row.innerText} ${department.dataset.title}`.toLocaleLowerCase("it").includes(query);
                        const visible = departmentMatches && matchesText;
                        row.hidden = !visible;
                        if (visible) visibleInDepartment++;
                    });
                    department.hidden = visibleInDepartment === 0;
                    if (visibleInDepartment > 0) visibleTests += visibleInDepartment;
                });
                noResults.classList.toggle("visible", visibleTests === 0);
            };

            search.addEventListener("input", filterRows);
            departmentFilter.addEventListener("change", filterRows);
            document.querySelector("#print").addEventListener("click", () => window.print());
            document.querySelector("#expand-all").addEventListener("click", (event) => {
                const shouldOpen = departments.some((department) => !department.open && !department.hidden);
                departments.filter((department) => !department.hidden).forEach((department) => { department.open = shouldOpen; });
                event.currentTarget.textContent = shouldOpen ? "Chiudi reparti" : "Apri reparti";
            });

            filterRows();
            updateSummary();
        })();
    </script>
</body>
</html>
