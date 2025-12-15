<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM penerimaan WHERE id_penerimaan = $id");
    header('Location: penerimaan.php?msg=delete_success');
    exit();
}

// Ambil data penerimaan
$query = "SELECT p.*, s.nama_supplier, u.nama_user, pg.no_pengadaan
          FROM penerimaan p
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          LEFT JOIN users u ON p.id_user = u.id_user
          LEFT JOIN pengadaan pg ON p.id_pengadaan = pg.id_pengadaan
          ORDER BY p.id_penerimaan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan - Inventory Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-diterima_sebagian { background-color: #17a2b8; }
        .badge-diterima_lengkap { background-color: #28a745; }
        .badge-ditolak { background-color: #dc3545; }
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
                    <a href="penerimaan.php" class="active"><i class="bi bi-box-arrow-in-down"></i> Penerimaan</a>
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
                    <h2><i class="bi bi-box-arrow-in-down"></i> Data Penerimaan Barang</h2>
                    <div>
                        <a href="penerimaan_form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Penerimaan Baru</a>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'success') echo 'Data penerimaan berhasil disimpan!';
                    if ($_GET['msg'] == 'update_success') echo 'Data penerimaan berhasil diupdate!';
                    if ($_GET['msg'] == 'delete_success') echo 'Data penerimaan berhasil dihapus!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>No Penerimaan</th>
                                        <th>Tanggal</th>
                                        <th>No Pengadaan</th>
                                        <th>Supplier</th>
                                        <th>Total Item</th>
                                        <th>Total Qty</th>
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
                                        <td><strong><?= $row['no_penerimaan'] ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_penerimaan'])) ?></td>
                                        <td><?= $row['no_pengadaan'] ?? '-' ?></td>
                                        <td><?= $row['nama_supplier'] ?></td>
                                        <td><span class="badge bg-info"><?= $row['total_item'] ?> item</span></td>
                                        <td><span class="badge bg-primary"><?= $row['total_qty'] ?> pcs</span></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status_penerimaan'] ?>">
                                                <?= strtoupper(str_replace('_', ' ', $row['status_penerimaan'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['nama_user'] ?></td>
                                        <td>
                                            <a href="penerimaan_detail.php?id=<?= $row['id_penerimaan'] ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="penerimaan_form.php?edit=<?= $row['id_penerimaan'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?= $row['id_penerimaan'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
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