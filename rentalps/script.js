$(document).ready(function() {
    const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
    
    // (BARU) Variabel untuk menyimpan sisa waktu setiap konsol di sisi browser
    let clientSideTimers = {};

    function updateJam() {
        const now = new Date();
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');
        $('#jam-sekarang').text(`${jam}:${menit}:${detik}`);
    }

    function formatWaktu(totalDetik) {
        if (totalDetik < 0) totalDetik = 0;
        const jam = Math.floor(totalDetik / 3600);
        const menit = Math.floor((totalDetik % 3600) / 60);
        const detik = totalDetik % 60;
        return [jam, menit, detik].map(v => v < 10 ? "0" + v : v).join(":");
    }

    // (BARU) Fungsi untuk menjalankan timer di sisi browser setiap detik
    function updateClientTimers() {
        for (const id in clientSideTimers) {
            if (clientSideTimers[id] > 0) {
                clientSideTimers[id]--; // Kurangi 1 detik
                const card = $('#konsol-' + id);
                if (card.length) {
                    card.find('.sisa-waktu').text(formatWaktu(clientSideTimers[id]));
                }
            } else {
                // Jika waktu habis di browser, panggil fetchStatus untuk konfirmasi ke server
                delete clientSideTimers[id];
                fetchStatus();
            }
        }
    }


    // Fungsi ini sekarang bertugas sebagai sinkronisasi
    function fetchStatus() {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'get_status' },
            dataType: 'json',
            success: function(response) {
                if (response.status !== 'success') { console.error("API Error:", response.message); return; }

                $.each(response.data, function(id, data) {
                    const card = $('#konsol-' + id);
                    if(card.length === 0) return;
                    
                    card.data('logid', data.log_id);

                    const statusDiv = card.find('.status');
                    const statusText = card.find('.status-text');
                    const infoSewa = card.find('.info-sewa');
                    const sisaWaktuSpan = card.find('.sisa-waktu');
                    const biayaDiv = card.find('.biaya');
                    const formSewa = card.find('.form-sewa');
                    const formTambahWaktu = card.find('.form-tambah-waktu');
                    const tombolHentikan = card.find('.tombol-hentikan');

                    infoSewa.hide();
                    biayaDiv.hide();
                    formSewa.hide();
                    formTambahWaktu.hide();
                    tombolHentikan.hide();

                    switch (data.status_display) {
                        case 'mati':
                        case 'menyala_standby':
                            statusText.text(data.status_display === 'mati' ? 'TV MATI' : 'TV MENYALA (STANDBY)');
                            statusDiv.attr('data-status', data.status_display === 'mati' ? 'mati' : 'menyala');
                            formSewa.show();
                            delete clientSideTimers[id]; // Hapus dari timer lokal
                            break;

                        case 'menyala_berjalan':
                            statusDiv.attr('data-status', 'menyala');
                            statusText.text('SEWA BERJALAN');
                            infoSewa.show();
                            tombolHentikan.show();
                            // (PERUBAIKAN) Sinkronisasi timer lokal dengan data server
                            clientSideTimers[id] = data.sisa_waktu;
                            sisaWaktuSpan.text(formatWaktu(data.sisa_waktu));
                            break;

                        case 'waktu_habis':
                            statusDiv.attr('data-status', 'selesai');
                            statusText.text('WAKTU HABIS');
                            sisaWaktuSpan.text('00:00:00');
                            const totalBiaya = data.durasi_total * data.harga_per_menit;
                            card.find('.total-biaya').text(formatter.format(totalBiaya));
                            infoSewa.show();
                            biayaDiv.show();
                            formTambahWaktu.show();
                            delete clientSideTimers[id]; // Hapus dari timer lokal
                            break;
                            
                        case 'nonaktif':
                            statusDiv.attr('data-status', 'mati');
                            statusText.text('KONSOL NON-AKTIF');
                            delete clientSideTimers[id];
                            break;

                        default:
                            statusText.text('STATUS ERROR');
                            delete clientSideTimers[id];
                            break;
                    }
                });
            },
            error: function() { console.error("Gagal terhubung untuk sinkronisasi status."); }
        });
    }


    // --- EVENT HANDLERS DENGAN LOGIKA ANTI-KLIK-GANDA ---

    $('.form-sewa').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const card = form.closest('.konsol-card');
        const button = form.find('button[type="submit"]');
        const id_konsol = card.data('idkonsol');
        const durasi = card.find('.input-menit').val();
        if (!durasi || durasi < 1) { alert("Masukkan durasi yang valid."); return; }
        button.prop('disabled', true).text('MEMPROSES...');
        $.ajax({
            url: 'api.php', type: 'POST', data: { action: 'start_rental', id_konsol: id_konsol, durasi: durasi }, dataType: 'json',
            success: function(res) { if(res.status !== 'success') { alert('Gagal: ' + res.message); } },
            error: function() { alert('Gagal terhubung ke server.'); },
            complete: function() { button.prop('disabled', false).text('START'); card.find('.input-menit').val(''); fetchStatus(); }
        });
    });

    $('.form-tambah-waktu').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const card = form.closest('.konsol-card');
        const button = form.find('button[type="submit"]');
        const id_konsol = card.data('idkonsol');
        const log_id = card.data('logid');
        const durasi = card.find('.input-menit-tambahan').val();
        if (!durasi || durasi < 1) { alert("Masukkan durasi tambahan yang valid."); return; }
        button.prop('disabled', true).text('MEMPROSES...');
        $.ajax({
            url: 'api.php', type: 'POST', data: { action: 'add_time', id_konsol: id_konsol, log_id: log_id, durasi: durasi }, dataType: 'json',
            success: function(res) { if(res.status !== 'success') { alert('Gagal: ' + res.message); } },
            error: function() { alert('Gagal terhubung ke server.'); },
            complete: function() { button.prop('disabled', false).text('TAMBAH WAKTU'); card.find('.input-menit-tambahan').val(''); fetchStatus(); }
        });
    });
    
    $('.container').on('click', '.tombol-hentikan', function() {
        const button = $(this);
        if (!confirm('Anda yakin ingin menghentikan sewa ini sekarang?')) return;
        button.prop('disabled', true).text('MENGHENTIKAN...');
        const log_id = button.closest('.konsol-card').data('logid');
        $.ajax({
            url: 'api.php', type: 'POST', data: { action: 'force_stop_session', log_id: log_id }, dataType: 'json',
            success: function(res) { if (res.status !== 'success') { alert('Gagal: ' + res.message); } },
            error: function() { alert('Gagal terhubung ke server.'); },
            complete: function() { fetchStatus(); }
        });
    });

    $('.container').on('click', '.tombol-selesai', function() {
        const button = $(this);
        if (!confirm('Pastikan pembayaran sudah diterima. Lanjutkan?')) return;
        button.prop('disabled', true).text('MEMPROSES...');
        const log_id = button.closest('.konsol-card').data('logid');
        $.ajax({
            url: 'api.php', type: 'POST', data: { action: 'finish_session', log_id: log_id }, dataType: 'json',
            success: function(res) { if (res.status !== 'success') { alert('Gagal: ' + res.message); } },
            error: function() { alert('Gagal terhubung ke server.'); },
            complete: function() { fetchStatus(); }
        });
    });

    // === LOGIKA UNTUK MODAL LAPORAN HARIAN (LENGKAP) ===
    $('#btn-laporan-harian').on('click', function() {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'get_daily_report' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let laporanHtml = '';
                    if (res.data.length > 0) {
                        res.data.forEach(d => {
                            laporanHtml += `<tr>
                                <td>${d.nama_kasir_selesai || 'N/A'}</td>
                                <td>${d.jumlah_transaksi}</td>
                                <td>${formatter.format(d.total_omset)}</td>
                            </tr>`;
                        });
                    } else {
                        laporanHtml = '<tr><td colspan="3" style="text-align:center;">Belum ada omset tercatat hari ini.</td></tr>';
                    }
                    $('#laporan-harian-body').html(laporanHtml);
                    $('#grand-total-harian').text(formatter.format(res.grand_total));
                    $('#modal-laporan').addClass('active');
                } else {
                    alert('Gagal mengambil laporan: ' + res.message);
                }
            }
        });
    });

    // Event handler untuk menutup modal
    $('.modal-close').on('click', function() {
        $('#modal-laporan').removeClass('active');
    });
    $('#modal-laporan').on('click', function(e) {
        if ($(e.target).is('.modal-overlay')) {
            $(this).removeClass('active');
        }
    });

    // --- INISIALISASI ---
    updateJam();
    setInterval(updateJam, 1000);
    fetchStatus();
    setInterval(fetchStatus, 30000);
    setInterval(updateClientTimers, 1000);
});