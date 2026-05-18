document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('wallet-recharge-form');
    const inputImporto = document.getElementById('wallet-importo');
    const submitBtn = document.getElementById('wallet-submit-btn');
    const feedbackBox = document.getElementById('wallet-feedback');
    const balanceElement = document.getElementById('wallet-balance');

    if (!form || !inputImporto || !submitBtn || !feedbackBox || !balanceElement) {
        return;
    }

    function showFeedback(message, isSuccess) {
        feedbackBox.style.display = 'block';
        feedbackBox.textContent = message;
        feedbackBox.style.backgroundColor = isSuccess ? '#e8f7ee' : '#fdecea';
        feedbackBox.style.color = isSuccess ? '#1e6b3a' : '#b42318';
        feedbackBox.style.border = isSuccess
            ? '1px solid #b7dfc6'
            : '1px solid #f5c2c0';
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const importo = parseFloat(inputImporto.value);

        if (isNaN(importo) || importo <= 0) {
            showFeedback('Inserisci un importo valido maggiore di 0.', false);
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Ricarica in corso...';

        try {
            const formData = new FormData();
            formData.append('importo', importo);

            const response = await fetch('ricarica_wallet_ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                balanceElement.textContent = '€ ' + data.nuovo_saldo;
                inputImporto.value = '';
                showFeedback(data.message, true);
            } else {
                showFeedback(data.message || 'Operazione non riuscita.', false);
            }
        } catch (error) {
            showFeedback('Errore di comunicazione con il server.', false);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Ricarica saldo';
        }
    });
});