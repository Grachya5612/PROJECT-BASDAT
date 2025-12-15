<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Handle Update Harga - Hanya untuk Super Admin
if (isset($_POST['update_harga']) && $role == 'super_admin') {
    $id_produk = $_POST['id_produk'];
    $harga_jual = $_POST['harga_jual'];
    
    $query = "UPDATE produk SET harga_jual = $harga_jual WHERE id_produk = $id_produk";
    
    if (mysqli_query($conn, $query)) {
        header('Location: margin_penjualan.php?msg=update_success');
        exit();
    }
}

// Filter
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'aktif';

// Query margin penjualan
$query = "SELECT p.*, k.nama_kategori, k.icon, s.singkatan,
          (p.harga_jual - p.harga_beli) as margin_rupiah,
          CASE 
            WHEN p.harga_beli > 0 THEN ROUND(((p.harga_jual - p.harga_beli) / p.harga_beli * 100), 2)
            ELSE 0
          END as margin_persen
          FROM produk p
          LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori
          LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
          WHERE 1=1";

if ($filter_kategori) $query .= " AND p.id_kategori = $filter_kategori";
if ($filter_status) $query .= " AND p.status = '$filter_status'";

$query .= " ORDER BY margin_persen DESC";
$result = mysqli_query($conn, $query);

// Ambil kategori untuk filter
$kategori_list = mysqli_query($conn, "SELECT * FROM kategori_produk WHERE status='aktif' ORDER BY nama_kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Margin Penjualan - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; }
        .badge-aktif { background-color: #28a745; }
        .badge-tidak_aktif { background-color: #dc3545; }
        .margin-tinggi { background-color: #d4edda; }
        .margin-sedang { background-color: #fff3cd; }
        .margin-rendah { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="p-3">
                    <h4 class="text-white">📦 INVENTORY</h4>
                    <hr class="text-white">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a>
                    <a href="satuan.php"><i class="bi bi-rulers"></i> Satuan</a>
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php"><i class="bi bi-box-seam"></i> Produk</a>
                    <a href="margin_penjualan.php" class="active"><i class="bi bi-percent"></i> Margin Penjualan</a>
                    <a href="pengadaan.php"><i class="bi bi-cart-plus"></i> Pengadaan</a>
                    <a href="penerimaan.php"><i class="bi bi-box-arrow-in-down"></i> Penerimaan</a>
                    <a href="penjualan.php"><i class="bi bi-cart-check"></i> Penjualan</a>
                    <a href="kartu_stok.php"><i class="bi bi-card-list"></i> Kartu Stok</a>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-percent"></i> Margin Penjualan Produk</h2>
                    <span class="text-muted">User: <?= $_SESSION['nama_user'] ?></span>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'update_success') echo 'Harga jual berhasil diupdate!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php while($k = mysqli_fetch_assoc($kategori_list)): ?>
                                    <option value="<?= $k['id_kategori'] ?>" <?= $filter_kategori == $k['id_kategori'] ? 'selected' : '' ?>>
                                        <?= $k['nama_kategori'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status Produk</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="tidak_aktif" <?= $filter_status == 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="margin_penjualan.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Margin Tinggi (> 30%)</h6>
                                <h3><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM produk WHERE ((harga_jual - harga_beli) / harga_beli * 100) > 30 AND status='aktif'")) ?> Produk</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h6>Margin Sedang (15-30%)</h6>
                                <h3><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM produk WHERE ((harga_jual - harga_beli) / harga_beli * 100) BETWEEN 15 AND 30 AND status='aktif'")) ?> Produk</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h6>Margin Rendah (< 15%)</h6>
                                <h3><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM produk WHERE ((harga_jual - harga_beli) / harga_beli * 100) < 15 AND status='aktif'")) ?> Produk</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Kategori</th>
                                        <th>Nama Produk</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Margin (Rp)</th>
                                        <th>Margin (%)</th>
                                        <th>Status</th>
                                        <?php if ($role == 'super_admin'): ?>
                                        <th>Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                    // Tentukan class berdasarkan margin
                                    $row_class = '';
                                    if ($row['margin_persen'] > 30) $row_class = 'margin-tinggi';
                                    elseif ($row['margin_persen'] >= 15) $row_class = 'margin-sedang';
                                    elseif ($row['margin_persen'] > 0) $row_class = 'margin-rendah';
                                    ?>
                                    <tr class="<?= $row_class ?>">
                                        <td><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $row['kode_produk'] ?></span></td>
                                        <td><?= $row['icon'] ?> <?= $row['nama_kategori'] ?></td>
                                        <td><strong><?= $row['nama_produk'] ?></strong></td>
                                        <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($row['margin_rupiah'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="badge <?= $row['margin_persen'] > 30 ? 'bg-success' : ($row['margin_persen'] >= 15 ? 'bg-warning text-dark' : 'bg-danger') ?>" style="font-size: 14px;">
                                                <?= number_format($row['margin_persen'], 2) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                        <?php if ($role == 'super_admin'): ?>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditHarga<?= $row['id_produk'] ?>">
                                                <i class="bi bi-pencil"></i> Edit Harga
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Modal Edit Harga -->
                                    <?php if ($role == 'super_admin'): ?>
                                    <div class="modal fade" id="modalEditHarga<?= $row['id_produk'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Edit Harga Jual - <?= $row['nama_produk'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_produk" value="<?= $row['id_produk'] ?>">
                                                        
                                                        <div class="alert alert-info">
                                                            <strong>Info Produk:</strong><br>
                                                            Kode: <?= $row['kode_produk'] ?><br>
                                                            Kategori: <?= $row['nama_kategori'] ?>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Harga Beli (tidak dapat diubah)</label>
                                                            <input type="text" class="form-control" value="Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?>" disabled>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Harga Jual Baru <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" name="harga_jual" value="<?= $row['harga_jual'] ?>" required>
                                                            <small class="text-muted">Harga jual saat ini: Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></small>
                                                        </div>
                                                        
                                                        <div class="alert alert-warning">
                                                            <strong><i class="bi bi-info-circle"></i> Margin saat ini:</strong><br>
                                                            Rp <?= number_format($row['margin_rupiah'], 0, ',', '.') ?> (<?= number_format($row['margin_persen'], 2) ?>%)
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_harga" class="btn btn-warning">Update Harga</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6><i class="bi bi-info-circle"></i> Keterangan Warna:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="p-2 margin-tinggi border rounded">
                                    <strong>Hijau:</strong> Margin Tinggi (> 30%)
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 margin-sedang border rounded">
                                    <strong>Kuning:</strong> Margin Sedang (15-30%)
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 margin-rendah border rounded">
                                    <strong>Merah:</strong> Margin Rendah (< 15%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>