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

$rmNumber = "";
$patientName = "";
$surveySubmitted = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_rm'])) {
        $rmNumber = $_POST['nomorrm'];
        $query = "SELECT nama FROM TPasien WHERE nomorrm = ?";
        $params = [$rmNumber];
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $patientName = $row['nama'];
        } else {
            echo '<div class="alert alert-danger text-center">Nomor RM tidak ditemukan.</div>';
        }
    }

    if (isset($_POST['submit_survey'])) {
        $rmNumber = $_POST['nomorrm'];
        $answers = [
            $_POST['question1'],
            $_POST['question2'],
            $_POST['question3'],
            $_POST['question4'],
            $_POST['question5']
        ];

        $status = (in_array("1", $answers)) ? "BUKAN TERDUGA TB" : "TERDUGA TB";

        $query = "INSERT INTO TSurveys (nomorrm, question1, question2, question3, question4, question5, status_pasien, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())";
        $params = array_merge([$rmNumber], $answers, [$status]);
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt) {
            $surveySubmitted = true;
        } else {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Survey Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 650px;
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
        .form-check-label {
            font-size: 16px;
            font-weight: 500;
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
        <h2 class="mt-2">Survey Pasien</h2>
    </div>

    <!-- Form Input Nomor RM -->
    <form method="POST" class="mb-3">
        <div class="mb-3">
            <label class="form-label">Masukkan Nomor RM:</label>
            <input type="text" name="nomorrm" class="form-control" required value="<?php echo htmlspecialchars($rmNumber); ?>">
        </div>
        <button type="submit" name="search_rm" class="btn btn-primary w-100">Cari</button>
    </form>

    <?php if (!empty($patientName)): ?>
        <div class="alert alert-success text-center">
            <strong>Nama Pasien:</strong> <?php echo htmlspecialchars($patientName); ?>
        </div>

        <!-- Form Survey -->
        <form method="POST">
            <input type="hidden" name="nomorrm" value="<?php echo htmlspecialchars($rmNumber); ?>">
            
            <?php
            $questions = [
                "Apakah Anda batuk lebih dari 2 minggu?",
                "Apakah Anda batuk darah?",
                "Apakah BB Anda turun/tidak naik dalam 2 bulan terakhir?",
                "Apakah Anda demam lebih dari 2 minggu?",
                "Apakah Anda lesu/malaise, anak kurang aktif bermain?"
            ];
            foreach ($questions as $index => $question) {
                $qIndex = $index + 1;
                echo "
                <div class='mb-3'>
                    <label class='form-label'>$qIndex. $question</label>
                    <div class='form-check'>
                        <input type='radio' name='question$qIndex' value='1' required class='form-check-input' id='q$qIndex-ya'>
                        <label class='form-check-label' for='q$qIndex-ya'>Ya</label>
                    </div>
                    <div class='form-check'>
                        <input type='radio' name='question$qIndex' value='0' required class='form-check-input' id='q$qIndex-tidak'>
                        <label class='form-check-label' for='q$qIndex-tidak'>Tidak</label>
                    </div>
                </div>";
            }
            ?>

            <button type="submit" name="submit_survey" class="btn btn-success w-100">Kirim Jawaban</button>
        </form>
    <?php endif; ?>

    <?php if ($surveySubmitted): ?>
        <div class="alert alert-success text-center mt-3">Survey berhasil disimpan!</div>
    <?php endif; ?>
</div>

</body>
</html>
