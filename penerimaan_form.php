<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$edit_mode = isset($_GET['edit']);
$id_penerimaan = $edit_mode ? $_GET['edit'] : 0;

// Jika edit mode
if ($edit_mode) {
    $query = "SELECT * FROM penerimaan WHERE id_penerimaan = $id_penerimaan";
    $result = mysqli_query($conn, $query);
    $data_penerimaan = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Edit' : 'Tambah' ?> Penerimaan - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .table-borderless td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container-fluid content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-arrow-in-down"></i> <?= $edit_mode ? 'Edit' : 'Tambah' ?> Penerimaan</h2>
            <a href="penerimaan.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <!-- Search Pengadaan -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-search"></i> Cari Pengadaan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" id="tgl_dari">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" id="tgl_sampai">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Pengadaan</label>
                        <select class="form-select" id="status_pengadaan">
                            <option value="">Semua Status</option>
                            <option value="disetujui" selected>Disetujui</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="cariPengadaan()">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </div>

                <div id="hasilPengadaan" class="mt-3"></div>
            </div>
        </div>

        <!-- Form Penerimaan -->
        <div class="card" id="formSection" style="display: none;">
            <div class="card-header bg-success text-white">
                <h5><i class="bi bi-file-text"></i> Data Penerimaan</h5>
            </div>
            <div class="card-body">
                <form id="formPenerimaan">
                    <input type="hidden" name="id_penerimaan" value="<?= $id_penerimaan ?>">
                    <input type="hidden" name="id_user" value="<?= $_SESSION['user_id'] ?>">
                    <input type="hidden" name="id_pengadaan" id="id_pengadaan">
                    <input type="hidden" name="id_supplier" id="id_supplier">
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">No Penerimaan</label>
                            <input type="text" class="form-control" name="no_penerimaan" 
                                   value="<?= $edit_mode ? $data_penerimaan['no_penerimaan'] : 'GR-'.date('Ymd').'-'.rand(1000,9999) ?>" 
                                   readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Penerimaan</label>
                            <input type="date" class="form-control" name="tanggal_penerimaan" 
                                   value="<?= $edit_mode ? $data_penerimaan['tanggal_penerimaan'] : date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">No Pengadaan</label>
                            <input type="text" class="form-control" id="display_no_pengadaan" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="display_supplier" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="2"><?= $edit_mode ? $data_penerimaan['catatan'] : '' ?></textarea>
                        </div>
                    </div>
                </form>

                <!-- Preview Detail (Frontend Only - Belum Insert) -->
                <h5 class="mt-4 mb-3">Preview Detail Barang yang Dipesan</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Silakan input jumlah barang yang diterima. Data belum tersimpan sampai Anda klik tombol "Simpan Permanen".
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablePreview">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Kode Produk</th>
                                <th width="30%">Nama Produk</th>
                                <th width="12%">Qty Dipesan</th>
                                <th width="12%">Qty Diterima</th>
                                <th width="13%">Kondisi</th>
                                <th width="13%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="previewItems"></tbody>
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
                                <td><strong>Total Qty Diterima:</strong></td>
                                <td class="text-end"><span id="totalQty">0</span> pcs</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="button" class="btn btn-lg btn-success" onclick="simpanPenerimaan()">
                        <i class="bi bi-save"></i> Simpan Permanen (Insert to DB)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let detailItems = [];
        
        function cariPengadaan() {
            const tglDari = $('#tgl_dari').val();
            const tglSampai = $('#tgl_sampai').val();
            const status = $('#status_pengadaan').val();
            
            $.get('get_pengadaan.php', {
                tgl_dari: tglDari,
                tgl_sampai: tglSampai,
                status: status
            }, function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped mt-3">';
                    html += '<thead><tr><th>No Pengadaan</th><th>Tanggal</th><th>Supplier</th><th>Total Item</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
                    
                    response.data.forEach(item => {
                        html += `<tr>
                            <td>${item.no_pengadaan}</td>
                            <td>${item.tanggal_pengadaan}</td>
                            <td>${item.nama_supplier}</td>
                            <td>${item.total_item} item</td>
                            <td><span class="badge bg-success">${item.status_pengadaan}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="pilihPengadaan(${item.id_pengadaan}, '${item.no_pengadaan}', ${item.id_supplier}, '${item.nama_supplier}')">
                                    <i class="bi bi-check-circle"></i> Pilih
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    $('#hasilPengadaan').html(html);
                } else {
                    $('#hasilPengadaan').html('<div class="alert alert-warning mt-3">Tidak ada pengadaan yang sesuai kriteria</div>');
                }
            }, 'json');
        }
        
        function pilihPengadaan(idPengadaan, noPengadaan, idSupplier, namaSupplier) {
            // Redirect langsung ke halaman tambah penerimaan dengan parameter pengadaan
            window.location.href = 'penerimaan_tambah.php?id_pengadaan=' + idPengadaan;
        }
        
        function tampilkanPreview() {
            let html = '';
            let totalItem = 0;
            let totalQty = 0;
            
            detailItems.forEach((item, index) => {
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.kode_produk}</td>
                        <td>${item.nama_produk}</td>
                        <td class="text-center"><strong>${item.qty_dipesan}</strong></td>
                        <td>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${item.qty_diterima}" min="0" max="${item.qty_dipesan}"
                                   onchange="updateQty(${index}, this.value)">
                        </td>
                        <td>
                            <select class="form-select form-select-sm" onchange="updateKondisi(${index}, this.value)">
                                <option value="baik" selected>Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="cacat">Cacat</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" 
                                   placeholder="Opsional" onchange="updateKeterangan(${index}, this.value)">
                        </td>
                    </tr>
                `;
                totalItem++;
                totalQty += parseInt(item.qty_diterima);
            });
            
            $('#previewItems').html(html);
            $('#totalItem').text(totalItem);
            $('#totalQty').text(totalQty);
        }
        
        function updateQty(index, value) {
            detailItems[index].qty_diterima = parseInt(value);
            updateTotalQty();
        }
        
        function updateKondisi(index, value) {
            detailItems[index].kondisi = value;
        }
        
        function updateKeterangan(index, value) {
            detailItems[index].keterangan = value;
        }
        
        function updateTotalQty() {
            let total = 0;
            detailItems.forEach(item => {
                total += parseInt(item.qty_diterima);
            });
            $('#totalQty').text(total);
        }
        
        function simpanPenerimaan() {
            if (!$('#id_pengadaan').val()) {
                alert('Pilih pengadaan terlebih dahulu!');
                return;
            }
            
            const formData = $('#formPenerimaan').serialize();
            const dataToSend = formData + '&items=' + JSON.stringify(detailItems);
            
            $.post('penerimaan_proses.php', dataToSend, function(response) {
                if (response.success) {
                    alert('Penerimaan berhasil disimpan dan stok berhasil diupdate!');
                    window.location.href = 'penerimaan.php?msg=success';
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                }
            }, 'json').fail(function() {
                alert('Terjadi kesalahan server!');
            });
        }
    </script>
</body>
</html>