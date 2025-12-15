<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil role user
$role = $_SESSION['role'];

// Cek apakah ada ID penerimaan
if (!isset($_GET['id'])) {
    header('Location: penerimaan.php');
    exit();
}

$id_penerimaan = $_GET['id'];

// Ambil data penerimaan
$query = "SELECT pen.*, peng.no_pengadaan, peng.tanggal_pengadaan, peng.id_pengadaan,
          s.nama_supplier, s.alamat, s.kota, s.no_telp, s.email, u.nama_user 
          FROM penerimaan pen
          LEFT JOIN pengadaan peng ON pen.id_pengadaan = peng.id_pengadaan
          LEFT JOIN supplier s ON peng.id_supplier = s.id_supplier
          LEFT JOIN users u ON pen.id_user = u.id_user
          WHERE pen.id_penerimaan = $id_penerimaan";
$penerimaan = mysqli_fetch_assoc(mysqli_query($conn, $query));

if (!$penerimaan) {
    header('Location: penerimaan.php');
    exit();
}

// Ambil detail item penerimaan
$query_detail = "SELECT dp.*, pr.kode_produk, pr.nama_produk, k.nama_kategori, s.singkatan
                 FROM detail_penerimaan dp
                 LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
                 LEFT JOIN kategori_produk k ON pr.id_kategori = k.id_kategori
                 LEFT JOIN satuan s ON pr.id_satuan = s.id_satuan
                 WHERE dp.id_penerimaan = $id_penerimaan
                 ORDER BY dp.id_detail_penerimaan";
$detail_result = mysqli_query($conn, $query_detail);

// Ambil status pengadaan untuk setiap produk (yang diadakan vs yang sudah diterima)
$query_status = "SELECT 
                 dpeng.id_produk, 
                 pr.kode_produk, 
                 pr.nama_produk, 
                 s.singkatan,
                 dpeng.qty as qty_diadakan,
                 COALESCE(SUM(dpen.qty_diterima), 0) as total_diterima,
                 (dpeng.qty - COALESCE(SUM(dpen.qty_diterima), 0)) as sisa
                 FROM detail_pengadaan dpeng
                 LEFT JOIN produk pr ON dpeng.id_produk = pr.id_produk
                 LEFT JOIN satuan s ON pr.id_satuan = s.id_satuan
                 LEFT JOIN penerimaan pen ON pen.id_pengadaan = dpeng.id_pengadaan
                 LEFT JOIN detail_penerimaan dpen ON dpen.id_penerimaan = pen.id_penerimaan AND dpen.id_produk = dpeng.id_produk
                 WHERE dpeng.id_pengadaan = {$penerimaan['id_pengadaan']}
                 GROUP BY dpeng.id_produk, pr.kode_produk, pr.nama_produk, s.singkatan, dpeng.qty
                 ORDER BY pr.nama_produk";
$status_result = mysqli_query($conn, $query_status);

