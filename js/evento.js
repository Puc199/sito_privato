document.addEventListener('DOMContentLoaded', () => {
    const replicaButtons = document.querySelectorAll('.replica-button');
    const replicaRiepilogo = document.getElementById('replica-riepilogo');
    const sectorList = document.getElementById('sector-list');

    const purchaseSection = document.getElementById('purchase-section');
    const purchaseSettore = document.getElementById('purchase-settore');
    const purchasePrezzo = document.getElementById('purchase-prezzo');
    const purchasePrezzoInput = document.getElementById('purchase-prezzo-input');
    const purchasePosti = document.getElementById('purchase-posti');
    const selectedEventoSettore = document.getElementById('selected-evento-settore');
    const seatGrid = document.getElementById('seat-grid');

    if (!replicaButtons.length || !sectorList) return;

    replicaButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const idReplica = button.dataset.replicaId;
            const labelReplica = button.dataset.replicaLabel || '-';

            replicaButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            if (replicaRiepilogo) {
                replicaRiepilogo.textContent = labelReplica;
            }

            sectorList.innerHTML = `
                <div class="empty-state">
                    <h3>Caricamento settori...</h3>
                    <p>Attendi qualche istante.</p>
                </div>
            `;

            try {
                const response = await fetch(`ajax_settori_replica.php?id_replica=${encodeURIComponent(idReplica)}`);
                const data = await response.json();

                if (!data.success || !Array.isArray(data.settori) || data.settori.length === 0) {
                    sectorList.innerHTML = `
                        <div class="empty-state">
                            <h3>Nessun settore disponibile</h3>
                            <p>Questa replica non ha settori prenotabili.</p>
                        </div>
                    `;
                    return;
                }

                sectorList.innerHTML = '';

                data.settori.forEach(settore => {
                    const card = document.createElement('div');
                    card.className = 'match-card';

                    const prezzo = Number.parseFloat(settore.prezzo || 0).toFixed(2).replace('.', ',');

                    card.innerHTML = `
                        <div class="match-card-top">
                            <span class="match-badge">${settore.nome_settore}</span>
                            <span class="match-date">€ ${prezzo}</span>
                        </div>

                        <div class="match-details" style="padding-top: 18px;">
                            <h3>${settore.nome_settore}</h3>
                            <p>Posti disponibili: ${settore.posti_disponibili} / ${settore.posti_totali}</p>
                        </div>

                        <div class="match-card-bottom">
                            <button
                                type="button"
                                class="match-action sector-button"
                                data-settore-id="${settore.id}"
                                data-settore-nome="${settore.nome_settore}"
                                data-settore-prezzo="${settore.prezzo}"
                                data-settore-disponibili="${settore.posti_disponibili}"
                                data-settore-totali="${settore.posti_totali}"
                            >
                                Scegli questo settore
                            </button>
                        </div>
                    `;

                    sectorList.appendChild(card);
                });

                bindSectorButtons();
            } catch (error) {
                sectorList.innerHTML = `
                    <div class="empty-state">
                        <h3>Errore di caricamento</h3>
                        <p>Non è stato possibile recuperare i settori della replica selezionata.</p>
                    </div>
                `;
            }
        });
    });

    function bindSectorButtons() {
        const sectorButtons = document.querySelectorAll('.sector-button');

        sectorButtons.forEach(button => {
            button.addEventListener('click', () => {
                const settoreId = button.dataset.settoreId;
                const settoreNome = button.dataset.settoreNome;
                const settorePrezzo = Number.parseFloat(button.dataset.settorePrezzo || 0).toFixed(2).replace('.', ',');
                const settoreDisponibili = button.dataset.settoreDisponibili;
                const settoreTotali = parseInt(button.dataset.settoreTotali || 0, 10);

                if (purchaseSection) {
                    purchaseSection.style.display = '';
                }

                if (purchaseSettore) purchaseSettore.textContent = settoreNome;
                if (purchasePrezzo) purchasePrezzo.textContent = settorePrezzo;
                if (purchasePrezzoInput) purchasePrezzoInput.value = `€ ${settorePrezzo}`;
                if (purchasePosti) purchasePosti.value = settoreDisponibili;
                if (selectedEventoSettore) selectedEventoSettore.value = settoreId;

                if (seatGrid) {
                    seatGrid.innerHTML = '';
                    for (let i = 1; i <= settoreTotali; i++) {
                        const label = document.createElement('label');
                        label.className = 'seat-pill seat-available';
                        label.innerHTML = `
                            <input type="checkbox" name="posti[]" value="${i}">
                            <span>P${i}</span>
                        `;
                        seatGrid.appendChild(label);
                    }
                }

                purchaseSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    bindSectorButtons();
});