<?php
session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Handle Delete - Hanya untuk Super Admin
if (isset($_GET['delete']) && $role == 'super_admin') {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM satuan WHERE id_satuan = $id");
    header('Location: satuan.php?msg=delete_success');
    exit();
}

// Handle Create - Hanya untuk Super Admin
if (isset($_POST['tambah']) && $role == 'super_admin') {
    $nama_satuan = mysqli_real_escape_string($conn, $_POST['nama_satuan']);
    $singkatan = mysqli_real_escape_string($conn, $_POST['singkatan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $query = "INSERT INTO satuan (nama_satuan, singkatan, deskripsi, created_at) 
              VALUES ('$nama_satuan', '$singkatan', '$deskripsi', NOW())";
    
    if (mysqli_query($conn, $query)) {
        header('Location: satuan.php?msg=add_success');
        exit();
    }
}

// Handle Update - Hanya untuk Super Admin
if (isset($_POST['update']) && $role == 'super_admin') {
    $id_satuan = $_POST['id_satuan'];
    $nama_satuan = mysqli_real_escape_string($conn, $_POST['nama_satuan']);
    $singkatan = mysqli_real_escape_string($conn, $_POST['singkatan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $query = "UPDATE satuan SET 
              nama_satuan = '$nama_satuan',
              singkatan = '$singkatan',
              deskripsi = '$deskripsi'
              WHERE id_satuan = $id_satuan";
    
    if (mysqli_query($conn, $query)) {
        header('Location: satuan.php?msg=update_success');
        exit();
    }
}

// Ambil data satuan
$query = "SELECT * FROM satuan ORDER BY id_satuan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Satuan - Inventory Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; }
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
                    <a href="satuan.php" class="active"><i class="bi bi-rulers"></i> Satuan</a>
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php"><i class="bi bi-box-seam"></i> Produk</a>
                    <a href="margin_penjualan.php"><i class="bi bi-percent"></i> Margin Penjualan</a>
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
                    <h2><i class="bi bi-rulers"></i> Data Satuan</h2>
                    <div>
                        <?php if ($role == 'super_admin'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle"></i> Tambah Satuan
                        </button>
                        <?php endif; ?>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'add_success') echo 'Satuan berhasil ditambahkan!';
                    if ($_GET['msg'] == 'update_success') echo 'Satuan berhasil diupdate!';
                    if ($_GET['msg'] == 'delete_success') echo 'Satuan berhasil dihapus!';
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
                                        <th>Nama Satuan</th>
                                        <th>Singkatan</th>
                                        <th>Deskripsi</th>
                                        <th>Dibuat</th>
                                        <?php if ($role == 'super_admin'): ?>
                                        <th>Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result)): 
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= $row['nama_satuan'] ?></strong></td>
                                        <td><span class="badge bg-primary"><?= $row['singkatan'] ?></span></td>
                                        <td><?= $row['deskripsi'] ?? '-' ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                        <?php if ($role == 'super_admin'): ?>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id_satuan'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <a href="satuan.php?delete=<?= $row['id_satuan'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin hapus satuan ini?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <?php if ($role == 'super_admin'): ?>
                                    <div class="modal fade" id="modalEdit<?= $row['id_satuan'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Edit Satuan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_satuan" value="<?= $row['id_satuan'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Satuan</label>
                                                            <input type="text" class="form-control" name="nama_satuan" value="<?= $row['nama_satuan'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Singkatan</label>
                                                            <input type="text" class="form-control" name="singkatan" value="<?= $row['singkatan'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Deskripsi</label>
                                                            <textarea class="form-control" name="deskripsi" rows="3"><?= $row['deskripsi'] ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update" class="btn btn-warning">Update</button>
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
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <?php if ($role == 'super_admin'): ?>
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Satuan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_satuan" placeholder="Contoh: Kilogram" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Singkatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="singkatan" placeholder="Contoh: kg" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="3" placeholder="Keterangan satuan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>