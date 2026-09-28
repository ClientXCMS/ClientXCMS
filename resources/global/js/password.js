
function generate_password(button) {
    const lower = "abcdefghijklmnopqrstuvwxyz";
    const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const symbols = "$@!%*?&";
    const length = Number.parseInt(button.dataset.passwordLength || "12", 10);
    const requiredSets = [lower];
    if (button.dataset.passwordMixedCase === "true") requiredSets.push(upper);
    if (button.dataset.passwordNumbers === "true") requiredSets.push(numbers);
    if (button.dataset.passwordSymbols === "true") requiredSets.push(symbols);
    const charset = lower + upper + numbers + symbols;
    const randomIndex = (maximum) => {
        const values = new Uint32Array(1);
        crypto.getRandomValues(values);
        return values[0] % maximum;
    };
    const characters = requiredSets.map((set) => set[randomIndex(set.length)]);
    while (characters.length < length) characters.push(charset[randomIndex(charset.length)]);
    for (let index = characters.length - 1; index > 0; index--) {
        const target = randomIndex(index + 1);
        [characters[index], characters[target]] = [characters[target], characters[index]];
    }
    const password = characters.join("");
    const form = button.closest('form');
    const inputs = form.querySelectorAll('.input-password');
    inputs.forEach((input) => {
        input.value = password;
        const button = input.closest('div').querySelector('button');
        if (button && input.type === 'password') {
            button.click();
        }
    });
}
const passwordBtn = document.querySelectorAll('.generate-password-btn');
passwordBtn.forEach((button) => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        generate_password(button);
    });
});
document.addEventListener('toggle.hs.toggle-select', (e) => {
    const input = document.getElementById(e.target.getAttribute('aria-controls') || '');
    if (input) {
        e.target.setAttribute('aria-pressed', input.type === 'text' ? 'true' : 'false');
    }
});
