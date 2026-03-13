// flash.js - gère le toggle du burger et la fermeture automatique des flash messages
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.nav-toggle');
    const header = document.querySelector('.site-header');
    if (toggle && header) {
        toggle.addEventListener('click', function () {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            this.classList.toggle('open');
            header.classList.toggle('nav-open');
        });
    }

    // Auto-dismiss flash messages après 7 secondes, et possibilité de fermer en cliquant
    const flashContainer = document.querySelector('.flash-container');
    if (flashContainer) {
        const flashes = flashContainer.querySelectorAll('.flash');
        flashes.forEach(function(flash){
            // fermer au clic
            flash.addEventListener('click', function(){ removeFlash(flash); });
            // timer de 7s
            setTimeout(function(){ removeFlash(flash); }, 7000);
        });
    }

    function removeFlash(el){
        if (!el || !el.parentNode) return;
        // animation de disparition
        el.style.transition = 'opacity 0.6s ease, transform 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        // suppression après la transition
        setTimeout(function(){ if (el && el.parentNode) el.remove(); }, 700);
    }
});
