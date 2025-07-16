const darkModeBtn = document.querySelector('#dark-mode-btn');
const darkModeSun = document.querySelector('#dark-mode-sun');
const darkModeMoon = document.querySelector('#dark-mode-moon');

/**
 * Darkmode switcher
 */
const setTheme = theme => {
    if (theme === 'auto') {
        document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'))
    } else {
        document.documentElement.setAttribute('data-bs-theme', theme)
    }
}
const showActiveTheme = (theme, focus = false) => {
    const themeSwitcher = document.querySelector('#bd-theme')

    if (!themeSwitcher) {
        return
    }

    const themeSwitcherText = document.querySelector('#bd-theme-text')
    const activeThemeIcon = document.querySelector('.theme-icon-active use')
    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
    const svgOfActiveBtn = btnToActive.querySelector('svg use').getAttribute('href')

    document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
        element.classList.remove('active')
        element.setAttribute('aria-pressed', 'false')
    })

    btnToActive.classList.add('active')
    btnToActive.setAttribute('aria-pressed', 'true')
    activeThemeIcon.setAttribute('href', svgOfActiveBtn)
    const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`
    themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

    if (focus) {
        themeSwitcher.focus()
    }
}
function darkmodeSwitcher() {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    if (isDark) {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.remove('d-none');
        darkModeMoon.classList.add('d-none');
        showActiveTheme('light', false)
        document.documentElement.setAttribute('data-bs-theme', 'light');
    } else {
        if (darkModeSun == null){
            return;
        }
        darkModeSun.classList.add('d-none');
        darkModeMoon.classList.remove('d-none');
        showActiveTheme('dark', false)
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    }
    fetch(darkModeBtn.dataset.url);

}
if (darkModeBtn) {
    darkModeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        darkmodeSwitcher();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme')
    setTheme(currentTheme)
    showActiveTheme(currentTheme);
});
