<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Cek apakah ada ID pengadaan
if (!isset($_GET['id'])) {
    header('Location: pengadaan.php');
    exit();
}

$id_pengadaan = $_GET['id'];

// Ambil data pengadaan
$query = "SELECT p.*, s.nama_supplier, s.alamat, s.kota, s.no_telp, s.email, u.nama_user 
          FROM pengadaan p
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          LEFT JOIN users u ON p.id_user = u.id_user
          WHERE p.id_pengadaan = $id_pengadaan";
$pengadaan = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$pengadaan) {
    header('Location: pengadaan.php');
    exit();
}

// Ambil detail item pengadaan
$query_detail = "SELECT dp.*, pr.kode_produk, pr.nama_produk, k.nama_kategori, s.singkatan
                 FROM detail_pengadaan dp
                 LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
                 LEFT JOIN kategori_produk k ON pr.id_kategori = k.id_kategori
                 LEFT JOIN satuan s ON pr.id_satuan = s.id_satuan
                 WHERE dp.id_pengadaan = $id_pengadaan
                 ORDER BY dp.id_detail_pengadaan";
$detail_result = mysqli_query($conn, $query_detail);

// Handle Update Status - Hanya untuk Super Admin
if (isset($_POST['update_status']) && $role == 'super_admin') {
    $status_baru = $_POST['status_pengadaan'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    
    $query_update = "UPDATE pengadaan SET 
                     status_pengadaan = '$status_baru',
                     catatan = '$catatan'
                     WHERE id_pengadaan = $id_pengadaan";
    
    if (mysqli_query($conn, $query_update)) {
        header("Location: pengadaan_detail.php?id=$id_pengadaan&msg=status_updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengadaan - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .badge-draft { background-color: #6c757d; }
        .badge-diajukan { background-color: #0dcaf0; }
        .badge-disetujui { background-color: #198754; }
        .badge-ditolak { background-color: #dc3545; }
        .badge-selesai { background-color: #0d6efd; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
        @media print {
            .sidebar, .no-print { display: none !important; }
            .content { padding: 0; }
        }
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
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <a href="pengadaan.php" class="btn btn-secondary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <h2 class="d-inline"><i class="bi bi-file-text"></i> Detail Pengadaan</h2>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php if ($_GET['msg'] == 'status_updated') echo 'Status pengadaan berhasil diupdate!'; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Info Pengadaan -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Informasi Pengadaan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="40%"><strong>No Pengadaan</strong></td>
                                        <td>: <?= $pengadaan['no_pengadaan'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Pengadaan</strong></td>
                                        <td>: <?= date('d F Y', strtotime($pengadaan['tanggal_pengadaan'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>: <span class="badge badge-<?= $pengadaan['status_pengadaan'] ?>"><?= strtoupper($pengadaan['status_pengadaan']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat Oleh</strong></td>
                                        <td>: <?= $pengadaan['nama_user'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat Pada</strong></td>
                                        <td>: <?= date('d F Y H:i', strtotime($pengadaan['created_at'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-truck"></i> Informasi Supplier</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="40%"><strong>Nama Supplier</strong></td>
                                        <td>: <?= $pengadaan['nama_supplier'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Alamat</strong></td>
                                        <td>: <?= $pengadaan['alamat'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kota</strong></td>
                                        <td>: <?= $pengadaan['kota'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. Telepon</strong></td>
                                        <td>: <?= $pengadaan['no_telp'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td>: <?= $pengadaan['email'] ?? '-' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Item Pengadaan -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Detail Item Pengadaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="12%">Kode Produk</th>
                                        <th width="25%">Nama Produk</th>
                                        <th width="15%">Kategori</th>
                                        <th width="10%">Jumlah</th>
                                        <th width="10%">Satuan</th>
                                        <th width="13%">Harga Satuan</th>
                                        <th width="15%">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($detail = mysqli_fetch_assoc($detail_result)): 
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $detail['kode_produk'] ?></span></td>
                                        <td><strong><?= $detail['nama_produk'] ?></strong></td>
                                        <td><?= $detail['nama_kategori'] ?></td>
                                        <td class="text-center"><strong><?= $detail['qty'] ?></strong></td>
                                        <td class="text-center"><?= $detail['singkatan'] ?></td>
                                        <td class="text-end">Rp <?= number_format($detail['harga_satuan'], 0, ',', '.') ?></td>
                                        <td class="text-end"><strong>Rp <?= number_format($detail['subtotal'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Item:</strong></td>
                                        <td class="text-center"><strong><?= $pengadaan['total_item'] ?></strong></td>
                                        <td colspan="2" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end"><strong>Rp <?= number_format($pengadaan['total_harga'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="text-end"><strong>PPN (10%):</strong></td>
                                        <td class="text-end"><strong>Rp <?= number_format($pengadaan['pajak'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                    <tr class="table-success">
                                        <td colspan="7" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                        <td class="text-end"><h5 class="mb-0">Rp <?= number_format($pengadaan['grand_total'], 0, ',', '.') ?></h5></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Catatan -->
                <?php if ($pengadaan['catatan']): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Catatan</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br($pengadaan['catatan']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Update Status - Hanya untuk Super Admin -->
                <?php if ($role == 'super_admin'): ?>
                <div class="card no-print">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Update Status Pengadaan (Super Admin)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Status Pengadaan</strong></label>
                                    <select class="form-select" name="status_pengadaan" required>
                                        <option value="draft" <?= $pengadaan['status_pengadaan'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="diajukan" <?= $pengadaan['status_pengadaan'] == 'diajukan' ? 'selected' : '' ?>>Diajukan</option>
                                        <option value="disetujui" <?= $pengadaan['status_pengadaan'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="ditolak" <?= $pengadaan['status_pengadaan'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                        <option value="selesai" <?= $pengadaan['status_pengadaan'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label"><strong>Catatan</strong></label>
                                    <textarea class="form-control" name="catatan" rows="3" placeholder="Tambahkan catatan (opsional)..."><?= $pengadaan['catatan'] ?></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Status
                                </button>
                                <a href="pengadaan.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Action Buttons untuk Super Admin -->
                <div class="card no-print">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <?php if ($pengadaan['status_pengadaan'] == 'diajukan'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="status_pengadaan" value="disetujui">
                                    <input type="hidden" name="catatan" value="Pengadaan disetujui oleh <?= $_SESSION['nama_user'] ?>">
                                    <button type="submit" name="update_status" class="btn btn-success" onclick="return confirm('Setujui pengadaan ini?')">
                                        <i class="bi bi-check-circle"></i> Setujui Pengadaan
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="status_pengadaan" value="ditolak">
                                    <input type="hidden" name="catatan" value="Pengadaan ditolak oleh <?= $_SESSION['nama_user'] ?>">
                                    <button type="submit" name="update_status" class="btn btn-danger" onclick="return confirm('Tolak pengadaan ini?')">
                                        <i class="bi bi-x-circle"></i> Tolak Pengadaan
                                    </button>
                                </form>
                            <?php elseif ($pengadaan['status_pengadaan'] == 'disetujui'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="status_pengadaan" value="selesai">
                                    <input type="hidden" name="catatan" value="Pengadaan selesai, barang telah diterima">
                                    <button type="submit" name="update_status" class="btn btn-primary" onclick="return confirm('Tandai pengadaan ini sebagai selesai?')">
                                        <i class="bi bi-check-all"></i> Tandai Selesai
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($pengadaan['status_pengadaan'] == 'draft'): ?>
                                <a href="pengadaan.php?delete=<?= $pengadaan['id_pengadaan'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Yakin hapus pengadaan ini?')">
                                    <i class="bi bi-trash"></i> Hapus Pengadaan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>