<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Handle Approve/Reject - Hanya untuk Super Admin
if (isset($_GET['action']) && isset($_GET['id']) && $role == 'super_admin') {
    $id_pengadaan = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE pengadaan SET status_pengadaan = 'disetujui' WHERE id_pengadaan = $id_pengadaan");
        header('Location: pengadaan.php?msg=approved');
        exit();
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE pengadaan SET status_pengadaan = 'ditolak' WHERE id_pengadaan = $id_pengadaan");
        header('Location: pengadaan.php?msg=rejected');
        exit();
    }
}

// Handle Delete - Hanya untuk Super Admin dan hanya untuk status draft
if (isset($_GET['delete']) && $role == 'super_admin') {
    $id = $_GET['delete'];
    // Hapus detail pengadaan terlebih dahulu
    mysqli_query($conn, "DELETE FROM detail_pengadaan WHERE id_pengadaan = $id");
    // Hapus pengadaan
    mysqli_query($conn, "DELETE FROM pengadaan WHERE id_pengadaan = $id");
    header('Location: pengadaan.php?msg=delete_success');
    exit();
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data pengadaan
$query = "SELECT p.*, s.nama_supplier, u.nama_user 
          FROM pengadaan p
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          LEFT JOIN users u ON p.id_user = u.id_user
          WHERE 1=1";

if ($filter_status) $query .= " AND p.status_pengadaan = '$filter_status'";
if ($search) $query .= " AND (p.no_pengadaan LIKE '%$search%' OR s.nama_supplier LIKE '%$search%')";

$query .= " ORDER BY p.id_pengadaan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan - Inventory Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; }
        .badge-draft { background-color: #6c757d; }
        .badge-diajukan { background-color: #0dcaf0; }
        .badge-disetujui { background-color: #198754; }
        .badge-ditolak { background-color: #dc3545; }
        .badge-selesai { background-color: #0d6efd; }
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
                    <a href="margin_penjualan.php"><i class="bi bi-percent"></i> Margin Penjualan</a>
                    <a href="pengadaan.php" class="active"><i class="bi bi-cart-plus"></i> Pengadaan</a>
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
                    <h2><i class="bi bi-cart-plus"></i> Data Pengadaan</h2>
                    <div>
                        <a href="pengadaan_form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Pengadaan Baru</a>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'approved') echo 'Pengadaan berhasil disetujui!';
                    if ($_GET['msg'] == 'rejected') echo 'Pengadaan berhasil ditolak!';
                    if ($_GET['msg'] == 'delete_success') echo 'Pengadaan berhasil dihapus!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Cari no pengadaan / supplier..." value="<?= $search ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="draft" <?= $filter_status == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="diajukan" <?= $filter_status == 'diajukan' ? 'selected' : '' ?>>Diajukan</option>
                                    <option value="disetujui" <?= $filter_status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                    <option value="ditolak" <?= $filter_status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                    <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="pengadaan.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>No Pengadaan</th>
                                        <th>Tanggal</th>
                                        <th>Supplier</th>
                                        <th>Total Item</th>
                                        <th>Subtotal</th>
                                        <th>PPN (10%)</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= $row['no_pengadaan'] ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_pengadaan'])) ?></td>
                                        <td><?= $row['nama_supplier'] ?></td>
                                        <td><span class="badge bg-info"><?= $row['total_item'] ?> item</span></td>
                                        <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['pajak'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($row['grand_total'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status_pengadaan'] ?>">
                                                <?= strtoupper($row['status_pengadaan']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['nama_user'] ?></td>
                                        <td>
                                            <a href="pengadaan_detail.php?id=<?= $row['id_pengadaan'] ?>" class="btn btn-info btn-sm" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <?php if ($role == 'super_admin'): ?>
                                                <?php if ($row['status_pengadaan'] == 'diajukan'): ?>
                                                    <a href="pengadaan.php?action=approve&id=<?= $row['id_pengadaan'] ?>" 
                                                       class="btn btn-success btn-sm" 
                                                       onclick="return confirm('Setujui pengadaan ini?')" 
                                                       title="Setujui">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                    <a href="pengadaan.php?action=reject&id=<?= $row['id_pengadaan'] ?>" 
                                                       class="btn btn-warning btn-sm" 
                                                       onclick="return confirm('Tolak pengadaan ini?')" 
                                                       title="Tolak">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($row['status_pengadaan'] == 'draft'): ?>
                                                    <a href="pengadaan.php?delete=<?= $row['id_pengadaan'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin hapus pengadaan ini?')" 
                                                       title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($role == 'super_admin'): ?>
                <!-- Info Card untuk Super Admin -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6><i class="bi bi-info-circle"></i> Info untuk Super Admin:</h6>
                        <ul class="mb-0">
                            <li>Anda dapat <strong>menyetujui</strong> atau <strong>menolak</strong> pengadaan dengan status "Diajukan"</li>
                            <li>Anda dapat <strong>menghapus</strong> pengadaan dengan status "Draft"</li>
                            <li>Admin biasa hanya dapat membuat dan melihat pengadaan</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>