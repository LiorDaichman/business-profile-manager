<?php
require_once 'business_model.php';
require_once 's3_helper.php'; 

// Get selected business ID from URL or default to the first business
$businesses = getAllBusinesses();
$business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : ($businesses[0]['id'] ?? 0);
$profile = getBusinessProfile($business_id);
$hours = getBusinessHours($business_id);

// Helper function to format time (HH:mm)
function formatTime($time) {
    return date("H:i", strtotime($time)); // Ensures '09:00' in 24-hour format
}

// Prepare business hours for FullCalendar
$events = [];
$dayMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($hours as $hour) {
    $dayIndex = array_search($hour['day_of_week'], $dayMap);

    if ($hour['is_closed']) {
        $events[] = [
            'daysOfWeek' => [$dayIndex],
            'startTime' => "00:00",
            'endTime' => "23:59",
            'color' => "#d9534f",  
            'is_closed' => true
        ];
    } else {
        $openTime = formatTime($hour['open_time']);
        $closeTime = formatTime($hour['close_time']);
        $events[] = [
            'daysOfWeek' => [$dayIndex],
            'startTime' => $openTime,
            'endTime' => $closeTime,
            'title' => "$openTime - $closeTime",
            'color' => "#5cb85c",  
            'is_closed' => false
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Profile</title>

    <!-- Fonts, Bootstrap, FullCalendar CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">

    <!-- jQuery, FullCalendar JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fa;
            color: #495057;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1140px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 50px;
            margin-top: 60px;
        }

        h1, h2, h3 {
            color: #343a40;
        }

        p {
            color: #6c757d;
        }

        /* Remove or comment out the global "width: 100%" on all img
           so that images won't automatically stretch */
        img {
            border-radius: 12px;
            margin-top: 20px;
            /* width: 100%;  <-- remove or comment this out */
        }

        /* Specifically target the header image to limit its maximum size */
        .header-image {
            display: block;
            max-width: 600px;  /* adjust to your preferred width */
            max-height: 400px; /* optional: limits height if very tall */
            width: auto;       /* let the browser scale it proportionally */
            height: auto;
            object-fit: cover; /* crops if needed, but only if container is smaller */
            margin: 0 auto;    /* center the image horizontally */
        }

        #calendar {
            margin-top: 40px;
        }

        .fc-event {
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
            text-align: center;
            padding: 5px;
        }

        .fc-event-closed {
            background-color: #dc3545 !important;
        }

        .fc-event-open {
            background-color: #28a745 !important;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .form-select, .btn {
            border-radius: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .select2-container {
            width: 100% !important;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="mt-4 mb-4 text-center">Business Profile</h1>

    <!-- Business Selection Form -->
    <form method="GET" class="mb-4">
        <div class="mb-3">
            <label for="businessSelect" class="form-label">Select Business</label>
            <select id="businessSelect" name="business_id" class="form-select" onchange="this.form.submit()">
                <option value="">Select a Business</option>
                <?php foreach ($businesses as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $b['id'] == $business_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Business Info Card -->
    <div class="card shadow-sm p-3 mb-4">
        <h2 class="text-center"><?= htmlspecialchars($profile['name'] ?? 'Business Name') ?></h2>
        <p class="text-center">
            <strong>Address:</strong> 
            <?= htmlspecialchars($profile['address'] ?? 'Not Available') ?>
        </p>
        <p class="text-center">
            <strong>Phone:</strong> 
            <?= htmlspecialchars($profile['phone'] ?? 'Not Available') ?>
        </p>

        <!-- Display the Header Image if it exists -->
        <?php if (!empty($profile['header_image'])): ?>
            <img 
                src="<?= getS3ImageUrl($profile['header_image']) ?>" 
                alt="Business Header"
                class="header-image"
            >
        <?php endif; ?>
    </div>

    <h3 class="mt-4 text-center">Business Hours</h3>
    <div id="calendar"></div>

    <!-- Admin Login Button -->
    <div class="mt-4 text-center">
        <a href="admin_login.php" class="btn btn-primary">Go to Admin Login</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        allDaySlot: false,
        contentHeight: 'auto',
        nowIndicator: true,
        slotMinTime: "00:00:00",
        slotMaxTime: "23:59:59",
        slotDuration: "01:00:00",
        expandRows: true,
        events: <?= json_encode($events) ?>,
        eventTextColor: "#ffffff",
        eventDidMount: function (info) {
            if (info.event.extendedProps.is_closed) {
                info.el.classList.add("fc-event-closed");
                info.el.innerHTML = "Closed";
            } else {
                info.el.classList.add("fc-event-open");
                info.el.innerHTML = info.event.title;
            }
        }
    });
    calendar.render();
});
</script>

</body>
</html>
