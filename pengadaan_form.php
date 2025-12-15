<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$edit_mode = isset($_GET['edit']);
$id_pengadaan = $edit_mode ? $_GET['edit'] : 0;

// Jika edit mode, ambil data pengadaan
if ($edit_mode) {
    $query = "SELECT * FROM pengadaan WHERE id_pengadaan = $id_pengadaan";
    $result = mysqli_query($conn, $query);
    $data_pengadaan = mysqli_fetch_assoc($result);
}

// Ambil data supplier aktif
$suppliers = mysqli_query($conn, "SELECT * FROM supplier WHERE status = 'aktif' ORDER BY nama_supplier");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Edit' : 'Tambah' ?> Pengadaan - Inventory</title>
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
            <h2><i class="bi bi-cart-plus"></i> <?= $edit_mode ? 'Edit' : 'Tambah' ?> Pengadaan</h2>
            <a href="pengadaan.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <!-- Form Header Pengadaan -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-file-text"></i> Data Pengadaan</h5>
            </div>
            <div class="card-body">
                <form id="formPengadaan">
                    <input type="hidden" name="id_pengadaan" value="<?= $id_pengadaan ?>">
                    <input type="hidden" name="id_user" value="<?= $_SESSION['user_id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No Pengadaan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="no_pengadaan" 
                                   value="<?= $edit_mode ? $data_pengadaan['no_pengadaan'] : 'PO-'.date('Ymd').'-'.rand(1000,9999) ?>" 
                                   readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Pengadaan <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_pengadaan" 
                                   value="<?= $edit_mode ? $data_pengadaan['tanggal_pengadaan'] : date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_supplier" id="supplier" required>
                                <option value="">-- Pilih Supplier --</option>
                                <?php while($sup = mysqli_fetch_assoc($suppliers)): ?>
                                <option value="<?= $sup['id_supplier'] ?>" <?= ($edit_mode && $data_pengadaan['id_supplier'] == $sup['id_supplier']) ? 'selected' : '' ?>>
                                    <?= $sup['kode_supplier'] ?> - <?= $sup['nama_supplier'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="2"><?= $edit_mode ? $data_pengadaan['catatan'] : '' ?></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Form Detail Pengadaan -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="bi bi-list-ul"></i> Detail Item Pengadaan</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Cari Produk</label>
                        <select class="form-select" id="cariProduk">
                            <option value="">-- Ketik nama produk untuk mencari --</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" id="qtyProduk" min="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Harga Beli</label>
                        <input type="number" class="form-control" id="hargaBeli" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="tambahItem()">
                            <i class="bi bi-plus-circle"></i> Tambah
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="tableDetail">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Kode Produk</th>
                                <th width="35%">Nama Produk</th>
                                <th width="10%">Qty</th>
                                <th width="15%">Harga Satuan</th>
                                <th width="15%">Subtotal</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailItems">
                            <!-- Items akan ditambahkan di sini via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Total Item:</strong></td>
                                <td class="text-end"><span id="totalItem">0</span> item</td>
                            </tr>
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-end">Rp <span id="subtotal">0</span></td>
                            </tr>
                            <tr>
                                <td><strong>PPN (10%):</strong></td>
                                <td class="text-end">Rp <span id="ppn">0</span></td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>GRAND TOTAL:</strong></td>
                                <td class="text-end"><strong>Rp <span id="grandTotal">0</span></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="button" class="btn btn-lg btn-success" onclick="simpanPengadaan()">
                        <i class="bi bi-save"></i> Simpan Permanen
                    </button>
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
            // Initialize Select2 untuk pencarian produk
            $('#cariProduk').select2({
                placeholder: '-- Ketik nama produk untuk mencari --',
                ajax: {
                    url: 'get_produk.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    }
                },
                minimumInputLength: 2
            });
            
            // Auto-fill harga beli saat produk dipilih
            $('#cariProduk').on('select2:select', function (e) {
                var data = e.params.data;
                if (data.harga_beli) {
                    $('#hargaBeli').val(data.harga_beli);
                }
            });
            
            // Load data jika edit mode
            <?php if ($edit_mode): ?>
            loadDetailPengadaan(<?= $id_pengadaan ?>);
            <?php endif; ?>
        });
        
        function loadDetailPengadaan(id) {
            $.get('get_detail_pengadaan.php?id=' + id, function(data) {
                items = JSON.parse(data);
                updateTable();
            });
        }
        
        function tambahItem() {
            const produkSelect = $('#cariProduk');
            const produkData = produkSelect.select2('data')[0];
            
            if (!produkData || !produkData.id) {
                alert('Pilih produk terlebih dahulu!');
                return;
            }
            
            const qty = parseInt($('#qtyProduk').val());
            const harga = parseFloat($('#hargaBeli').val());
            
            if (qty <= 0 || harga <= 0) {
                alert('Qty dan harga harus lebih dari 0!');
                return;
            }
            
            // Cek apakah produk sudah ada
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
                    harga_satuan: harga,
                    subtotal: qty * harga
                });
            }
            
            // Reset form
            produkSelect.val(null).trigger('change');
            $('#qtyProduk').val(1);
            $('#hargaBeli').val('');
            
            updateTable();
        }
        
        function hapusItem(index) {
            items.splice(index, 1);
            updateTable();
        }
        
        function updateTable() {
            let html = '';
            let totalItem = 0;
            let subtotal = 0;
            
            items.forEach((item, index) => {
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.kode_produk}</td>
                        <td>${item.nama_produk}</td>
                        <td>${item.qty}</td>
                        <td class="text-end">${formatRupiah(item.harga_satuan)}</td>
                        <td class="text-end">${formatRupiah(item.subtotal)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="hapusItem(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                totalItem++;
                subtotal += item.subtotal;
            });
            
            if (items.length === 0) {
                html = '<tr><td colspan="7" class="text-center text-muted">Belum ada item</td></tr>';
            }
            
            $('#detailItems').html(html);
            
            const ppn = subtotal * 0.1;
            const grandTotal = subtotal + ppn;
            
            $('#totalItem').text(totalItem);
            $('#subtotal').text(formatRupiah(subtotal));
            $('#ppn').text(formatRupiah(ppn));
            $('#grandTotal').text(formatRupiah(grandTotal));
        }
        
        function simpanPengadaan() {
            if (!$('#supplier').val()) {
                alert('Pilih supplier terlebih dahulu!');
                return;
            }
            
            if (items.length === 0) {
                alert('Tambahkan minimal 1 item!');
                return;
            }
            
            const formData = $('#formPengadaan').serialize();
            const dataToSend = formData + '&items=' + JSON.stringify(items);
            
            $.post('pengadaan_proses.php', dataToSend, function(response) {
                if (response.success) {
                    alert('Pengadaan berhasil disimpan!');
                    window.location.href = 'pengadaan.php?msg=success';
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                }
            }, 'json').fail(function() {
                alert('Terjadi kesalahan server!');
            });
        }
        
        function formatRupiah(angka) {
            return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>