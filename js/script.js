/*  document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper('.swiper-container', {
        loop: true,
        spaceBetween: 30,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
});
window.onload = function() {
    fetch('session.php')
        .then(response => response.json())
        .then(data => {
            const userArea = document.getElementById('user-area');
            const loginLink = document.getElementById('login-link');
            if (data.logged_in) {
                loginLink.style.display = 'none';
                const userNameElement = document.createElement('li');
                // Aggiungi un gestore di eventi onclick al link dell'username
                userNameElement.innerHTML = `<a href="#" class="username-style" onclick="checkRoleAndRedirect(${data.ruolo})">${data.username}</a><a href="logout.php" class="logout-style">Logout</a>`;
                userArea.appendChild(userNameElement);
            }
        });
};

// Funzione per verificare il ruolo e reindirizzare
function checkRoleAndRedirect(ruolo) {
    if (ruolo === 1) {
        window.location.href = 'admin_dashboard.php'; // Sostituisci con il link effettivo della pagina admin
    } else if (ruolo === 2) {
        window.location.href = 'ricarica_saldo.php'; // Sostituisci con il link effettivo della pagina utente
    }
}

