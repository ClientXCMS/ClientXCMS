document.querySelector('#test-connection').addEventListener('click', function (e) {
    e.preventDefault();
    const button = document.querySelector('#test-connection');
    const text = button.innerText;
    button.innerHTML = 'Test in progress...';
    button.disabled = true;
    document.getElementById('successTest').classList.add('hidden');
    document.getElementById('failedTest').classList.add('hidden');
    fetch(button.dataset.url).then(function (response) {
        if (response.ok) {
            document.getElementById('successTest').classList.remove('hidden');
        } else{
            document.getElementById('failedTest').classList.remove('hidden');
            response.text().then(function (text) {
                document.getElementById('failedTest').innerHTML = text;
            })
        }
    }).finally(function () {
        button.innerHTML = '<i class="bi bi-gear-fill"></i> Test closed';
        button.disabled = false;
        button.disabled = false;
        button.innerHTML = text;
    });
});
