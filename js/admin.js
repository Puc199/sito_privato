document.addEventListener("DOMContentLoaded", function() {
    const matchSelect = document.getElementById('match-select');

    // Popola il menu a tendina delle partite
    fetch('admin.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(match => {
                const option = document.createElement('option');
                option.value = match.id;
                option.text = `${match.Squadra_C} vs ${match.Squadra_T} - ${match.Data_partita}`;
                matchSelect.add(option);
            });
        })
        .catch(error => console.error('Errore nel recupero delle partite:', error));
});
