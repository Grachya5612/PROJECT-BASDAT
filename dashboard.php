<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil statistik
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE status='aktif'"))['total'];
$total_supplier = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier WHERE status='aktif'"))['total'];
$total_customer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM customer WHERE status='aktif'"))['total'];

// Stok menipis (dibawah stok minimal)
$stok_menipis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk WHERE stok_tersedia < stok_minimal AND status='aktif'"))['total'];

// Penjualan hari ini
$penjualan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, IFNULL(SUM(grand_total), 0) as pendapatan FROM penjualan WHERE DATE(tanggal_penjualan) = CURDATE()"));

// Penjualan bulan ini
$penjualan_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, IFNULL(SUM(grand_total), 0) as pendapatan FROM penjualan WHERE MONTH(tanggal_penjualan) = MONTH(CURDATE()) AND YEAR(tanggal_penjualan) = YEAR(CURDATE())"));

// Produk stok menipis
$query_stok = "SELECT p.*, k.nama_kategori, s.singkatan 
               FROM produk p
               LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori
               LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
               WHERE p.stok_tersedia < p.stok_minimal AND p.status = 'aktif'
               ORDER BY p.stok_tersedia ASC
               LIMIT 10";
$result_stok = mysqli_query($conn, $query_stok);

// Penjualan terbaru
$query_penjualan = "SELECT pj.*, c.nama_customer 
                    FROM penjualan pj
                    LEFT JOIN customer c ON pj.id_customer = c.id_customer
                    ORDER BY pj.created_at DESC
                    LIMIT 5";
