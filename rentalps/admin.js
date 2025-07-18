$(document).ready(function() {
    const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // Fungsi untuk memuat semua data awal ke tabel
    function loadData() {
        $.ajax({
            url: 'admin_api.php',
            type: 'POST',
            data: { action: 'get_all_data' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Isi tabel konsol
                    let konsolHtml = '';
                    res.data.konsol.forEach(k => {
                        konsolHtml += `
                            <tr>
                                <td>${k.id}</td>
                                <td>${k.nama}</td>
                                <td>${k.ip_address}</td>
                                <td>${formatter.format(k.harga_per_menit)}</td>
                                <td><span class="status-chip status-${k.is_active == 1 ? 'aktif' : 'nonaktif'}">${k.is_active == 1 ? 'Aktif' : 'Non-Aktif'}</span></td>
                                <td>
                                    <button class="btn-edit" data-id="${k.id}" data-type="konsol">Edit</button>
                                    <button class="btn-hapus-konsol" data-id="${k.id}" data-nama="${k.nama}">Hapus</button>
                                </td>
                            </tr>`;
                    });
                    $('#tabel-konsol tbody').html(konsolHtml);
                    
                    // Isi tabel kasir
                    let kasirHtml = '';
                    res.data.kasir.forEach(k => {
                        kasirHtml += `
                            <tr>
                                <td>${k.id}</td>
                                <td>${k.username}</td>
                                <td>${k.nama_lengkap}</td>
                                <td>${k.role}</td>
                                <td>
                                    <button class="btn-edit" data-id="${k.id}" data-type="kasir">Edit</button>
                                    <button class="btn-hapus-kasir" data-id="${k.id}" data-nama="${k.nama_lengkap}">Hapus</button>
                                </td>
                            </tr>`;
                    });
                    $('#tabel-kasir tbody').html(kasirHtml);
                }
            }
        });
    }
    
    // Logika untuk navigasi Tab
    $('.admin-nav-link').on('click', function(e) {
        e.preventDefault();
        $('.admin-nav-link').removeClass('active');
        $(this).addClass('active');
        $('.admin-tab-content').removeClass('active');
        $($(this).attr('href')).addClass('active');
    });

    // --- MANAJEMEN KONSOL ---
    function clearKonsolForm() {
        $('#form-konsol')[0].reset();
        $('#konsol-id').val('');
        $('#form-konsol-title').text('Tambah Konsol Baru');
    }

    $('#form-konsol').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serializeArray();
        let action = $('#konsol-id').val() ? 'update_console' : 'add_console';
        formData.push({name: 'action', value: action});
        $.ajax({
            url: 'admin_api.php', type: 'POST', data: $.param(formData), dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    alert('Data konsol berhasil disimpan!');
                    clearKonsolForm();
                    loadData();
                } else {
                    alert('Gagal: ' + res.message);
                }
            }
        });
    });

    $('#btn-clear-konsol').on('click', clearKonsolForm);

    $('#tabel-konsol').on('click', '.btn-hapus-konsol', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        if (confirm(`Apakah Anda yakin ingin menghapus konsol "${nama}"?`)) {
            $.ajax({
                url: 'admin_api.php', type: 'POST', data: { action: 'delete_console', id: id }, dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        loadData();
                    } else {
                        alert('Gagal: ' + res.message);
                    }
                }
            });
        }
    });

    // --- MANAJEMEN KASIR ---
    function clearKasirForm() {
        $('#form-kasir')[0].reset();
        $('#kasir-id').val('');
        $('#form-kasir-title').text('Tambah Kasir Baru');
        $('#kasir-password').attr('placeholder', 'Password').prop('required', true);
    }

    $('#form-kasir').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serializeArray();
        let action = $('#kasir-id').val() ? 'update_user' : 'add_user';
        formData.push({name: 'action', value: action});
        $.ajax({
            url: 'admin_api.php', type: 'POST', data: $.param(formData), dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    alert('Data kasir berhasil disimpan!');
                    clearKasirForm();
                    loadData();
                } else {
                    alert('Gagal: ' + res.message);
                }
            }
        });
    });

    $('#btn-clear-kasir').on('click', clearKasirForm);

    $('#tabel-kasir').on('click', '.btn-hapus-kasir', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        if (confirm(`Apakah Anda yakin ingin menghapus kasir "${nama}"?`)) {
            $.ajax({
                url: 'admin_api.php', type: 'POST', data: { action: 'delete_user', id: id }, dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        loadData();
                    } else {
                        alert('Gagal: ' + res.message);
                    }
                }
            });
        }
    });

    // --- FUNGSI EDIT UNIVERSAL ---
    $('.admin-container').on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');

        if (type === 'konsol') {
            $.ajax({
                url: 'admin_api.php', type: 'POST', data: { action: 'get_console', id: id }, dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        const k = res.data;
                        $('#form-konsol-title').text('Edit Konsol: ' + k.nama);
                        $('#konsol-id').val(k.id);
                        $('#konsol-nama').val(k.nama);
                        $('#konsol-tipe').val(k.tipe);
                        $('#konsol-ip').val(k.ip_address);
                        $('#konsol-harga').val(k.harga_per_menit);
                        $('#konsol-status').val(k.is_active);
                        $('html, body').animate({ scrollTop: $("#form-konsol").offset().top }, 500);
                    }
                }
            });
        } else if (type === 'kasir') {
            $.ajax({
                url: 'admin_api.php', type: 'POST', data: { action: 'get_user', id: id }, dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        const k = res.data;
                        $('#form-kasir-title').text('Edit Kasir: ' + k.nama_lengkap);
                        $('#kasir-id').val(k.id);
                        $('#kasir-username').val(k.username);
                        $('#kasir-nama').val(k.nama_lengkap);
                        $('#kasir-role').val(k.role);
                        $('#kasir-password').attr('placeholder', '(Kosongkan jika tidak diubah)').prop('required', false);
                        $('html, body').animate({ scrollTop: $("#form-kasir").offset().top }, 500);
                    }
                }
            });
        }
    });

    // --- LAPORAN OMSET ---
    $('#form-laporan').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin_api.php', type: 'POST', data: $(this).serialize() + '&action=generate_report', dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let laporanHtml = '';
                    if (res.data.length > 0) {
                        res.data.forEach(d => {
                            laporanHtml += `
                                <tr>
                                    <td>${d.tanggal}</td>
                                    <td>${d.nama_kasir_selesai}</td>
                                    <td>${d.jumlah_transaksi}</td>
                                    <td>${formatter.format(d.total_omset)}</td>
                                </tr>`;
                        });
                    } else {
                        laporanHtml = '<tr><td colspan="4" style="text-align:center;">Tidak ada data untuk rentang tanggal ini.</td></tr>';
                    }
                    $('#tabel-laporan tbody').html(laporanHtml);
                    $('#grand-total').text(formatter.format(res.grand_total));
                }
            }
        });
    });
    
    // --- FUNGSI EKSPOR KE EXCEL ---
    $('#btn-export-xlsx').on('click', function() {
        const table = document.getElementById("tabel-laporan");
        if (!table || $('#tabel-laporan tbody tr').first().find('td').length < 2) {
            alert('Tidak ada data untuk diekspor. Silakan tampilkan laporan terlebih dahulu.');
            return;
        }
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Laporan Omset");
        const startDate = $('#start_date').val() || 'mulai';
        const endDate = $('#end_date').val() || 'akhir';
        const fileName = `Laporan_Omset_${startDate}_sampai_${endDate}.xlsx`;
        XLSX.writeFile(wb, fileName);
    });
    
    // Muat data awal saat halaman dibuka
    loadData();
});