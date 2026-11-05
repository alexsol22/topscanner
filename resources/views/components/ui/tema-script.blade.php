<script>
    (function () {
        const applica = () => {
            const salvato = localStorage.getItem('tema');
            document.documentElement.classList.toggle('dark', salvato === 'scuro');
        };
        applica();
        document.addEventListener('livewire:navigated', applica);
    })();
</script>
