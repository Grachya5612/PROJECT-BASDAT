<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Filter
$tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');
$id_customer = isset($_GET['id_customer']) ? $_GET['id_customer'] : '';

// Query laporan penjualan
$query = "SELECT pj.*, c.nama_customer, u.nama_user,
          (SELECT SUM(dp.qty) FROM detail_penjualan dp WHERE dp.id_penjualan = pj.id_penjualan) as total_qty
          FROM penjualan pj
          LEFT JOIN customer c ON pj.id_customer = c.id_customer
          LEFT JOIN users u ON pj.id_user = u.id_user
          WHERE pj.tanggal_penjualan BETWEEN '$tgl_dari' AND '$tgl_sampai'";

if ($id_customer) {
    $query .= " AND pj.id_customer = $id_customer";
}

$query .= " ORDER BY pj.tanggal_penjualan DESC, pj.id_penjualan DESC";
$result = mysqli_query($conn, $query);

// Hitung total
$total_transaksi = 0;
$total_item = 0;
$total_qty = 0;
$total_pendapatan = 0;

$data_penjualan = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_penjualan[] = $row;
    $total_transaksi++;
    $total_item += $row['total_item'];
    $total_qty += $row['total_qty'];
    $total_pendapatan += $row['grand_total'];
}

// Get customer list
$customers = mysqli_query($conn, "SELECT * FROM customer WHERE status = 'aktif' ORDER BY nama_customer");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .card-stat { transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        @media print {
            .no-print { display: none !important; }
            .sidebar { display: none !important; }
            .content { padding: 0 !important; }
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
                    <a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a>
                    <a href="satuan.php"><i class="bi bi-rulers"></i> Satuan</a>
                    <a href="supplier.php"><i class="bi bi-truck"></i> Supplier</a>
                    <a href="customer.php"><i class="bi bi-people"></i> Customer</a>
                    <a href="produk.php"><i class="bi bi-box-seam"></i> Produk</a>
                    <a href="pengadaan.php"><i class="bi bi-cart-plus"></i> Pengadaan</a>
                    <a href="penerimaan.php"><i class="bi bi-box-arrow-in-down"></i> Penerimaan</a>
                    <a href="penjualan.php"><i class="bi bi-cart-check"></i> Penjualan</a>
                    <a href="kartu_stok.php"><i class="bi bi-card-list"></i> Kartu Stok</a>
                    <a href="laporan.php" class="active"><i class="bi bi-file-earmark-text"></i> Laporan</a>
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
                    <h2><i class="bi bi-file-earmark-text"></i> Laporan Penjualan</h2>
                    <span class="text-muted">User: <?= $_SESSION['nama_user'] ?></span>
                </div>

                <!-- Filter -->
                <div class="card no-print">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-funnel"></i> Filter Laporan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Dari</label>
                                <input type="date" class="form-control" name="tgl_dari" value="<?= $tgl_dari ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Sampai</label>
                                <input type="date" class="form-control" name="tgl_sampai" value="<?= $tgl_sampai ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="id_customer">
                                    <option value="">Semua Customer</option>
                                    <?php while($cust = mysqli_fetch_assoc($customers)): ?>
                                    <option value="<?= $cust['id_customer'] ?>" <?= $id_customer == $cust['id_customer'] ? 'selected' : '' ?>>
                                        <?= $cust['nama_customer'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Tampilkan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card card-stat bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Transaksi</h6>
                                        <h2><?= $total_transaksi ?></h2>
                                    </div>
                                    <div><i class="bi bi-receipt" style="font-size: 3rem;"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Item</h6>
                                        <h2><?= $total_item ?></h2>
                                    </div>
                                    <div><i class="bi bi-box-seam" style="font-size: 3rem;"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Qty</h6>
                                        <h2><?= $total_qty ?></h2>
                                    </div>
                                    <div><i class="bi bi-123" style="font-size: 3rem;"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Pendapatan</h6>
                                        <h2>Rp <?= number_format($total_pendapatan/1000000, 1) ?>M</h2>
                                    </div>
                                    <div><i class="bi bi-cash-stack" style="font-size: 3rem;"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                        <h5><i class="bi bi-table"></i> Detail Laporan Penjualan</h5>
                        <button onclick="window.print()" class="btn btn-sm btn-light no-print">
                            <i class="bi bi-printer"></i> Print Laporan
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>No Invoice</th>
                                        <th>Customer</th>
                                        <th>Kasir</th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th>PPN</th>
                                        <th>Grand Total</th>
                                        <th class="no-print">Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach ($data_penjualan as $row): 
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_penjualan'])) ?></td>
                                        <td><strong><?= $row['no_penjualan'] ?></strong></td>
                                        <td><?= $row['nama_customer'] ?></td>
                                        <td><?= $row['nama_user'] ?></td>
                                        <td class="text-center"><?= $row['total_item'] ?></td>
                                        <td class="text-center"><?= $row['total_qty'] ?></td>
                                        <td class="text-end">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format($row['pajak'], 0, ',', '.') ?></td>
                                        <td class="text-end"><strong>Rp <?= number_format($row['grand_total'], 0, ',', '.') ?></strong></td>
                                        <td class="text-center no-print">
                                            <a href="penjualan_invoice.php?id=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="bi bi-file-earmark-pdf"></i> Lihat
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-success fw-bold">
                                        <td colspan="7" class="text-end">TOTAL:</td>
                                        <td class="text-end">Rp <?= number_format($total_pendapatan - ($total_pendapatan * 0.1 / 1.1), 0, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format($total_pendapatan * 0.1 / 1.1, 0, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                        <td class="no-print"></td>
                                    </tr>
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