<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// HANYA SUPER ADMIN YANG BISA AKSES HALAMAN INI
if ($_SESSION['role'] != 'super_admin') {
    header('Location: dashboard.php?msg=access_denied');
    exit();
}

// Ambil data users
$query = "SELECT * FROM users ORDER BY id_user DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Users - Inventory</title>
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
        .badge-admin { background-color: #17a2b8; }
        .badge-super_admin { background-color: #dc3545; }
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
                    <a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a>
                    <a href="satuan.php"><i class="bi bi-rulers"></i> Satuan</a>
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php"><i class="bi bi-box-seam"></i> Produk</a>
                    <a href="pengadaan.php"><i class="bi bi-cart-plus"></i> Pengadaan</a>
                    <a href="penerimaan.php"><i class="bi bi-box-arrow-in-down"></i> Penerimaan</a>
                    <a href="penjualan.php"><i class="bi bi-cart-check"></i> Penjualan</a>
                    <a href="kartu_stok.php"><i class="bi bi-card-list"></i> Kartu Stok</a>
                    <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a href="users.php" class="active"><i class="bi bi-person-gear"></i> Users</a>
                    <hr class="text-white">
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-person-gear"></i> Manajemen Users</h2>
                    <span class="text-muted">User: <?= $_SESSION['nama_user'] ?></span>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>No Telp</th>
                                        <th>Status</th>
                                        <th>Terdaftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <?php if ($row['foto_profil']): ?>
                                            <img src="<?= $row['foto_profil'] ?>" width="40" height="40" class="rounded-circle">
                                            <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 40px; height: 40px; color: white;">
                                                <?= strtoupper(substr($row['nama_user'], 0, 1)) ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= $row['nama_user'] ?></strong></td>
                                        <td><?= $row['email'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['role'] ?>">
                                                <?= strtoupper(str_replace('_', ' ', $row['role'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['no_telp'] ?? '-' ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                    </tr>

                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>