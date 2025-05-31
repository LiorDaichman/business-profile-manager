<?php
require_once 'db.php';
require_once 's3_helper.php';


function getDefaultBusinessProfile() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM business_profile LIMIT 1");
    return $stmt->fetch();
}


function getBusinessProfile($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM business_profile WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}


function saveBusinessProfile(
    $business_id,
    $name,
    $address,
    $phone,
    $header_image_file = null,
    $edited_image_data = null,
    $header_image_filename = null
) {
    global $pdo;


    $existing = getBusinessProfile($business_id);

    if ($existing) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE business_profile
            SET name = ?, address = ?, phone = ?, header_image = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $address,
            $phone,
            $header_image_filename, // Only store the final S3 filename
            $business_id
        ]);
        return $business_id;

    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO business_profile 
            (name, address, phone, header_image)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $address,
            $phone,
            $header_image_filename
        ]);
        return $pdo->lastInsertId();
    }
}


function getAllBusinesses() {
    global $pdo;
    return $pdo->query("SELECT * FROM business_profile")->fetchAll();
}


function getBusinessHours($business_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT *
        FROM business_hours
        WHERE business_id = ?
        ORDER BY FIELD(
          day_of_week,
          'Sunday','Monday','Tuesday','Wednesday',
          'Thursday','Friday','Saturday'
        )
    ");
    $stmt->execute([$business_id]);
    return $stmt->fetchAll();
}


function saveBusinessHours($business_id, $hoursData) {
    global $pdo;
    // Remove old hours
    $stmt = $pdo->prepare("DELETE FROM business_hours WHERE business_id = ?");
    $stmt->execute([$business_id]);

    // Insert or re-insert hours
    foreach ($hoursData as $hour) {
        if ($hour['is_closed']) {
            $hour['open_time']  = null;
            $hour['close_time'] = null;
        }
        $stmt = $pdo->prepare("
            INSERT INTO business_hours
            (business_id, day_of_week, open_time, close_time, is_closed)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $business_id,
            $hour['day_of_week'],
            $hour['open_time'],
            $hour['close_time'],
            $hour['is_closed']
        ]);
    }
}
