const labels = JSON.parse(window.labels);
const typeSelect = document.getElementById("type");
const button = document.getElementById("test-connection")
const resultContainer = document.getElementById("result-container");
const state = document.getElementById("state");
const statuscode = document.getElementById("statuscode");
const data = document.getElementById("data");
const hostnameInputContainer = document.getElementById("hostname-input-container");
const registrarSelectContainer = document.getElementById("registrar-select-container");
const hostnameInput = document.querySelector("[data-server-hostname-input]");
const registrarSelect = document.querySelector("[data-server-registrar-select]");
const portContainer = document.getElementById("port-container");
const portInput = portContainer?.querySelector("input[name=\"port\"]");
const testModeContainer = document.getElementById("test-mode-container");
const testModeInput = testModeContainer?.querySelector("input[name=\"test_mode\"]");

function updateDomainFields() {
    const isDomain = typeSelect.value === "domain";

    hostnameInputContainer.classList.toggle("hidden", isDomain);
    hostnameInputContainer.classList.toggle("flex", !isDomain);
    hostnameInput.disabled = isDomain;
    registrarSelectContainer.classList.toggle("hidden", !isDomain);
    registrarSelectContainer.classList.toggle("flex", isDomain);
    registrarSelect.disabled = !isDomain;
    portContainer.classList.toggle("hidden", isDomain);
    portInput.disabled = isDomain;
    testModeContainer.classList.toggle("hidden", !isDomain);
    testModeContainer.classList.toggle("flex", isDomain);
    testModeInput.disabled = !isDomain;
}

const text = button.innerText
typeSelect.addEventListener("change", (e) => {
    const selected = e.target.options[e.target.selectedIndex];
    const currentLabel = labels[selected.value];
    if (currentLabel){
        document.querySelector("label[for^=\"username\"]").innerHTML = currentLabel[0];
        document.querySelector("label[for^=\"password\"]").innerHTML = currentLabel[1];
    }
    updateDomainFields();
})
const selected = typeSelect.options[typeSelect.selectedIndex];
const currentLabel = labels[selected.value];
if (currentLabel){
    document.querySelector("label[for^=\"username\"]").innerHTML = currentLabel[0];
    document.querySelector("label[for^=\"password\"]").innerHTML = currentLabel[1];
}
updateDomainFields();

button.addEventListener("click", (e) => {
    resultContainer.classList.add("hidden")
    button.innerHTML = '<i class="bi bi-gear-fill"></i> Test in progress...'
    button.disabled = true
    let DataQuery = new URLSearchParams({
        address: document.querySelector("input[name^=\"address\"]").value,
        type: document.querySelector("select[name=\"type\"]").value,
        username: document.querySelector("input[name^=\"username\"]").value,
        password: document.querySelector("input[name^=\"password\"]").value,
        port: portInput.disabled ? "" : portInput.value,
        hostname: document.querySelector("[name=\"hostname\"]:not(:disabled)").value,
        test_mode: testModeInput && !testModeInput.disabled && testModeInput.checked ? "1" : "0",
    });

    e.preventDefault()
    fetch(button.dataset.fetch + DataQuery).then((response) => {
        response.json().then((json) => {

            if (response.status === 200) {
                resultContainer.classList.remove("hidden")
                const icon = json.success ? '<i class="bi bi-check text-green-500"></i>' : '<i class="bi bi-exclamation-lg text-red-500"></i>'
                state.innerHTML = icon
                statuscode.innerHTML = json.status + " " + icon
                data.innerText = json.message;

            } else if (response.status === 500) {
                data.innerText = json.message;
            }
        }).catch((err) => {
            response.text().then((text) => {
                data.innerText = text;
            });
        }).finally(() => {
            button.innerHTML = '<i class="bi bi-gear-fill"></i> Test closed'
            button.disabled = false
            button.disabled = false;
            button.innerHTML = text;
        })
    })
})
