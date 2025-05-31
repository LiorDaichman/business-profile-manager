<?php
require_once 'business_model.php';

$businesses = getAllBusinesses();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
        }
        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-select, .btn {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p class="lead text-muted">Manage your businesses from here</p>
    </div>

    <div class="card p-4">
        <h3 class="mb-4 text-center">Select a Business to Edit</h3>
        <form method="GET" action="business_edit.php">
            <div class="mb-3">
                <label for="businessSelect" class="form-label">Business</label>
                <select id="businessSelect" name="business_id" class="form-select" required>
                    <option value="">Select a Business</option>
                    <?php foreach ($businesses as $business): ?>
                        <option value="<?= $business['id'] ?>"><?= htmlspecialchars($business['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Edit Business</button>
            </div>
        </form>
    </div>

    <div class="text-center mt-4">
        <a href="business_create.php" class="btn btn-success">Create New Business</a>
    </div>

    <div class="text-center mt-4">
        <a href="http://localhost:8000/" class="btn btn-secondary">Back to Homepage</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
