<?php 
require_once 'business_model.php';  

$hours = [
    'Sunday'    => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Monday'    => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Tuesday'   => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Wednesday' => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Thursday'  => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Friday'    => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
    'Saturday'  => ['open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false]
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone   = $_POST['phone'] ?? '';

    if (!preg_match('/^\+?[0-9\- ]+$/', $phone)) {
        die("Invalid phone number format. Allowed: numbers, +, -, and spaces.");
    }

    $business_id = saveBusinessProfile(0, $name, $address, $phone, null, null, null);

    if ($business_id) {
        $business_hours = [];
        if (isset($_POST['business_hours'])) {
            foreach ($_POST['business_hours'] as $day => $data) {
                if (isset($data['is_closed'])) {
                    $data['open_time'] = null;
                    $data['close_time'] = null;
                } elseif ($data['open_time'] >= $data['close_time']) {
                    die("Error: On $day, opening time must be before closing time.");
                }
                
                $business_hours[] = [
                    'business_id' => $business_id,
                    'day_of_week' => $day,
                    'open_time'   => $data['open_time'] ?? null,
                    'close_time'  => $data['close_time'] ?? null,
                    'is_closed'   => isset($data['is_closed']) ? 1 : 0
                ];
            }
        }
        saveBusinessHours($business_id, $business_hours);
    }

    header('Location: business_manage.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
        }
        .form-label {
            font-weight: bold;
        }
        .form-section {
            margin-top: 30px;
        }
        .card {
            border-radius: 12px;
            padding: 25px;
            background-color: #fff;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            font-size: 1.1rem;
            border-radius: 10px;
            padding: 12px;
        }
        .btn-primary {
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .time-field {
            display: flex;
        }
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4 text-primary">Create New Business</h1>
    <form id="businessForm" action="business_create.php" method="POST">
        
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Business Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required
                           pattern="^\+?[0-9\- ]+$" oninput="validatePhone(this)">
                    <small class="text-muted">Only numbers, dashes (-), spaces, and + are allowed.</small>
                </div>
            </div>
        </div>

        <div class="form-section card mt-4">
            <div class="card-body">
                <h4 class="section-title">Business Hours</h4>
                <?php foreach ($hours as $day => $hour): ?>
                    <div class="mb-3 row business-hour-row">
                        <label class="form-label col-sm-2"><?= $day ?></label>
                        <div class="col-sm-3 time-field">
                            <input type="time" class="form-control open-time" name="business_hours[<?= $day ?>][open_time]"
                                   value="<?= $hour['open_time'] ?>">
                        </div>
                        <div class="col-sm-3 time-field">
                            <input type="time" class="form-control close-time" name="business_hours[<?= $day ?>][close_time]"
                                   value="<?= $hour['close_time'] ?>">
                        </div>
                        <div class="col-sm-2 form-check d-flex align-items-center">
                            <input type="checkbox" class="form-check-input is-closed" name="business_hours[<?= $day ?>][is_closed]">
                            <label class="form-check-label">Closed</label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary w-100">Create Business</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function validatePhone(input) {
    input.value = input.value.replace(/[^0-9+\- ]/g, '');
}

document.querySelectorAll('.business-hour-row').forEach(row => {
    const closedCheckbox = row.querySelector('.is-closed');
    const timeFields = row.querySelectorAll('.time-field');

    function toggleTimeFields() {
        timeFields.forEach(field => {
            field.classList.toggle('hidden', closedCheckbox.checked);
        });
    }

    closedCheckbox.addEventListener('change', toggleTimeFields);
    toggleTimeFields();
});

document.getElementById('businessForm').addEventListener('submit', function(event) {
    document.querySelectorAll('.business-hour-row').forEach(row => {
        const openTime = row.querySelector('.open-time').value;
        const closeTime = row.querySelector('.close-time').value;
        const isClosed = row.querySelector('.is-closed').checked;

        if (!isClosed && openTime >= closeTime) {
            alert(`Error: On ${row.querySelector('.form-label').innerText}, opening time must be before closing time.`);
            event.preventDefault();
        }
    });
});
</script>

</body>
</html>
