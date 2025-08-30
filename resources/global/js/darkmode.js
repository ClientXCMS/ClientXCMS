const darkModeBtn = document.querySelector('#dark-mode-btn');
const darkModeSun = document.querySelector('#dark-mode-sun');
const darkModeMoon = document.querySelector('#dark-mode-moon');

/**
 * Darkmode switcher
 */
function darkmodeSwitcher() {
    document.querySelector('html').classList.toggle('dark');
    if (document.querySelector('html').classList.contains('dark')) {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.remove('hidden');
        darkModeMoon.classList.add('hidden');
    } else {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.add('hidden');
        darkModeMoon.classList.remove('hidden');
    }
    fetch(darkModeBtn.dataset.url);
}
if (darkModeBtn) {
    darkModeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        darkmodeSwitcher();
    });
}
