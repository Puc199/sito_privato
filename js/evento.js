document.addEventListener('DOMContentLoaded', () => {
    const replicaSelect = document.getElementById('replica-select');
    const settoreSelect = document.getElementById('settore-select');
    const postiSpan = document.getElementById('posti-disponibili');
    const prezzoSpan = document.getElementById('prezzo-settore');

    if (!replicaSelect || !settoreSelect || !postiSpan || !prezzoSpan) return;

    replicaSelect.addEventListener('change', async function () {
        const idReplica = this.value;

        settoreSelect.disabled = true;
        settoreSelect.innerHTML = '<option value="">Caricamento...</option>';
        postiSpan.textContent = '-';
        prezzoSpan.textContent = '-';

        if (!idReplica) {
            settoreSelect.innerHTML = '<option value="">Seleziona prima una replica</option>';
            return;
        }

        try {
            const response = await fetch(`ajax_settori_replica.php?id_replica=${encodeURIComponent(idReplica)}`);
            const data = await response.json();

            if (!data.success || !data.settori || data.settori.length === 0) {
                settoreSelect.innerHTML = '<option value="">Nessun settore disponibile</option>';
                return;
            }

            settoreSelect.innerHTML = '<option value="">Seleziona un settore</option>';

            data.settori.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.nome_settore} - €${parseFloat(item.prezzo).toFixed(2)} - ${item.posti_disponibili} posti`;
                option.dataset.prezzo = item.prezzo;
                option.dataset.posti = item.posti_disponibili;
                settoreSelect.appendChild(option);
            });

            settoreSelect.disabled = false;
        } catch (error) {
            settoreSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
        }
    });

    settoreSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];

        if (!selected || !selected.value) {
            postiSpan.textContent = '-';
            prezzoSpan.textContent = '-';
            return;
        }

        postiSpan.textContent = selected.dataset.posti || '-';
        prezzoSpan.textContent = parseFloat(selected.dataset.prezzo || 0).toFixed(2);
    });
});