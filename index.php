<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Survey Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .dashboard-card {
            width: 80%;
            max-width: 500px;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .dashboard-button {
            margin-top: 10px;
            width: 100%;
        }
        .logo {
            width: 120px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-card">
        <img src="assets/RSPWDC.png" alt="Logo Survey" class="logo">
        <h2>Dashboard Screening Pasien</h2>
        <p>Silakan pilih menu:</p>

        <a href="surver1.php" class="btn btn-primary dashboard-button">📝 Input Survey</a>
        <a href="lapor.php" class="btn btn-success dashboard-button">📊 Lihat Laporan</a>
    </div>
</div>
</body>
</html>
