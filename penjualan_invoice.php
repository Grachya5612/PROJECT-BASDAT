<?php
session_start();
require_once 'config.php';

$id_penjualan = $_GET['id'] ?? 0;

// Get data penjualan
$query = "SELECT p.*, c.*, u.nama_user 
          FROM penjualan p
          LEFT JOIN customer c ON p.id_customer = c.id_customer
          LEFT JOIN users u ON p.id_user = u.id_user
          WHERE p.id_penjualan = $id_penjualan";
$result = mysqli_query($conn, $query);
$penjualan = mysqli_fetch_assoc($result);

// Get detail penjualan
$query_detail = "SELECT dp.*, p.kode_produk, p.nama_produk, s.singkatan as satuan
                 FROM detail_penjualan dp
                 LEFT JOIN produk p ON dp.id_produk = p.id_produk
                 LEFT JOIN satuan s ON p.id_satuan = s.id_satuan
                 WHERE dp.id_penjualan = $id_penjualan";
$detail = mysqli_query($conn, $query_detail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= $penjualan['no_penjualan'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        body { background-color: #f8f9fa; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; background: white; padding: 30px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .invoice-header { border-bottom: 3px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .table-invoice { font-size: 14px; }
        .table-invoice th { background-color: #333; color: white; }
        .company-info { text-align: right; }
        .customer-info { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 style="margin: 0; font-size: 32px; color: #333;">INVOICE</h1>
                    <h3 style="margin: 5px 0; color: #667eea;">ELEKTRONIK STORE</h3>
                    <p style="margin: 0; font-size: 13px;">
                        Jl. Contoh No. 123, Surabaya<br>
                        Telp: (031) 1234567<br>
                        Email: info@elektronikstore.com
                    </p>
                </div>
                <div class="col-md-6 company-info">
                    <h4 style="margin: 0;">No Invoice</h4>
                    <h2 style="margin: 5px 0; color: #667eea;"><?= $penjualan['no_penjualan'] ?></h2>
                    <p style="margin: 0;">
                        <strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($penjualan['tanggal_penjualan'])) ?><br>
                        <strong>Kasir:</strong> <?= $penjualan['nama_user'] ?><br>
                        <strong>Pembayaran:</strong> <?= strtoupper($penjualan['metode_pembayaran']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="customer-info">
            <div class="row">
                <div class="col-md-6">
                    <h5>Kepada Yth:</h5>
                    <strong><?= $penjualan['nama_customer'] ?></strong><br>
                    <?= $penjualan['alamat'] ?? '-' ?><br>
                    <?= $penjualan['kota'] ?? '' ?><br>
                    Telp: <?= $penjualan['no_telp'] ?? '-' ?>
                </div>
            </div>
        </div>

        <!-- Detail Items -->
        <table class="table table-bordered table-invoice">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Kode</th>
                    <th width="40%">Nama Produk</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Harga</th>
                    <th width="15%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($item = mysqli_fetch_assoc($detail)): 
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $item['kode_produk'] ?></td>
                    <td><?= $item['nama_produk'] ?></td>
                    <td class="text-center"><?= $item['qty'] ?> <?= $item['satuan'] ?></td>
                    <td class="text-end">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="row mt-4">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-end">Rp <?= number_format($penjualan['total_harga'], 0, ',', '.') ?></td>
                    </tr>
                    <?php if ($penjualan['diskon'] > 0): ?>
                    <tr>
                        <td><strong>Diskon:</strong></td>
                        <td class="text-end">- Rp <?= number_format($penjualan['diskon'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>PPN (10%):</strong></td>
                        <td class="text-end">Rp <?= number_format($penjualan['pajak'], 0, ',', '.') ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #333;">
                        <td><h4><strong>GRAND TOTAL:</strong></h4></td>
                        <td class="text-end"><h4><strong>Rp <?= number_format($penjualan['grand_total'], 0, ',', '.') ?></strong></h4></td>
                    </tr>
                    <tr>
                        <td><strong>Bayar:</strong></td>
                        <td class="text-end">Rp <?= number_format($penjualan['bayar'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kembalian:</strong></td>
                        <td class="text-end">Rp <?= number_format($penjualan['kembalian'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-5" style="border-top: 1px solid #ddd; padding-top: 20px;">
            <div class="row">
                <div class="col-md-6">
                    <p style="font-size: 12px; color: #666;">
                        <strong>Catatan:</strong><br>
                        Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kesalahan dari pihak kami.<br>
                        Terima kasih atas kepercayaan Anda!
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p style="margin-top: 50px;">
                        <strong>_____________________</strong><br>
                        Tanda Tangan & Stempel
                    </p>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg">
                <i class="bi bi-printer"></i> Cetak Invoice
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">
                Tutup
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>