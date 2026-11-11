// Livewire include Alpine e navigazione
import jsQR from 'jsqr';
import $ from 'jquery';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';

window.$ = window.jQuery = $;
select2(window, $);

function inizializzaSelect2Qr(radice = document) {
    const select = radice.matches?.('.select2-qr') ? [radice] : radice.querySelectorAll?.('.select2-qr');

    $(select ?? []).each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({ width: '100%' });
        }
    });
}

function distruggiSelect2Qr(radice) {
    const select = radice.matches?.('.select2-qr') ? [radice] : radice.querySelectorAll?.('.select2-qr');

    $(select ?? []).each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }
    });
}

function collegaSelect2Qr() {
    if (!window.Livewire || window.select2QrCollegato) return;

    window.select2QrCollegato = true;
    window.Livewire.hook('morphed', ({ el }) => inizializzaSelect2Qr(el));
    window.Livewire.hook('morph.removing', ({ el }) => distruggiSelect2Qr(el));
    inizializzaSelect2Qr();
}

document.addEventListener('livewire:init', collegaSelect2Qr);
document.addEventListener('livewire:initialized', collegaSelect2Qr);
collegaSelect2Qr();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('scanner', () => ({
        camera: false, stream: null, caricamento: false, errore: null,
        gps: 'attesa', lat: null, lng: null, codice: null,

        init() { this.posizione(); },

        posizione() {
            if (!navigator.geolocation) { this.gps = 'assente'; return; }
            this.gps = 'attesa';
            navigator.geolocation.getCurrentPosition(
                p => { this.lat = p.coords.latitude; this.lng = p.coords.longitude; this.gps = 'ok'; },
                () => { this.gps = 'negato'; },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 });
        },

        async conferma() {
            if (!this.codice || this.caricamento) return;
            this.caricamento = true;
            this.errore = null;
            try {
                await this.$wire.timbra(this.codice, this.lat, this.lng, false, this.$refs.nota?.value ?? '');
                this.codice = null;
                if (this.$refs.nota) this.$refs.nota.value = '';
            } catch {
            } finally { this.caricamento = false; }
        },

        annulla() {
            this.codice = null;
            if (this.$refs.nota) this.$refs.nota.value = '';
        },

        async avvia() {
            this.errore = null;
            if (!navigator.mediaDevices?.getUserMedia) { this.errore = 'Fotocamera non disponibile: serve una connessione HTTPS.'; return; }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            } catch (e) {
                this.errore = 'Accesso alla fotocamera negato. Consentilo dalle impostazioni del browser.';
                return;
            }
            this.camera = true;
            await this.$nextTick();
            const video = this.$refs.video;
            video.srcObject = this.stream;
            await video.play();
            this.ciclo();
        },

        async ciclo() {
            const video = this.$refs.video;
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            const nativo = 'BarcodeDetector' in window ? new BarcodeDetector({ formats: ['qr_code'] }) : null;

            const passo = async () => {
                if (!this.camera) return;
                let valore = null;
                if (video.readyState >= 2) {
                    if (nativo) {
                        valore = (await nativo.detect(video).catch(() => []))[0]?.rawValue ?? null;
                    } else {
                        const w = Math.min(video.videoWidth, 640);
                        canvas.width = w; canvas.height = Math.round(w * video.videoHeight / video.videoWidth);
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        valore = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' })?.data ?? null;
                    }
                }
                if (valore && !this.codice) {
                    this.ferma();
                    navigator.vibrate?.(80);
                    this.codice = valore;
                    return;
                }
                requestAnimationFrame(passo);
            };
            passo();
        },

        ferma() {
            this.camera = false;
            this.stream?.getTracks().forEach(t => t.stop());
            this.stream = null;
        },
    }));
});
