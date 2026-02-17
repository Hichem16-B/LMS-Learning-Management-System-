// Gestion du timer
function startTimer(duration) {
    let timer = duration, minutes, seconds;
    const timerElement = document.getElementById('time');
    
    const interval = setInterval(() => {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        timerElement.textContent = minutes + ":" + seconds;

        // Changement de couleur quand il reste peu de temps
        if (timer <= 60) {
            timerElement.style.color = '#ffcc00';
        }
        if (timer <= 30) {
            timerElement.style.color = '#ff3333';
            timerElement.style.fontWeight = 'bold';
        }

        if (--timer < 0) {
            clearInterval(interval);
            alert("Temps écoulé ! Le test sera soumis automatiquement.");
            document.getElementById('quizForm').submit();
        }
    }, 1000);
}

// Détection de triche
let warningCount = 0;
const maxWarnings = 2;

function detectCheating() {
    warningCount++;
    document.body.classList.add('warning');
    
    if (warningCount === 1) {
        alert("Attention : Vous avez changé d'onglet. Ce comportement est enregistré.");
    } else if (warningCount >= maxWarnings) {
        alert("Triche détectée ! Le test sera soumis automatiquement.");
        document.getElementById('quizForm').submit();
    }
}

// Événements de détection
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        detectCheating();
    } else {
        document.body.classList.remove('warning');
    }
});

// Blocage des raccourcis
const blockedKeys = {
    'F12': true,
    'F5': true,
    'F11': true,
    'PrintScreen': true
};

const blockedCombinations = [
    { ctrl: true, shift: true, key: 'I' },
    { ctrl: true, shift: true, key: 'J' },
    { ctrl: true, shift: true, key: 'C' },
    { ctrl: true, key: 'U' }
];

document.addEventListener('keydown', (e) => {
    // Bloquer les touches simples
    if (blockedKeys[e.key]) {
        e.preventDefault();
        detectCheating();
        return;
    }
    
    // Bloquer les combinaisons
    for (const combo of blockedCombinations) {
        if ((!combo.ctrl || e.ctrlKey) &&
            (!combo.shift || e.shiftKey) &&
            e.key === combo.key) {
            e.preventDefault();
            detectCheating();
            return;
        }
    }
});

// Empêcher la copie
document.addEventListener('copy', e => {
    e.preventDefault();
    alert("La copie est désactivée pendant le test.");
    detectCheating();
});

// Empêcher le clic droit
document.addEventListener('contextmenu', e => {
    e.preventDefault();
    alert("Le clic droit est désactivé pendant le test.");
    detectCheating();
});

// Détection d'inspection d'éléments
let elementInspected = false;

const observer = new MutationObserver(() => {
    if (!elementInspected && document.body.style.cursor === 'text') {
        elementInspected = true;
        detectCheating();
    }
});

observer.observe(document.body, {
    attributes: true,
    attributeFilter: ['style']
});