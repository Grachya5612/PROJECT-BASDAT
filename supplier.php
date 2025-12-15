<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Handle Delete - Hanya untuk Super Admin
if (isset($_GET['delete']) && $role == 'super_admin') {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM supplier WHERE id_supplier = $id");
    header('Location: supplier.php?msg=delete_success');
    exit();
}

// Handle Create - Hanya untuk Super Admin
if (isset($_POST['tambah']) && $role == 'super_admin') {
    $kode_supplier = mysqli_real_escape_string($conn, $_POST['kode_supplier']);
    $nama_supplier = mysqli_real_escape_string($conn, $_POST['nama_supplier']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $status = $_POST['status'];
    
    $query = "INSERT INTO supplier (kode_supplier, nama_supplier, alamat, kota, no_telp, email, contact_person, status, created_at) 
              VALUES ('$kode_supplier', '$nama_supplier', '$alamat', '$kota', '$no_telp', '$email', '$contact_person', '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        header('Location: supplier.php?msg=add_success');
        exit();
    }
}

// Handle Update - Hanya untuk Super Admin
if (isset($_POST['update']) && $role == 'super_admin') {
    $id_supplier = $_POST['id_supplier'];
    $kode_supplier = mysqli_real_escape_string($conn, $_POST['kode_supplier']);
    $nama_supplier = mysqli_real_escape_string($conn, $_POST['nama_supplier']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $status = $_POST['status'];
    
    $query = "UPDATE supplier SET 
              kode_supplier = '$kode_supplier',
              nama_supplier = '$nama_supplier',
              alamat = '$alamat',
              kota = '$kota',
              no_telp = '$no_telp',
              email = '$email',
              contact_person = '$contact_person',
              status = '$status'
              WHERE id_supplier = $id_supplier";
    
    if (mysqli_query($conn, $query)) {
        header('Location: supplier.php?msg=update_success');
        exit();
    }
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT * FROM supplier WHERE 1=1";
if ($filter_status) {
    $query .= " AND status = '$filter_status'";
}
if ($search) {
    $query .= " AND (kode_supplier LIKE '%$search%' OR nama_supplier LIKE '%$search%' OR kota LIKE '%$search%')";
}
$query .= " ORDER BY id_supplier DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Supplier - Inventory Penjualan</title>
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
                    <a href="supplier.php" class="active"><i class="bi bi-truck"></i> Supplier</a>
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
                    <h2><i class="bi bi-truck"></i> Data Supplier</h2>
                    <div>
                        <?php if ($role == 'super_admin'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle"></i> Tambah Supplier
                        </button>
                        <?php endif; ?>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'add_success') echo 'Supplier berhasil ditambahkan!';
                    if ($_GET['msg'] == 'update_success') echo 'Supplier berhasil diupdate!';
                    if ($_GET['msg'] == 'delete_success') echo 'Supplier berhasil dihapus!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Cari kode/nama/kota..." value="<?= $search ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="tidak_aktif" <?= $filter_status == 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="supplier.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
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
                                        <th>Kode</th>
                                        <th>Nama Supplier</th>
                                        <th>Alamat</th>
                                        <th>Kota</th>
                                        <th>Kontak</th>
                                        <th>Contact Person</th>
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
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $row['kode_supplier'] ?></span></td>
                                        <td><strong><?= $row['nama_supplier'] ?></strong></td>
                                        <td><?= $row['alamat'] ?? '-' ?></td>
                                        <td><?= $row['kota'] ?? '-' ?></td>
                                        <td>
                                            <small>
                                                <i class="bi bi-telephone"></i> <?= $row['no_telp'] ?? '-' ?><br>
                                                <i class="bi bi-envelope"></i> <?= $row['email'] ?? '-' ?>
                                            </small>
                                        </td>
                                        <td><?= $row['contact_person'] ?? '-' ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                        <?php if ($role == 'super_admin'): ?>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id_supplier'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="supplier.php?delete=<?= $row['id_supplier'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin hapus supplier ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <?php if ($role == 'super_admin'): ?>
                                    <div class="modal fade" id="modalEdit<?= $row['id_supplier'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Edit Supplier</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_supplier" value="<?= $row['id_supplier'] ?>">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Kode Supplier</label>
                                                                <input type="text" class="form-control" name="kode_supplier" value="<?= $row['kode_supplier'] ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Nama Supplier</label>
                                                                <input type="text" class="form-control" name="nama_supplier" value="<?= $row['nama_supplier'] ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Alamat</label>
                                                            <textarea class="form-control" name="alamat" rows="2"><?= $row['alamat'] ?></textarea>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Kota</label>
                                                                <input type="text" class="form-control" name="kota" value="<?= $row['kota'] ?>">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">No. Telepon</label>
                                                                <input type="text" class="form-control" name="no_telp" value="<?= $row['no_telp'] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" name="email" value="<?= $row['email'] ?>">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Contact Person</label>
                                                                <input type="text" class="form-control" name="contact_person" value="<?= $row['contact_person'] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="aktif" <?= $row['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                                <option value="tidak_aktif" <?= $row['status'] == 'tidak_aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                                            </select>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Supplier Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Supplier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kode_supplier" placeholder="SUP001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_supplier" placeholder="PT. Supplier Indonesia" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Jl. Contoh No. 123"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kota</label>
                                <input type="text" class="form-control" name="kota" placeholder="Jakarta">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" name="no_telp" placeholder="021-12345678">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="supplier@email.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person" placeholder="Nama PIC">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="aktif" selected>Aktif</option>
                                <option value="tidak_aktif">Tidak Aktif</option>
                            </select>
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