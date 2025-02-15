<?php
$serverName = "localhost"; // Sesuaikan dengan server SQL Server Anda
$connectionOptions = [
    "Database" => "dbname", // Nama database
    "Uid" => "userdb",            // Username SQL Server
    "PWD" => "pasword"   // Password SQL Server 
];


$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("Koneksi gagal: " . print_r(sqlsrv_errors(), true));
}

$fromDate = "";
$toDate = "";
$condition = "";
$params = [];
$limit = 10;  // Data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Sorting (default: `created_at` DESC)
$allowedColumns = ['nomorrmr', 'nama', 'status_pasien', 'created_at'];
$sortBy = isset($_GET['sort']) && in_array($_GET['sort'], $allowedColumns) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
$toggleOrder = $order === 'ASC' ? 'desc' : 'asc';

// **Filter Berdasarkan Tanggal**
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['from_date'])) {
    if (!empty($_POST['from_date']) || !empty($_GET['from_date'])) {
        $fromDate = $_POST['from_date'] ?? $_GET['from_date'];
        $toDate = $_POST['to_date'] ?? $_GET['to_date'];

        // Konversi format tanggal agar sesuai dengan SQL Server
        $fromDateSQL = date('Y-m-d', strtotime($fromDate));
        $toDateSQL = date('Y-m-d', strtotime($toDate));

        $condition = "WHERE CONVERT(DATE, s.created_at) BETWEEN ? AND ?";
        $params = [$fromDateSQL, $toDateSQL];
    }
}

// ** Cek versi SQL Server **
$versionQuery = "SELECT CAST(SERVERPROPERTY('ProductVersion') AS VARCHAR(100)) AS version";
$versionStmt = sqlsrv_query($conn, $versionQuery);
$versionRow = sqlsrv_fetch_array($versionStmt, SQLSRV_FETCH_ASSOC);
$sqlVersion = explode(".", $versionRow['version'])[0]; // Ambil angka versi utama

// Jika SQL Server 2012+, gunakan OFFSET FETCH
if ((int)$sqlVersion >= 11) {
    $query = "SELECT s.nomorrm, p.nama, s.status_pasien, s.created_at 
              FROM TSurveys s   
              JOIN TPasien p ON s.nomorrm = p.nomorrm
              $condition 
              ORDER BY $sortBy $order 
              OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $paramsPagination = array_merge($params, [$offset, $limit]);

} else {
    // Jika SQL Server 2008 atau lebih lama, gunakan ROW_NUMBER()
    $startRow = ($page - 1) * $limit + 1;
    $endRow = $page * $limit;
    $query = "WITH Paginated AS (
                SELECT s.nomorrm, p.nama, s.status_pasien, s.created_at,
                       ROW_NUMBER() OVER (ORDER BY $sortBy $order) AS RowNum
                FROM TSurveys s 
                JOIN TPasien p ON s.nomorrm = p.nomorrm
                $condition
              )
              SELECT * FROM Paginated
              WHERE RowNum BETWEEN ? AND ?
              ORDER BY RowNum;";
    $paramsPagination = array_merge($params, [$startRow, $endRow]);
}
$stmt = sqlsrv_query($conn, $query, $paramsPagination);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Hitung total pasien
$countQuery = "SELECT COUNT(*) as total FROM TSurveys s $condition";
$countStmt = sqlsrv_query($conn, $countQuery, $params);
$countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
$totalSurveyed = $countRow['total'];
$totalPages = ceil($totalSurveyed / $limit);

// Hitung total pasien TERDUGA & TIDAK TERDUGA
$statusQuery = "SELECT 
                SUM(CASE WHEN s.status_pasien = 'TERDUGA TB' THEN 1 ELSE 0 END) AS total_terduga,
                SUM(CASE WHEN s.status_pasien = 'BUKAN TERDUGA TB' THEN 1 ELSE 0 END) AS total_tidak_terduga
                FROM TSurveys s $condition";
$statusStmt = sqlsrv_query($conn, $statusQuery, $params);
$statusRow = sqlsrv_fetch_array($statusStmt, SQLSRV_FETCH_ASSOC);
$totalterduga = $statusRow['total_terduga'] ?? 0;
$totalTidakterduga = $statusRow['total_tidak_terduga'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Survey Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 120px;
        }
        .back-button {
            display: flex;
            justify-content: start;
            margin-bottom: 10px;
        }
        .table th a {
            text-decoration: none;
            color: black;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <!-- Tombol Kembali ke Dashboard -->
    <div class="back-button">
        <a href="index.php" class="btn btn-secondary">⬅ Kembali ke Dashboard</a>
    </div>

    <!-- Logo -->
    <div class="logo-container">
        <img src="assets/RSPWDC.png" alt="Logo Survey" class="logo">
        <h2 class="mt-2">Laporan Screening Pasien</h2>
    </div>

    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-5">
                <label>Dari Tanggal:</label>
                <input type="date" name="from_date" class="form-control">
            </div>
            <div class="col-md-5">
                <label>Sampai Tanggal:</label>
                <input type="date" name="to_date" class="form-control">
            </div>
            <div class="col-md-2">
                <br>
                <button type="submit" class="btn btn-primary w-100 mt-2">Filter</button>
            </div>
        </div>
    </form>

    <p><strong>Total Pasien Dilakukan Screening:</strong> <?php echo $totalSurveyed; ?></p>
    <p><strong>Total Pasien TERDUGA TB</strong> <?php echo $totalterduga; ?></p>
    <p><strong>Total Pasien BUKAN TERDUGA TB:</strong> <?php echo $totalTidakterduga; ?></p>

    <table class="table table-bordered">
        <tr>
            <th>No</th>
            <th><a href="?sort=nomorrm&order=<?= $toggleOrder ?>">Nomor RM</a></th>
            <th><a href="?sort=nama&order=<?= $toggleOrder ?>">Nama</a></th>
            <th><a href="?sort=status_pasien&order=<?= $toggleOrder ?>">Status</a></th>
            <th><a href="?sort=created_at&order=<?= $toggleOrder ?>">Tanggal</a></th>
        </tr>
        <?php $no = $offset + 1; while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['nomorrm'] ?></td>
            <td><?= $row['nama'] ?></td>
            <td><?= $row['status_pasien'] ?></td>
            <td><?= $row['created_at']->format('Y-m-d') ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

</body>
</html>