/// Handle Update Status - Hanya untuk Super Admin
if (isset($_POST['update_status']) && $role == 'super_admin') {
    $status_baru = $_POST['status_penerimaan'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    
    // Debug: Tampilkan nilai yang akan diupdate
    echo "DEBUG - Status yang akan diupdate: '" . $status_baru . "'<br>";
    echo "DEBUG - Panjang string: " . strlen($status_baru) . "<br>";
    echo "DEBUG - Catatan: " . $catatan . "<br>";
    
    // Handle Update Status - Hanya untuk Super Admin
if (isset($_POST['update_status']) && $role == 'super_admin') {
    $status_baru = $_POST['status_penerimaan'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    
    // Cek apakah status valid
    $valid_status = ['pending', 'diterima_sebagian', 'diterima_lengkap', 'ditolak'];
    if (!in_array($status_baru, $valid_status)) {
        die("ERROR: Status tidak valid! Status: '$status_baru'");
    }
    
    $status_baru_escaped = mysqli_real_escape_string($conn, $status_baru);
    
    $query_update = "UPDATE penerimaan SET 
                     status_penerimaan = '$status_baru_escaped',
                     catatan = '$catatan'
                     WHERE id_penerimaan = $id_penerimaan";
    
    if (mysqli_query($conn, $query_update)) {
        header("Location: penerimaan_detail.php?id=$id_penerimaan&msg=status_updated");
        exit();
    } else {
        die("ERROR: " . mysqli_error($conn));
    }
}
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penerimaan - Inventory</title>
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
        .badge-pending { background-color: #6c757d; }
        .badge-diterima_sebagian { background-color: #ffc107; color: #000; }
        .badge-diterima_lengkap { background-color: #198754; }
        .badge-ditolak { background-color: #dc3545; }
        .status-complete { background-color: #d4edda !important; }
        .status-partial { background-color: #fff3cd !important; }
        .status-pending { background-color: #f8d7da !important; }
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
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <a href="penerimaan.php" class="btn btn-secondary me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <h2 class="d-inline"><i class="bi bi-box-arrow-in-down"></i> Detail Penerimaan Barang</h2>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
                        <span class="text-muted ms-3">User: <?= $_SESSION['nama_user'] ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php if ($_GET['msg'] == 'status_updated') echo 'Status penerimaan berhasil diupdate!'; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Info Penerimaan -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Informasi Penerimaan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="40%"><strong>No Penerimaan</strong></td>
                                        <td>: <?= $penerimaan['no_penerimaan'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Penerimaan</strong></td>
                                        <td>: <?= date('d F Y', strtotime($penerimaan['tanggal_penerimaan'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>No Pengadaan</strong></td>
                                        <td>: <a href="pengadaan_detail.php?id=<?= $penerimaan['id_pengadaan'] ?>"><?= $penerimaan['no_pengadaan'] ?></a></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>: <span class="badge badge-<?= $penerimaan['status_penerimaan'] ?>"><?= strtoupper($penerimaan['status_penerimaan']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Diterima Oleh</strong></td>
                                        <td>: <?= $penerimaan['nama_user'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat Pada</strong></td>
                                        <td>: <?= date('d F Y H:i', strtotime($penerimaan['created_at'])) ?></td>
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
                                        <td>: <?= $penerimaan['nama_supplier'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Alamat</strong></td>
                                        <td>: <?= $penerimaan['alamat'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kota</strong></td>
                                        <td>: <?= $penerimaan['kota'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. Telepon</strong></td>
                                        <td>: <?= $penerimaan['no_telp'] ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td>: <?= $penerimaan['email'] ?? '-' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Pengadaan vs Penerimaan -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Status Pengadaan vs Penerimaan Barang</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Keterangan:</strong> Tabel ini menampilkan perbandingan antara jumlah yang diadakan dengan yang sudah diterima untuk setiap produk.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="12%">Kode Produk</th>
                                        <th width="30%">Nama Produk</th>
                                        <th width="10%">Satuan</th>
                                        <th width="12%">Qty Diadakan</th>
                                        <th width="12%">Qty Diterima</th>
                                        <th width="10%">Sisa</th>
                                        <th width="9%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($status = mysqli_fetch_assoc($status_result)): 
                                        // Tentukan status dan warna baris
                                        if ($status['sisa'] == 0) {
                                            $row_class = 'status-complete';
                                            $status_badge = '<span class="badge bg-success">LENGKAP</span>';
                                        } elseif ($status['total_diterima'] > 0 && $status['sisa'] > 0) {
                                            $row_class = 'status-partial';
                                            $status_badge = '<span class="badge bg-warning text-dark">SEBAGIAN</span>';
                                        } else {
                                            $row_class = 'status-pending';
                                            $status_badge = '<span class="badge bg-danger">BELUM</span>';
                                        }
                                        
                                        $persentase = ($status['qty_diadakan'] > 0) ? round(($status['total_diterima'] / $status['qty_diadakan']) * 100, 1) : 0;
                                    ?>
                                    <tr class="<?= $row_class ?>">
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $status['kode_produk'] ?></span></td>
                                        <td><strong><?= $status['nama_produk'] ?></strong></td>
                                        <td class="text-center"><?= $status['singkatan'] ?></td>
                                        <td class="text-center"><strong><?= $status['qty_diadakan'] ?></strong></td>
                                        <td class="text-center">
                                            <strong><?= $status['total_diterima'] ?></strong>
                                            <br><small class="text-muted">(<?= $persentase ?>%)</small>
                                        </td>
                                        <td class="text-center">
                                            <strong class="<?= $status['sisa'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= $status['sisa'] ?>
                                            </strong>
                                        </td>
                                        <td class="text-center"><?= $status_badge ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Legend -->
                        <div class="mt-3">
                            <h6><i class="bi bi-palette"></i> Keterangan Warna:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="p-2 status-complete border rounded mb-2">
                                        <strong>Hijau:</strong> Penerimaan Lengkap (Sisa = 0)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 status-partial border rounded mb-2">
                                        <strong>Kuning:</strong> Penerimaan Sebagian (Ada Sisa)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 status-pending border rounded mb-2">
                                        <strong>Merah:</strong> Belum Diterima Sama Sekali
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Penerimaan Kali Ini -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Detail Item yang Diterima (Penerimaan Ini)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="12%">Kode Produk</th>
                                        <th width="30%">Nama Produk</th>
                                        <th width="15%">Kategori</th>
                                        <th width="12%">Qty Diterima</th>
                                        <th width="10%">Satuan</th>
                                        <th width="16%">Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    mysqli_data_seek($detail_result, 0); // Reset pointer
                                    while ($detail = mysqli_fetch_assoc($detail_result)): 
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-info"><?= $detail['kode_produk'] ?></span></td>
                                        <td><strong><?= $detail['nama_produk'] ?></strong></td>
                                        <td><?= $detail['nama_kategori'] ?></td>
                                        <td class="text-center"><strong class="text-success"><?= $detail['qty_diterima'] ?></strong></td>
                                        <td class="text-center"><?= $detail['singkatan'] ?></td>
                                        <td class="text-center">
                                            <?php 
                                            $kondisi = $detail['kondisi'] ?? 'baik';
                                            if ($kondisi == 'baik') {
                                                echo '<span class="badge bg-success">BAIK</span>';
                                            } elseif ($kondisi == 'rusak') {
                                                echo '<span class="badge bg-danger">RUSAK</span>';
                                            } else {
                                                echo '<span class="badge bg-warning text-dark">CACAT</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Catatan -->
                <?php if ($penerimaan['catatan']): ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Catatan Penerimaan</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br($penerimaan['catatan']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Update Status - Hanya untuk Super Admin -->
                <?php if ($role == 'super_admin'): ?>
                <div class="card no-print">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Update Status Penerimaan (Super Admin)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                               <div class="col-md-4">
    <label class="form-label"><strong>Status Penerimaan</strong></label>
    <select class="form-select" name="status_penerimaan" required>
        <option value="pending" <?= $penerimaan['status_penerimaan'] == 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="diterima_sebagian" <?= $penerimaan['status_penerimaan'] == 'diterima_sebagian' ? 'selected' : '' ?>>Diterima Sebagian</option>
        <option value="diterima_lengkap" <?= $penerimaan['status_penerimaan'] == 'diterima_lengkap' ? 'selected' : '' ?>>Diterima Lengkap</option>
        <option value="ditolak" <?= $penerimaan['status_penerimaan'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
    </select>
</div>
                                <div class="col-md-8">
                                    <label class="form-label"><strong>Catatan</strong></label>
                                    <textarea class="form-control" name="catatan" rows="3" placeholder="Tambahkan catatan (opsional)..."><?= $penerimaan['catatan'] ?></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Status
                                </button>
                                <a href="penerimaan.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="d-flex gap-2">
    <?php if ($penerimaan['status_penerimaan'] == 'pending'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="status_penerimaan" value="diterima_lengkap">
            <input type="hidden" name="catatan" value="Barang diterima lengkap dengan baik oleh <?= $_SESSION['nama_user'] ?>">
            <button type="submit" name="update_status" class="btn btn-success" onclick="return confirm('Konfirmasi penerimaan lengkap?')">
                <i class="bi bi-check-circle"></i> Konfirmasi Penerimaan Lengkap
            </button>
        </form>
        <form method="POST" class="d-inline">
            <input type="hidden" name="status_penerimaan" value="diterima_sebagian">
            <input type="hidden" name="catatan" value="Barang diterima sebagian oleh <?= $_SESSION['nama_user'] ?>">
            <button type="submit" name="update_status" class="btn btn-warning" onclick="return confirm('Konfirmasi penerimaan sebagian?')">
                <i class="bi bi-box"></i> Penerimaan Sebagian
            </button>
        </form>
        <a href="penerimaan.php?delete=<?= $penerimaan['id_penerimaan'] ?>" 
           class="btn btn-danger" 
           onclick="return confirm('Yakin hapus penerimaan ini?')">
            <i class="bi bi-trash"></i> Hapus
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