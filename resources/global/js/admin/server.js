const labels = JSON.parse(window.labels);
const typeSelect = document.getElementById("type");
const button = document.getElementById("test-connection")
const resultContainer = document.getElementById("result-container");
const state = document.getElementById("state");
const statuscode = document.getElementById("statuscode");
const data = document.getElementById("data");

const text = button.innerText
typeSelect.addEventListener("change", (e) => {
    const selected = e.target.options[e.target.selectedIndex];
    const currentLabel = labels[selected.value];
    if (currentLabel){
        document.querySelector("label[for^=\"username\"]").innerHTML = currentLabel[0];
        document.querySelector("label[for^=\"password\"]").innerHTML = currentLabel[1];
    }
})
const selected = typeSelect.options[typeSelect.selectedIndex];
const currentLabel = labels[selected.value];
if (currentLabel){
    document.querySelector("label[for^=\"username\"]").innerHTML = currentLabel[0];
    document.querySelector("label[for^=\"password\"]").innerHTML = currentLabel[1];
}

button.addEventListener("click", (e) => {
    resultContainer.classList.add("hidden")
    button.innerHTML = '<i class="bi bi-gear-fill"></i> Test in progress...'
    button.disabled = true
    let DataQuery = new URLSearchParams({
        address: document.querySelector("input[name^=\"address\"]").value,
        type: document.querySelector("select[name=\"type\"]").value,
        username: document.querySelector("input[name^=\"username\"]").value,
        password: document.querySelector("input[name^=\"password\"]").value,
        port: document.querySelector("input[name^=\"port\"]").value,
        hostname: document.querySelector("input[name^=\"hostname\"]").value,
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
