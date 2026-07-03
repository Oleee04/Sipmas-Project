console.log("SIPMAS aktif");

document.addEventListener("DOMContentLoaded", function () {
    const filterForm = document.getElementById("filterPengaduanForm");
    const kategoriSelect = document.getElementById("filterKategori");
    const kategoriPreview = document.getElementById("kategoriFilterPreview");

    if (filterForm) {
        filterForm.addEventListener("submit", function () {
            const fields = filterForm.querySelectorAll("input[name], select[name]");

            fields.forEach(function (field) {
                if (field.value.trim() === "") {
                    field.disabled = true;
                }
            });
        });
    }

    if (!kategoriSelect || !kategoriPreview) {
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const kategoriValue = urlParams.get("kategori");

    if (!kategoriValue || kategoriValue.trim() === "") {
        kategoriPreview.textContent = "Semua kategori";
        return;
    }

    kategoriPreview.textContent = kategoriValue;

    const optionExists = Array.from(kategoriSelect.options).some(function (option) {
        return option.value === kategoriValue;
    });

    if (optionExists) {
        kategoriSelect.value = kategoriValue;
    }
});