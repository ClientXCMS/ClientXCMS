import '../clipboard.js'
const collapses = document.querySelectorAll('.hs-collapse-toggle');
Array.from(collapses).forEach((collapse) => {
    collapse.addEventListener('click', function (e) {
        const el = e.currentTarget;
            document.getElementById(el.dataset.target).classList.toggle('hidden');
            document.getElementById(el.dataset.target).classList.toggle('duration-300');
    })
})
