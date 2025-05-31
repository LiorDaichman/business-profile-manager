<?php
require_once 'business_model.php';
require_once 's3_helper.php';
require 'vendor/autoload.php'; 


$business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
$business = getBusinessProfile($business_id); 
$hours   = getBusinessHours($business_id);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    $business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : 0;
    $name    = $_POST['name'];
    $address = $_POST['address'];
    $phone   = $_POST['phone'];

    
    $header_image_filename = $_POST['header_image_filename'] ?? null;
    $header_image_file     = null; 

    
    if ($business_id === 0) {
        $business_id = saveBusinessProfile(
            $business_id, 
            $name, 
            $address, 
            $phone,
            $header_image_file,
            null,
            $header_image_filename
        );
    } else {
        saveBusinessProfile(
            $business_id, 
            $name, 
            $address, 
            $phone,
            $header_image_file,
            null,
            $header_image_filename
        );
    }

    
    $business_hours = [];
    if (isset($_POST['business_hours'])) {
        foreach ($_POST['business_hours'] as $day => $data) {
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

    // Check if AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success'     => true,
            'message'     => 'Profile saved successfully',
            'business_id' => $business_id
        ]);
        exit;
    }

    
    header('Location: admin_dashboard.php');
    exit();
}

// Handle AJAX image upload
if (isset($_GET['action']) && $_GET['action'] === 'upload_image') {
    header('Content-Type: application/json');

    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
        $file                 = $_FILES['header_image'];
        $business_id_for_file = intval($_POST['business_id'] ?? 0);

        // Upload to S3
        $filename = uploadFileToS3($file, $business_id_for_file);
        if ($filename) {
            echo json_encode([
                'success'  => true,
                'filename' => $filename,
                'url'      => getS3ImageUrl($filename)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded or error occurred'
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Business Profile</title>

  <!-- Bootstrap CSS -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
    rel="stylesheet"
  >
  <style>
    body {
      background-color: #f4f6f9;
    }
    .container {
      max-width: 900px;
      margin-top: 50px;
    }
    .card {
      border-radius: 12px;
      padding: 25px;
      background-color: #fff;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    .section-title {
      font-size: 1.6rem;
      font-weight: 600;
      color: #343a40;
      margin-bottom: 20px;
    }
    .image-preview {
      max-width: 100%;
      max-height: 300px;
      border-radius: 8px;
      margin-top: 15px;
    }
    .image-actions {
      margin-top: 15px;
    }
    .time-field {
      /* We can optionally animate the display, but here we just show/hide. */
    }
  </style>
</head>
<body>
<div class="container">
  <h1 class="text-center mb-4 text-primary">Edit Business Profile</h1>

  <form id="businessForm" action="business_edit.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="business_id" value="<?= $business_id ?>">

    <!-- Final S3 filename after AJAX upload -->
    <input type="hidden" name="header_image_filename" id="header_image_filename"
           value="<?= htmlspecialchars($business['header_image'] ?? '') ?>">

    <!-- Business Details -->
    <div class="card">
      <div class="card-body">
        <div class="mb-3">
          <label for="name" class="form-label">Business Name</label>
          <input type="text" class="form-control" id="name" name="name"
                 value="<?= htmlspecialchars($business['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Address</label>
          <input type="text" class="form-control" id="address" name="address"
                 value="<?= htmlspecialchars($business['address'] ?? '') ?>" required>
        </div>
		<div class="mb-3">
		  <label for="phone" class="form-label">Phone</label>
		  <input type="text" class="form-control" id="phone" name="phone"
				 value="<?= htmlspecialchars($business['phone'] ?? '') ?>" 
				 pattern="^\+?[0-9\- ]+$" 
				 required
				 oninput="validatePhone(this)">
		  <small class="text-muted">Only numbers, dashes (-), spaces, and + are allowed.</small>
		</div>
        <!-- Header Image Input -->
        <div class="mb-3">
          <label for="header_image" class="form-label">Header Image</label>
          <input type="file" class="form-control" id="header_image" name="header_image" accept="image/*">

          <!-- Image Preview -->
<!-- Image Preview -->
<?php
  $hasImage = !empty($business['header_image']);
  $previewUrl = $hasImage ? getS3ImageUrl($business['header_image']) : '';
?>
<div 
  id="image-preview-container" 
  class="mt-3 <?= $hasImage ? '' : 'd-none' ?>"
>
  <img 
    id="current-image" 
    src="<?= $previewUrl ?>" 
    class="image-preview" 
    alt="Header Image"
  >
  
  <!-- Add this div for image actions -->
  <div class="image-actions mt-2">
    <?php if ($hasImage): ?>
    <a 
      href="image_editor.php?business_id=<?= $business_id ?>&filename=<?= urlencode($business['header_image']) ?>" 
      class="btn btn-primary btn-sm"
    >
      Edit Image
    </a>
    <button 
      id="remove-image-btn" 
      class="btn btn-danger btn-sm"
    >
      Remove Image
    </button>
    <?php endif; ?>
  </div>
</div>

      </div>
    </div>

    <!-- Business Hours Section -->
    <div class="card mt-4">
      <div class="card-body">
        <h4 class="section-title">Business Hours</h4>
        <?php
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $hoursMap = [];
        if ($hours) {
            foreach ($hours as $hr) {
                $hoursMap[$hr['day_of_week']] = $hr;
            }
        }

        foreach ($days as $day):
          $openTime  = $hoursMap[$day]['open_time']  ?? '09:00';
          $closeTime = $hoursMap[$day]['close_time'] ?? '17:00';
          $isClosed  = !empty($hoursMap[$day]['is_closed']) ? 'checked' : '';
        ?>
        <div 
          class="row align-items-center mb-3 business-hour-row"
          data-day="<?= $day ?>"
        >
          <label class="form-label col-sm-2"><?= $day ?></label>

          <!-- Time fields wrapped in .time-field containers -->
          <div class="col-sm-3 time-field">
            <input type="time"
                   class="form-control open-time"
                   name="business_hours[<?= $day ?>][open_time]"
                   value="<?= $openTime ?>">
          </div>
          <div class="col-sm-3 time-field">
            <input type="time"
                   class="form-control close-time"
                   name="business_hours[<?= $day ?>][close_time]"
                   value="<?= $closeTime ?>">
          </div>

          <div class="col-sm-2 form-check">
            <input type="checkbox"
                   class="form-check-input is-closed"
                   name="business_hours[<?= $day ?>][is_closed]"
                   <?= $isClosed ?>>
            <label class="form-check-label ms-2">Closed</label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Save Button -->
    <div class="mt-4">
      <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </div>
  </form>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<script>
function validatePhone(input) {
    input.value = input.value.replace(/[^0-9+\- ]/g, '');
}

$(function() {
  let currentFile = null;
   // Check for new image parameter (from editor redirect)
  const urlParams = new URLSearchParams(window.location.search);
  const newImage = urlParams.get('new_image');
  if (newImage) {
    // Update the hidden field with the new filename
    $('#header_image_filename').val(newImage);
    
    // Update the preview with the new image
    const newImageUrl = getS3ImageUrl(newImage);
    $('#current-image').attr('src', newImageUrl);
    $('#image-preview-container').removeClass('d-none');
    
    // Remove the parameter from URL to prevent reloading on refresh
    const newUrl = window.location.pathname + '?business_id=' + <?= $business_id ?>;
    window.history.replaceState({}, '', newUrl);
  }
  
  // Helper function to get S3 URL (same as PHP version)
  function getS3ImageUrl(filename) {
    if (!filename) return '';
    return "https://external-dev-testing.s3.us-east-1.amazonaws.com/header_images/" + filename;
  }


  // If a file is chosen, preview immediately
  $('#header_image').on('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    currentFile = file;

    const reader = new FileReader();
    reader.onload = function(ev) {
      $('#current-image').attr('src', ev.target.result);
      $('#image-preview-container').removeClass('d-none');
    };
    reader.readAsDataURL(file);
  });

  // Remove image handler
  $('#remove-image-btn').on('click', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to remove this image?')) return;
    
    $('#current-image').attr('src', '');
    $('#image-preview-container').addClass('d-none');
    $('#header_image').val('');
    $('#header_image_filename').val('');
    currentFile = null;
  });

  // Toggle time fields if “Closed” is checked
  function toggleTimeFields($row, isClosed) {
    if (isClosed) {
      // Hide the open/close inputs
      $row.find('.time-field').hide();
    } else {
      // Show them
      $row.find('.time-field').show();
    }
  }

  // Initial setup for each day row & event binding
  $('.business-hour-row').each(function() {
    const $row = $(this);
    const isClosed = $row.find('.is-closed').is(':checked');
    toggleTimeFields($row, isClosed);

    // When user toggles the checkbox
    $row.find('.is-closed').on('change', function() {
      toggleTimeFields($row, $(this).is(':checked'));
    });
  });

  // Validate hours (only if not closed)
  function validateHours() {
    let isValid = true;
    $('.business-hour-row').each(function() {
      const $row = $(this);
      const day = $row.data('day');
      const isClosed = $row.find('.is-closed').is(':checked');
      if (!isClosed) {
        const openVal  = $row.find('.open-time').val();
        const closeVal = $row.find('.close-time').val();
        if (openVal && closeVal && openVal >= closeVal) {
          alert(`On ${day}, the open time cannot be later than or equal to the close time.`);
          isValid = false;
          return false; // break the .each loop
        }
      }
    });
    return isValid;
  }

  // AJAX upload to S3
  function uploadImage(file) {
    const formData = new FormData();
    formData.append('header_image', file);
    formData.append('business_id', $('[name="business_id"]').val());

    return $.ajax({
      url: 'business_edit.php?action=upload_image',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false
    });
  }

  // Main form submit
  $('#businessForm').on('submit', function(e) {
    // 1) Validate hours
    if (!validateHours()) {
      e.preventDefault();
      return;
    }
    
    if (currentFile) {
      e.preventDefault(); // prevent immediate submit
      uploadImage(currentFile)
        .done(function(response) {
          if (response.success) {
            $('#header_image_filename').val(response.filename);
            submitFormData();
          } else {
            alert('Error uploading image: ' + response.message);
          }
        })
        .fail(function(xhr, status, error) {
          alert('An error occurred during upload.');
          console.error(error);
        });
    }
  });

  // Final POST after image upload
  function submitFormData() {
    const formData = new FormData($('#businessForm')[0]);
    // Remove raw file input
    formData.delete('header_image');

    $.ajax({
      url: 'business_edit.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(resp) {
        let data;
        try {
          data = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        } catch (e) {
          // If not JSON, assume normal redirect
          window.location.href = 'admin_dashboard.php';
          return;
        }
        if (data.success) {
          window.location.href = 'admin_dashboard.php';
        } else {
          alert('Error saving data: ' + (data.message || 'Unknown error'));
        }
      },
      error: function(xhr, status, error) {
        alert('Error saving form data: ' + error);
      }
    });
  }
});
</script>
</body>
</html>
