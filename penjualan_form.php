<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$edit_mode = isset($_GET['edit']);
$id_penjualan = $edit_mode ? $_GET['edit'] : 0;

if ($edit_mode) {
    $query = "SELECT * FROM penjualan WHERE id_penjualan = $id_penjualan";
    $result = mysqli_query($conn, $query);
    $data_penjualan = mysqli_fetch_assoc($result);
}

// Ambil data customer
$customers = mysqli_query($conn, "SELECT * FROM customer WHERE status = 'aktif' ORDER BY nama_customer");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Edit' : 'Tambah' ?> Penjualan - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .table-borderless td { vertical-align: middle; }
        .select2-container .select2-selection--single { height: 38px; padding: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cart-check"></i> <?= $edit_mode ? 'Edit' : 'Transaksi' ?> Penjualan</h2>
            <a href="penjualan.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Cari Produk -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-search"></i> Cari Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-select" id="cariProduk">
                                    <option value="">-- Ketik nama produk --</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" id="qtyProduk" min="1" value="1" placeholder="Qty">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="tambahItem()">
                                    <i class="bi bi-plus-circle"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Item -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-cart"></i> Keranjang Belanja</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="40%">Produk</th>
                                        <th width="15%">Harga</th>
                                        <th width="10%">Qty</th>
                                        <th width="10%">Diskon</th>
                                        <th width="15%">Subtotal</th>
                                        <th width="5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="detailItems"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Form Header -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-file-text"></i> Data Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <form id="formPenjualan">
                            <input type="hidden" name="id_penjualan" value="<?= $id_penjualan ?>">
                            <input type="hidden" name="id_user" value="<?= $_SESSION['user_id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">No Penjualan</label>
                                <input type="text" class="form-control" name="no_penjualan" 
                                       value="<?= $edit_mode ? $data_penjualan['no_penjualan'] : 'INV-'.date('Ymd').'-'.rand(1000,9999) ?>" 
                                       readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal</label>
                                <input type="date" class="form-control" name="tanggal_penjualan" 
                                       value="<?= $edit_mode ? $data_penjualan['tanggal_penjualan'] : date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_customer" required>
                                    <option value="">-- Pilih Customer --</option>
                                    <?php while($cust = mysqli_fetch_assoc($customers)): ?>
                                    <option value="<?= $cust['id_customer'] ?>" <?= ($edit_mode && $data_penjualan['id_customer'] == $cust['id_customer']) ? 'selected' : '' ?>>
                                        <?= $cust['kode_customer'] ?> - <?= $cust['nama_customer'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="metode_pembayaran">
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="kartu_kredit">Kartu Kredit</option>
                                    <option value="e-wallet">E-Wallet</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Total -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td>Total Item:</td>
                                <td class="text-end"><strong><span id="totalItem">0</span> item</strong></td>
                            </tr>
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-end">Rp <span id="subtotal">0</span></td>
                            </tr>
                            <tr>
                                <td>PPN (10%):</td>
                                <td class="text-end">Rp <span id="ppn">0</span></td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>GRAND TOTAL:</strong></td>
                                <td class="text-end"><h4>Rp <span id="grandTotal">0</span></h4></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label class="form-label">Jumlah Bayar</label>
                                    <input type="text" class="form-control form-control-lg" id="jumlahBayar" onkeyup="formatRupiahInput(this); hitungKembalian()" placeholder="0">
                                    <input type="hidden" id="jumlahBayarValue" value="0">
                                </td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>KEMBALIAN:</strong></td>
                                <td class="text-end"><h4>Rp <span id="kembalian">0</span></h4></td>
                            </tr>
                        </table>
                        <button type="button" class="btn btn-success btn-lg w-100" onclick="simpanPenjualan()">
                            <i class="bi bi-save"></i> SIMPAN & CETAK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let items = [];
        
        $(document).ready(function() {
            $('#cariProduk').select2({
                placeholder: '-- Ketik nama produk --',
                ajax: {
                    url: 'get_produk.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) { return { results: data }; }
                },
                minimumInputLength: 2
            });
            
            <?php if ($edit_mode): ?>
            loadDetailPenjualan(<?= $id_penjualan ?>);
            <?php endif; ?>
        });
        
        function loadDetailPenjualan(id) {
            $.get('get_detail_penjualan.php?id=' + id, function(data) {
                items = JSON.parse(data);
                updateTable();
            });
        }
        
        function tambahItem() {
            const produkSelect = $('#cariProduk');
            const produkData = produkSelect.select2('data')[0];
            
            if (!produkData) { alert('Pilih produk!'); return; }
            
            const qty = parseInt($('#qtyProduk').val());
            if (qty <= 0) { alert('Qty harus > 0!'); return; }
            
            const existingIndex = items.findIndex(item => item.id_produk == produkData.id);
            if (existingIndex >= 0) {
                items[existingIndex].qty += qty;
                items[existingIndex].subtotal = items[existingIndex].qty * items[existingIndex].harga_satuan;
            } else {
                items.push({
                    id_produk: produkData.id,
                    kode_produk: produkData.kode_produk,
                    nama_produk: produkData.nama_produk,
                    qty: qty,
                    harga_satuan: produkData.harga_jual,
                    diskon_item: 0,
                    subtotal: qty * produkData.harga_jual
                });
            }
            
            produkSelect.val(null).trigger('change');
            $('#qtyProduk').val(1);
            updateTable();
        }
        
        function hapusItem(index) {
            items.splice(index, 1);
            updateTable();
        }
        
        function updateQty(index, value) {
            items[index].qty = parseInt(value);
            items[index].subtotal = items[index].qty * items[index].harga_satuan - items[index].diskon_item;
            updateTable();
        }
        
        function updateDiskon(index, value) {
            items[index].diskon_item = parseFloat(value);
            items[index].subtotal = items[index].qty * items[index].harga_satuan - items[index].diskon_item;
            updateTable();
        }
        
        function updateTable() {
            let html = '';
            let totalItem = 0;
            let subtotal = 0;
            
            items.forEach((item, index) => {
                html += `<tr>
                    <td>${index + 1}</td>
                    <td>${item.nama_produk}</td>
                    <td>${formatRupiah(item.harga_satuan)}</td>
                    <td><input type="number" class="form-control form-control-sm" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)"></td>
                    <td><input type="number" class="form-control form-control-sm" value="${item.diskon_item}" min="0" onchange="updateDiskon(${index}, this.value)"></td>
                    <td class="text-end">${formatRupiah(item.subtotal)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="hapusItem(${index})"><i class="bi bi-trash"></i></button></td>
                </tr>`;
                totalItem++;
                subtotal += item.subtotal;
            });
            
            if (items.length === 0) html = '<tr><td colspan="7" class="text-center text-muted">Belum ada item</td></tr>';
            
            $('#detailItems').html(html);
            
            const ppn = subtotal * 0.1;
            const grandTotal = subtotal + ppn;
            
            $('#totalItem').text(totalItem);
            $('#subtotal').text(formatRupiah(subtotal));
            $('#ppn').text(formatRupiah(ppn));
            $('#grandTotal').text(formatRupiah(grandTotal));
            
            hitungKembalian();
        }
        
        function hitungKembalian() {
            const grandTotal = parseFloat($('#grandTotal').text().replace(/\./g, ''));
            const bayar = parseFloat($('#jumlahBayarValue').val()) || 0;
            const kembalian = bayar - grandTotal;
            $('#kembalian').text(formatRupiah(kembalian > 0 ? kembalian : 0));
        }
        
        function formatRupiahInput(input) {
            // Hapus semua karakter selain angka
            let angka = input.value.replace(/[^,\d]/g, '');
            
            // Simpan nilai asli tanpa format
            $('#jumlahBayarValue').val(angka);
            
            // Format dengan titik
            let formatted = angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            input.value = formatted;
        }
        
        function simpanPenjualan() {
            if (items.length === 0) { alert('Tambahkan minimal 1 item!'); return; }
            
            const grandTotal = parseFloat($('#grandTotal').text().replace(/\./g, ''));
            const bayar = parseFloat($('#jumlahBayarValue').val()) || 0;
            
            if (bayar < grandTotal) { alert('Jumlah bayar kurang!'); return; }
            
            const formData = $('#formPenjualan').serialize();
            const dataToSend = formData + '&items=' + JSON.stringify(items) + '&bayar=' + bayar + '&kembalian=' + (bayar - grandTotal);
            
            $.post('penjualan_proses.php', dataToSend, function(response) {
                if (response.success) {
                    alert('Penjualan berhasil disimpan!');
                    window.open('penjualan_invoice.php?id=' + response.id_penjualan, '_blank');
                    window.location.href = 'penjualan.php?msg=success';
                } else {
                    alert('Gagal: ' + response.message);
                }
            }, 'json');
        }
        
        function formatRupiah(angka) {
            return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>