$result_penjualan = mysqli_query($conn, $query_penjualan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); border-radius: 5px; }
        .content { padding: 30px; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .card-stat { transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        
        /* Card Menu Style - Gemes dengan Palette Hijau */
        .card-menu {
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            min-height: 180px;
        }
        
        .card-menu:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .card-menu .card-body {
            position: relative;
            z-index: 2;
            padding: 25px;
        }
        
        .card-menu .icon-circle {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .card-menu h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .card-menu p {
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .card-menu .arrow-icon {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 1.5rem;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .card-menu:hover .arrow-icon {
            opacity: 1;
            right: 15px;
        }
        
        /* Background Pattern */
        .card-menu::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
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
                    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="kategori.php"><i class="bi bi-tags"></i> Kategori</a>
                    <a href="satuan.php"><i class="bi bi-rulers"></i> Satuan</a>
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
                    <div>
                        <h2>Dashboard</h2>
                        <p class="text-muted">Selamat datang, <strong><?= $_SESSION['nama_user'] ?></strong></p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> <?= date('d F Y') ?><br>
                            <i class="bi bi-clock"></i> <?= date('H:i:s') ?> WIB
                        </small>
                    </div>
                </div>

                <!-- Menu Cards -->
                <div class="row g-3 mb-4">
                    <!-- Card 1: Kategori -->
                    <div class="col-md-3">
                        <a href="kategori.php" class="text-decoration-none">
                            <div class="card card-menu bg-primary text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-tags" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kategori_produk WHERE status='aktif'")) ?></h2>
                                    <p class="mb-0 opacity-75">Kategori Produk</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 2: Produk -->
                    <div class="col-md-3">
                        <a href="produk.php" class="text-decoration-none">
                            <div class="card card-menu bg-success text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= $total_produk ?></h2>
                                    <p class="mb-0 opacity-75">Total Produk</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 3: Supplier -->
                    <div class="col-md-3">
                        <a href="supplier.php" class="text-decoration-none">
                            <div class="card card-menu bg-info text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-truck" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= $total_supplier ?></h2>
                                    <p class="mb-0 opacity-75">Supplier Aktif</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 4: Customer -->
                    <div class="col-md-3">
                        <a href="customer.php" class="text-decoration-none">
                            <div class="card card-menu bg-warning text-dark">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= $total_customer ?></h2>
                                    <p class="mb-0 opacity-75">Customer Aktif</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Card 5: Pengadaan -->
                    <div class="col-md-3">
                        <a href="pengadaan.php" class="text-decoration-none">
                            <div class="card card-menu bg-secondary text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-cart-plus" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM pengadaan WHERE MONTH(tanggal_pengadaan) = MONTH(CURDATE())")) ?></h2>
                                    <p class="mb-0 opacity-75">Pengadaan Bulan Ini</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 6: Penerimaan -->
                    <div class="col-md-3">
                        <a href="penerimaan.php" class="text-decoration-none">
                            <div class="card card-menu bg-dark text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-box-arrow-in-down" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM penerimaan WHERE MONTH(tanggal_penerimaan) = MONTH(CURDATE())")) ?></h2>
                                    <p class="mb-0 opacity-75">Penerimaan Bulan Ini</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 7: Penjualan -->
                    <div class="col-md-3">
                        <a href="penjualan.php" class="text-decoration-none">
                            <div class="card card-menu bg-danger text-white">
                                <div class="card-body">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-cart-check" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><?= $penjualan_bulan_ini['total'] ?></h2>
                                    <p class="mb-0 opacity-75">Penjualan Bulan Ini</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 8: Laporan -->
                    <div class="col-md-3">
                        <a href="laporan.php" class="text-decoration-none">
                            <div class="card card-menu" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body text-white">
                                    <div class="icon-circle mb-3">
                                        <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h2 class="mb-0"><i class="bi bi-graph-up"></i></h2>
                                    <p class="mb-0 opacity-75">Laporan Penjualan</p>
                                    <div class="arrow-icon">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Penjualan Hari Ini & Bulan Ini -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="bi bi-calendar-day"></i> Penjualan Hari Ini</h5>
                            </div>
                            <div class="card-body text-center">
                                <h1 class="text-success"><?= $penjualan_hari_ini['total'] ?></h1>
                                <p class="text-muted">Transaksi</p>
                                <hr>
                                <h3>Rp <?= number_format($penjualan_hari_ini['pendapatan'], 0, ',', '.') ?></h3>
                                <small class="text-muted">Total Pendapatan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="bi bi-calendar-month"></i> Penjualan Bulan Ini</h5>
                            </div>
                            <div class="card-body text-center">
                                <h1 class="text-primary"><?= $penjualan_bulan_ini['total'] ?></h1>
                                <p class="text-muted">Transaksi</p>
                                <hr>
                                <h3>Rp <?= number_format($penjualan_bulan_ini['pendapatan'], 0, ',', '.') ?></h3>
                                <small class="text-muted">Total Pendapatan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik Penjualan 7 Hari Terakhir -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="bi bi-bar-chart-line"></i> Grafik Penjualan 7 Hari Terakhir</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartPenjualan" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Produk Terlaris -->
                <?php
                $query_top = "SELECT p.nama_produk, SUM(dp.qty) as total_terjual, SUM(dp.subtotal) as total_nilai
                              FROM detail_penjualan dp
                              JOIN produk p ON dp.id_produk = p.id_produk
                              JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
                              WHERE MONTH(pj.tanggal_penjualan) = MONTH(CURDATE())
                              GROUP BY dp.id_produk
                              ORDER BY total_terjual DESC
                              LIMIT 5";
                $result_top = mysqli_query($conn, $query_top);
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5><i class="bi bi-trophy"></i> Top 5 Produk Terlaris Bulan Ini</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Ranking</th>
                                                <th>Nama Produk</th>
                                                <th>Jumlah Terjual</th>
                                                <th>Total Nilai Penjualan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $rank = 1;
                                            $badge_colors = ['bg-warning', 'bg-secondary', 'bg-info', 'bg-primary', 'bg-success'];
                                            while($top = mysqli_fetch_assoc($result_top)): 
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $badge_colors[$rank-1] ?>" style="font-size: 16px;">
                                                        #<?= $rank++ ?>
                                                    </span>
                                                </td>
                                                <td><strong><?= $top['nama_produk'] ?></strong></td>
                                                <td><span class="badge bg-primary"><?= $top['total_terjual'] ?> unit</span></td>
                                                <td>Rp <?= number_format($top['total_nilai'], 0, ',', '.') ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stok Menipis & Penjualan Terbaru -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5><i class="bi bi-exclamation-triangle"></i> Produk Stok Menipis</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Stok</th>
                                                <th>Min</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($result_stok)): ?>
                                            <tr>
                                                <td><small><?= $row['nama_produk'] ?></small></td>
                                                <td><span class="badge bg-danger"><?= $row['stok_tersedia'] ?></span></td>
                                                <td><?= $row['stok_minimal'] ?></td>
                                                <td><span class="badge bg-warning text-dark">Restock!</span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="bi bi-clock-history"></i> Penjualan Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>No Invoice</th>
                                                <th>Customer</th>
                                                <th>Total</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($result_penjualan)): ?>
                                            <tr>
                                                <td><small><strong><?= $row['no_penjualan'] ?></strong></small></td>
                                                <td><small><?= $row['nama_customer'] ?></small></td>
                                                <td><small>Rp <?= number_format($row['grand_total'], 0, ',', '.') ?></small></td>
                                                <td><small><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data penjualan 7 hari terakhir
        <?php
        $labels = [];
        $data_transaksi = [];
        $data_pendapatan = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-$i days"));
            $tanggal_indo = date('d/m', strtotime("-$i days"));
            
            $query = "SELECT COUNT(*) as total, IFNULL(SUM(grand_total), 0) as pendapatan 
                      FROM penjualan 
                      WHERE DATE(tanggal_penjualan) = '$tanggal'";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            $labels[] = $tanggal_indo;
            $data_transaksi[] = $row['total'];
            $data_pendapatan[] = $row['pendapatan'];
        }
        ?>
        
        const ctx = document.getElementById('chartPenjualan').getContext('2d');
        const chartPenjualan = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: <?= json_encode($data_transaksi) ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                }, {
                    label: 'Pendapatan (Rp)',
                    data: <?= json_encode($data_pendapatan) ?>,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tren Penjualan'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Jumlah Transaksi'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Pendapatan (Rp)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>