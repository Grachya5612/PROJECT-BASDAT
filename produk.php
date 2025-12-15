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
    mysqli_query($conn, "DELETE FROM produk WHERE id_produk = $id");
    header('Location: produk.php?msg=delete_success');
    exit();
}

// Handle Create - Hanya untuk Super Admin
if (isset($_POST['tambah']) && $role == 'super_admin') {
    $kode_produk = mysqli_real_escape_string($conn, $_POST['kode_produk']);
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $id_kategori = $_POST['id_kategori'];
    $id_satuan = $_POST['id_satuan'];
    $harga_beli = $_POST['harga_beli'];
    $harga_jual = $_POST['harga_jual'];
    $stok_minimal = $_POST['stok_minimal'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $status = $_POST['status'];
    
    $query = "INSERT INTO produk (kode_produk, nama_produk, id_kategori, id_satuan, harga_beli, harga_jual, stok_tersedia, stok_minimal, deskripsi, status, created_at) 
              VALUES ('$kode_produk', '$nama_produk', $id_kategori, $id_satuan, $harga_beli, $harga_jual, 0, $stok_minimal, '$deskripsi', '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        header('Location: produk.php?msg=add_success');
        exit();
    }
}

// Handle Update - Hanya untuk Super Admin
if (isset($_POST['update']) && $role == 'super_admin') {
    $id_produk = $_POST['id_produk'];
    $kode_produk = mysqli_real_escape_string($conn, $_POST['kode_produk']);
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $id_kategori = $_POST['id_kategori'];
    $id_satuan = $_POST['id_satuan'];
    $harga_beli = $_POST['harga_beli'];
    $harga_jual = $_POST['harga_jual'];
    $stok_minimal = $_POST['stok_minimal'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $status = $_POST['status'];
    
    $query = "UPDATE produk SET 
              kode_produk = '$kode_produk',
              nama_produk = '$nama_produk',
              id_kategori = $id_kategori,
              id_satuan = $id_satuan,
              harga_beli = $harga_beli,
              harga_jual = $harga_jual,
              stok_minimal = $stok_minimal,
              deskripsi = '$deskripsi',
              status = '$status'
              WHERE id_produk = $id_produk";
    
    if (mysqli_query($conn, $query)) {
        header('Location: produk.php?msg=update_success');
        exit();
    }
}

// Filter
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data produk dengan filter
$query = "SELECT p.*, k.nama_kategori, k.icon, s.singkatan 
          FROM produk p
          LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori
          LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
          WHERE 1=1";

if ($filter_kategori) $query .= " AND p.id_kategori = $filter_kategori";
if ($filter_status) $query .= " AND p.status = '$filter_status'";
if ($search) $query .= " AND (p.kode_produk LIKE '%$search%' OR p.nama_produk LIKE '%$search%')";

$query .= " ORDER BY p.id_produk DESC";
$result = mysqli_query($conn, $query);

// Ambil kategori dan satuan untuk dropdown
$kategori_list = mysqli_query($conn, "SELECT * FROM kategori_produk WHERE status='aktif' ORDER BY nama_kategori");
$satuan_list = mysqli_query($conn, "SELECT * FROM satuan ORDER BY nama_satuan");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Inventory</title>
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
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php" class="active"><i class="bi bi-box-seam"></i> Produk</a>
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
                    <h2><i class="bi bi-box-seam"></i> Data Produk</h2>
                    <div>
                        <?php if ($role == 'super_admin'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle"></i> Tambah Produk
                        </button>
                        <?php endif; ?>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php
                    if ($_GET['msg'] == 'add_success') echo 'Produk berhasil ditambahkan!';
                    if ($_GET['msg'] == 'update_success') echo 'Produk berhasil diupdate!';
                    if ($_GET['msg'] == 'delete_success') echo 'Produk berhasil dihapus!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Cari produk..." value="<?= $search ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php 
                                    mysqli_data_seek($kategori_list, 0);
                                    while($k = mysqli_fetch_assoc($kategori_list)): 
                                    ?>
                                    <option value="<?= $k['id_kategori'] ?>" <?= $filter_kategori == $k['id_kategori'] ? 'selected' : '' ?>>
                                        <?= $k['nama_kategori'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                                <a href="produk.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
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
                                        <th>Kategori</th>
                                        <th>Nama Produk</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                        <th>Satuan</th>
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
                                    $stok_class = $row['stok_tersedia'] < $row['stok_minimal'] ? 'bg-danger text-white' : 'bg-success text-white';
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $row['kode_produk'] ?></span></td>
                                        <td><?= $row['icon'] ?> <?= $row['nama_kategori'] ?></td>
                                        <td><strong><?= $row['nama_produk'] ?></strong></td>
                                        <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                                        <td><span class="badge <?= $stok_class ?>"><?= $row['stok_tersedia'] ?></span></td>
                                        <td><?= $row['stok_minimal'] ?></td>
                                        <td><?= $row['singkatan'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= $row['status'] ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                        <?php if ($role == 'super_admin'): ?>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id_produk'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="produk.php?delete=<?= $row['id_produk'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin hapus produk ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <?php if ($role == 'super_admin'): ?>
                                    <div class="modal fade" id="modalEdit<?= $row['id_produk'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Edit Produk</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_produk" value="<?= $row['id_produk'] ?>">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Kode Produk</label>
                                                                <input type="text" class="form-control" name="kode_produk" value="<?= $row['kode_produk'] ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Nama Produk</label>
                                                                <input type="text" class="form-control" name="nama_produk" value="<?= $row['nama_produk'] ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Kategori</label>
                                                                <select class="form-select" name="id_kategori" required>
                                                                    <?php 
                                                                    mysqli_data_seek($kategori_list, 0);
                                                                    while($k = mysqli_fetch_assoc($kategori_list)): 
                                                                    ?>
                                                                    <option value="<?= $k['id_kategori'] ?>" <?= $row['id_kategori'] == $k['id_kategori'] ? 'selected' : '' ?>>
                                                                        <?= $k['nama_kategori'] ?>
                                                                    </option>
                                                                    <?php endwhile; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Satuan</label>
                                                                <select class="form-select" name="id_satuan" required>
                                                                    <?php 
                                                                    mysqli_data_seek($satuan_list, 0);
                                                                    while($s = mysqli_fetch_assoc($satuan_list)): 
                                                                    ?>
                                                                    <option value="<?= $s['id_satuan'] ?>" <?= $row['id_satuan'] == $s['id_satuan'] ? 'selected' : '' ?>>
                                                                        <?= $s['nama_satuan'] ?> (<?= $s['singkatan'] ?>)
                                                                    </option>
                                                                    <?php endwhile; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Harga Beli</label>
                                                                <input type="number" class="form-control" name="harga_beli" value="<?= $row['harga_beli'] ?>" required>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Harga Jual</label>
                                                                <input type="number" class="form-control" name="harga_jual" value="<?= $row['harga_jual'] ?>" required>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Stok Minimal</label>
                                                                <input type="number" class="form-control" name="stok_minimal" value="<?= $row['stok_minimal'] ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Deskripsi</label>
                                                            <textarea class="form-control" name="deskripsi" rows="2"><?= $row['deskripsi'] ?></textarea>
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
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kode_produk" placeholder="PROD001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_produk" placeholder="Nama Produk" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php 
                                    mysqli_data_seek($kategori_list, 0);
                                    while($k = mysqli_fetch_assoc($kategori_list)): 
                                    ?>
                                    <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_satuan" required>
                                    <option value="">Pilih Satuan</option>
                                    <?php 
                                    mysqli_data_seek($satuan_list, 0);
                                    while($s = mysqli_fetch_assoc($satuan_list)): 
                                    ?>
                                    <option value="<?= $s['id_satuan'] ?>"><?= $s['nama_satuan'] ?> (<?= $s['singkatan'] ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Harga Beli <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="harga_beli" placeholder="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="harga_jual" placeholder="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stok Minimal <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stok_minimal" placeholder="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="2" placeholder="Deskripsi produk..."></textarea>
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