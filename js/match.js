document.addEventListener('DOMContentLoaded', () => {
    const sectorSelect = document.getElementById('sector');
    const rowSelect = document.getElementById('row');
    const seatSelect = document.getElementById('seat');
    const priceInput = document.getElementById('price');
    const form = document.getElementById('ticketForm');

    const prices = {
        'A': { '1': 100, '2': 100, '3': 100 },
        'B': { '1': 70, '2': 70, '3': 70 },
        'C': { '1': 120, '2': 120, '3': 120 } // Esempio di prezzi diversi per settore
    };

    function updatePrice() {
        const sector = sectorSelect.value;
        const row = rowSelect.value;
        const price = prices[sector][row];
        priceInput.value = price ? `€${price}` : 'N/A';
    }

    sectorSelect.addEventListener('change', updatePrice);
    rowSelect.addEventListener('change', updatePrice);
    seatSelect.addEventListener('change', updatePrice);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        // Aggiungi qui la logica per confermare l'acquisto
        window.location.href = 'confirmation.html';
    });

    updatePrice(); // Inizializza il prezzo al caricamento
});
