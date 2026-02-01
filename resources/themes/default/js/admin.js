import Swal from "sweetalert2";
window.Swal = Swal;
function isVisible(element) {
    if (!element) return false;
    const style = window.getComputedStyle(element);
    return style.display !== "none" && style.visibility !== "hidden" && element.offsetParent !== null;
}

function getVisibleSearchBar() {
    return [...document.querySelectorAll("#searchBar, #searchBarDesktop")].find(isVisible);
}

function getVisibleDropdownItems() {
    const searchBar = getVisibleSearchBar();
    if (searchBar) {
        if (searchBar.id === "searchBar") {
            return document.getElementById("dropdownItems");
        } else {
            return document.getElementById("dropdownItemsDesktop");
        }
    }
}

document.addEventListener("keydown", function (event) {
    if ((event.ctrlKey || event.metaKey) && (event.key === "/" || event.key === ":")) {
        event.preventDefault();
        const searchBar = getVisibleSearchBar();
        if (searchBar) searchBar.focus();
    }
});

document.querySelectorAll("#searchBar, #searchBarDesktop").forEach((searchBar) => {
    searchBar.addEventListener("input", async function (event) {
        const dropdownItems = getVisibleDropdownItems();

        const query = event.target.value;
        dropdownItems.innerHTML = "";

        if (query.length > 2) {
            try {
                const apiUrl = window.admin_config.intelligent_search_url + `?q=${encodeURIComponent(query)}`;
                const response = await fetch(apiUrl);
                const items = await response.json();

                if (items.length > 0) {
                    items.forEach((item) => {
                        const itemEl = document.createElement("div");
                        itemEl.classList.add(
                            "cursor-pointer",
                            "p-2",
                            "text-sm",
                            "text-gray-800",
                            "hover:bg-gray-100",
                            "rounded-lg",
                            "focus:outline-none",
                            "focus:bg-gray-100",
                            "dark:text-slate-200",
                            "dark:focus:bg-slate-400"
                        );

                        itemEl.innerHTML = `
                          <a href="${item.link}" class="text-primary">
                            ${item.title}
                          </a>
                        `;

                        dropdownItems.appendChild(itemEl);
                    });
                    dropdownItems.parentElement.style.display = "block";
                } else {
                    dropdownItems.parentElement.style.display = "none";
                }
            } catch (error) {
                console.error("Error fetching products:", error);
                dropdownItems.parentElement.style.display = "none";
            }
        } else {
            dropdownItems.parentElement.style.display = "none";
        }
    });

    searchBar.addEventListener("blur", function () {
        setTimeout(() => {
            const dropdownItems = getVisibleDropdownItems();
            dropdownItems.parentElement.style.display = "none";
        }, 200);
    });
    searchBar.addEventListener("focus", function () {
        if (dropdownItems.innerHTML.trim() !== "") {
            const dropdownItems = getVisibleDropdownItems();
            dropdownItems.parentElement.style.display = "block";
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const searchButton = document.getElementById("mobileSearchButton");
    const searchContainer = document.getElementById("mobileSearchContainer");
    const searchIcons = document.querySelectorAll(".searchIcons");
    searchButton.addEventListener("click", function () {
        searchIcons.forEach(function (searchIcon) {
            searchIcon.classList.toggle("hidden");
        });
        searchContainer.classList.toggle("hidden");
    });
});
document.querySelectorAll('.confirmation-popup').forEach(
    function (element) {
        element.addEventListener('submit', function (event) {
            event.preventDefault();
            confirmation(element).then((result) => {
                if (result.isConfirmed) {
                    element.submit();
                }
            });
        });
    }
)
function confirmation(element) {
    const text = element.getAttribute('data-text') ?? window.admin_config.doyouwantreally
    const icon = element.getAttribute('data-icon') ?? 'warning';
    const confirmButtonText = element.getAttribute('data-confirm-button-text') ?? window.admin_config.delete;
    const showCancelButton = element.getAttribute('data-show-cancel-button') ?? true;
    const cancelButtonText = element.getAttribute('data-cancel-button-text') ?? window.admin_config.cancel;
    const confirmButtonColor = element.getAttribute('data-confirm-button-color') ?? '#d33';
    return Swal.fire({
        text: text,
        icon: icon,
        confirmButtonText: confirmButtonText,
        showCancelButton: showCancelButton,
        cancelButtonText: cancelButtonText,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: '#3085d6',

    })
}
