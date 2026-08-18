document.addEventListener('DOMContentLoaded', function() {
    const jkSelect = document.getElementById('jenis_kelamin');
    const ukuranSelect = document.getElementById('ukuran');
    
    if (jkSelect && ukuranSelect) {
        // Fungsi untuk mengupdate opsi ukuran
        function updateUkuranOptions() {
            const jk = jkSelect.value;
            // Kosongkan opsi yang ada
            ukuranSelect.innerHTML = '';
            
            let options = [];
            
            if (jk === 'Laki-laki') {
                options = [55, 56, 57, 58, 59, 60];
            } else if (jk === 'Perempuan') {
                options = [58, 59, 60];
            } else {
                ukuranSelect.innerHTML = '<option value="">-- Pilih Jenis Kelamin Dahulu --</option>';
                return;
            }
            
            options.forEach(size => {
                const opt = document.createElement('option');
                opt.value = size;
                opt.textContent = size;
                ukuranSelect.appendChild(opt);
            });
        }

        // Panggil saat halaman dimuat
        updateUkuranOptions();

        // Panggil saat jenis kelamin berubah
        jkSelect.addEventListener('change', updateUkuranOptions);
    }
});

// Reusable function for filtering tables based on search input
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tr = table.getElementsByTagName("tr");

    // Loop through all table rows, and hide those who don't match the search query
    // Skip the first row (headers) if it's in thead, but getElementsByTagName gets all tr.
    // If the table uses <thead> and <tbody>, we can just search rows in <tbody>.
    const tbody = table.querySelector('tbody');
    const rows = tbody ? tbody.getElementsByTagName("tr") : tr;

    for (let i = 0; i < rows.length; i++) {
        const rowText = rows[i].textContent.toLowerCase();
        if (rowText.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}
