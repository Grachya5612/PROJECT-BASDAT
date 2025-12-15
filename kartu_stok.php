<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Filter
$filter_produk = isset($_GET['produk']) ? $_GET['produk'] : '';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');

// Query kartu stok
$query = "SELECT ks.*, p.kode_produk, p.nama_produk, s.singkatan
          FROM kartu_stok ks
          LEFT JOIN produk p ON ks.id_produk = p.id_produk
          LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
          WHERE ks.tanggal BETWEEN '$tgl_dari' AND '$tgl_sampai'";

if ($filter_produk) {
    $query .= " AND ks.id_produk = $filter_produk";
}
if ($filter_jenis) {
    $query .= " AND ks.jenis_transaksi = '$filter_jenis'";
}

$query .= " ORDER BY ks.tanggal DESC, ks.id_kartu_stok DESC";
$result = mysqli_query($conn, $query);

// Ambil daftar produk untuk filter
$produk_list = mysqli_query($conn, "SELECT * FROM produk WHERE status='aktif' ORDER BY nama_produk");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Stok - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; }
        .badge-penerimaan { background-color: #28a745; }
        .badge-penjualan { background-color: #dc3545; }
        .badge-adjustment { background-color: #ffc107; color: #000; }
        @media print {
            .no-print { display: none !important; }
            .sidebar { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar no-print">
                <div class="p-3">
                    <h4 class="text-white">📦 INVENTORY</h4>
                    <hr class="text-white">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a>
                    <a href="satuan.php"><i class="bi bi-rulers"></i> Satuan</a>
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php"><i class="bi bi-box-seam"></i> Produk</a>
                    <a href="margin_penjualan.php"><i class="bi bi-percent"></i> Margin Penjualan</a>
                    <a href="pengadaan.php"><i class="bi bi-cart-plus"></i> Pengadaan</a>
                    <a href="penerimaan.php"><i class="bi bi-box-arrow-in-down"></i> Penerimaan</a>
                    <a href="penjualan.php"><i class="bi bi-cart-check"></i> Penjualan</a>
                    <a href="kartu_stok.php" class="active"><i class="bi bi-card-list"></i> Kartu Stok</a>
                    <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <?php if ($_SESSION['role'] == 'super_admin'): ?>
                    <a href="users.php"><i class="bi bi-person-gear"></i> Users</a>
                    <?php endif; ?>
                    <hr class="text-white">
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2><i class="bi bi-card-list"></i> Kartu Stok</h2>
                    <span class="text-muted">User: <?= $_SESSION['nama_user'] ?></span>
                </div>

                <!-- Filter -->
                <div class="card mb-3 no-print">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-funnel"></i> Filter Kartu Stok</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Produk</label>
                                <select class="form-select" name="produk">
                                    <option value="">Semua Produk</option>
                                    <?php while($p = mysqli_fetch_assoc($produk_list)): ?>
                                    <option value="<?= $p['id_produk'] ?>" <?= $filter_produk == $p['id_produk'] ? 'selected' : '' ?>>
                                        <?= $p['kode_produk'] ?> - <?= $p['nama_produk'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jenis Transaksi</label>
                                <select class="form-select" name="jenis">
                                    <option value="">Semua</option>
                                    <option value="penerimaan" <?= $filter_jenis == 'penerimaan' ? 'selected' : '' ?>>Penerimaan</option>
                                    <option value="penjualan" <?= $filter_jenis == 'penjualan' ? 'selected' : '' ?>>Penjualan</option>
                                    <option value="adjustment" <?= $filter_jenis == 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Dari</label>
                                <input type="date" class="form-control" name="tgl_dari" value="<?= $tgl_dari ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Sampai</label>
                                <input type="date" class="form-control" name="tgl_sampai" value="<?= $tgl_sampai ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                        <h5><i class="bi bi-table"></i> Riwayat Pergerakan Stok</h5>
                        <button onclick="window.print()" class="btn btn-sm btn-light no-print">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="10%">Tanggal</th>
                                        <th width="10%">Kode Produk</th>
                                        <th width="20%">Nama Produk</th>
                                        <th width="12%">Jenis Transaksi</th>
                                        <th width="12%">Referensi</th>
                                        <th width="8%">Masuk</th>
                                        <th width="8%">Keluar</th>
                                        <th width="8%">Saldo</th>
                                        <th width="17%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if (mysqli_num_rows($result) > 0):
                                        while ($row = mysqli_fetch_assoc($result)): 
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                        <td><span class="badge bg-info"><?= $row['kode_produk'] ?></span></td>
                                        <td><?= $row['nama_produk'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['jenis_transaksi'] ?>">
                                                <?= strtoupper($row['jenis_transaksi']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['referensi'] ?></td>
                                        <td class="text-center">
                                            <?php if ($row['masuk'] > 0): ?>
                                            <span class="badge bg-success">+<?= $row['masuk'] ?></span>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['keluar'] > 0): ?>
                                            <span class="badge bg-danger">-<?= $row['keluar'] ?></span>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><strong><?= $row['saldo'] ?></strong></td>
                                        <td><small><?= $row['keterangan'] ?></small></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                                            <p>Belum ada data kartu stok</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="row mt-3 no-print">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Informasi Kartu Stok:</h6>
                            <ul class="mb-0">
                                <li><strong>Masuk:</strong> Stok bertambah dari penerimaan barang</li>
                                <li><strong>Keluar:</strong> Stok berkurang dari penjualan atau penggunaan</li>
                                <li><strong>Saldo:</strong> Jumlah stok tersisa setelah transaksi</li>
                                <li><strong>Referensi:</strong> Nomor dokumen transaksi (No. Penerimaan/Penjualan)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>