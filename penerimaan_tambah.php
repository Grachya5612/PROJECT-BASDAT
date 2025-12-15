<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$id_pengadaan = isset($_GET['id_pengadaan']) ? $_GET['id_pengadaan'] : 0;

if ($id_pengadaan == 0) {
    header('Location: penerimaan_form.php');
    exit();
}

// Ambil data pengadaan
$query = "SELECT p.*, s.nama_supplier 
          FROM pengadaan p
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          WHERE p.id_pengadaan = $id_pengadaan";
$result = mysqli_query($conn, $query);
$pengadaan = mysqli_fetch_assoc($result);

// Ambil detail pengadaan
$query_detail = "SELECT dp.*, p.kode_produk, p.nama_produk
                 FROM detail_pengadaan dp
                 LEFT JOIN produk p ON dp.id_produk = p.id_produk
                 WHERE dp.id_pengadaan = $id_pengadaan";
$detail = mysqli_query($conn, $query_detail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Penerimaan - Inventory</title>
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
            <h2><i class="bi bi-box-arrow-in-down"></i> Tambah Penerimaan Barang</h2>
            <a href="penerimaan.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <!-- Form Penerimaan -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="bi bi-file-text"></i> Data Penerimaan</h5>
            </div>
            <div class="card-body">
                <form id="formPenerimaan">
                    <input type="hidden" name="id_user" value="<?= $_SESSION['user_id'] ?>">
                    <input type="hidden" name="id_pengadaan" value="<?= $id_pengadaan ?>">
                    <input type="hidden" name="id_supplier" value="<?= $pengadaan['id_supplier'] ?>">
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">No Penerimaan</label>
                            <input type="text" class="form-control" name="no_penerimaan" 
                                   value="GR-<?= date('Ymd') ?>-<?= rand(1000,9999) ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Penerimaan</label>
                            <input type="date" class="form-control" name="tanggal_penerimaan" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">No Pengadaan</label>
                            <input type="text" class="form-control" value="<?= $pengadaan['no_pengadaan'] ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" value="<?= $pengadaan['nama_supplier'] ?>" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="2"></textarea>
                        </div>
                    </div>
                </form>

                <!-- Detail Barang yang Dipesan -->
                <h5 class="mt-4 mb-3">Detail Barang yang Dipesan</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Silakan input jumlah barang yang diterima. Data belum tersimpan sampai Anda klik tombol "Simpan Permanen".
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
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
                        <tbody id="tableItems">
                            <?php 
                            $no = 1;
                            $items_data = [];
                            while ($item = mysqli_fetch_assoc($detail)):
                                $items_data[] = [
                                    'id_produk' => $item['id_produk'],
                                    'qty_dipesan' => $item['qty'],
                                    'qty_diterima' => $item['qty'],
                                    'kondisi' => 'baik',
                                    'keterangan' => ''
                                ];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $item['kode_produk'] ?></td>
                                <td><?= $item['nama_produk'] ?></td>
                                <td class="text-center"><strong><?= $item['qty'] ?></strong></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm qty-input" 
                                           value="<?= $item['qty'] ?>" min="0" max="<?= $item['qty'] ?>"
                                           data-index="<?= $no-2 ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm kondisi-select" data-index="<?= $no-2 ?>">
                                        <option value="baik" selected>Baik</option>
                                        <option value="rusak">Rusak</option>
                                        <option value="cacat">Cacat</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm keterangan-input" 
                                           placeholder="Opsional" data-index="<?= $no-2 ?>">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Total Item:</strong></td>
                                <td class="text-end"><span id="totalItem"><?= mysqli_num_rows($detail) ?></span> item</td>
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
        let items = <?= json_encode($items_data) ?>;
        
        $(document).ready(function() {
            updateTotalQty();
            
            // Event listener untuk perubahan qty
            $('.qty-input').on('change', function() {
                const index = $(this).data('index');
                items[index].qty_diterima = parseInt($(this).val());
                updateTotalQty();
            });
            
            // Event listener untuk perubahan kondisi
            $('.kondisi-select').on('change', function() {
                const index = $(this).data('index');
                items[index].kondisi = $(this).val();
            });
            
            // Event listener untuk keterangan
            $('.keterangan-input').on('change', function() {
                const index = $(this).data('index');
                items[index].keterangan = $(this).val();
            });
        });
        
        function updateTotalQty() {
            let total = 0;
            items.forEach(item => {
                total += parseInt(item.qty_diterima);
            });
            $('#totalQty').text(total);
        }
        
        function simpanPenerimaan() {
            const formData = $('#formPenerimaan').serialize();
            const dataToSend = formData + '&items=' + JSON.stringify(items);
            
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