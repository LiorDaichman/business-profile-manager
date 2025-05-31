<?php
require_once 's3_helper.php';
require 'vendor/autoload.php';

// Get the current image filename and business ID
$business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
$image_filename = isset($_GET['filename']) ? $_GET['filename'] : '';

// Handle image save after editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_edited') {
    header('Content-Type: application/json');
    
    // Decode the base64 image data
    $imageData = $_POST['image_data'] ?? '';
    if (empty($imageData)) {
        echo json_encode(['success' => false, 'message' => 'No image data received']);
        exit;
    }
    
    // Remove the data URL prefix and decode
    list($type, $data) = explode(';', $imageData);
    list(, $data) = explode(',', $data);
    $imageData = base64_decode($data);
    
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'edited_');
    file_put_contents($tempFile, $imageData);
    
    // Get file extension from content type
    $contentType = explode(':', $type)[1];
    $extension = '';
    switch ($contentType) {
        case 'image/jpeg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        default:
            $extension = 'jpg';
    }
    
    // Create file array similar to $_FILES structure
    $file = [
        'name' => 'edited_image.' . $extension,
        'type' => $contentType,
        'tmp_name' => $tempFile,
        'error' => 0,
        'size' => filesize($tempFile)
    ];
    
    // Upload to S3
    $filename = uploadFileToS3($file, $business_id);
    
    // Clean up the temp file
    @unlink($tempFile);
    
    if ($filename) {
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'url' => getS3ImageUrl($filename)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload edited image'
        ]);
    }
    exit;
}

// Get the image URL for the editor
$imageUrl = !empty($image_filename) ? getS3ImageUrl($image_filename) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Image - Business Profile</title>
    
    <!-- Bootstrap CSS -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >
    
    <!-- Tui Image Editor CSS -->
    <link type="text/css" href="https://uicdn.toast.com/tui-color-picker/v2.2.7/tui-color-picker.min.css" rel="stylesheet">
    <link type="text/css" href="https://uicdn.toast.com/tui-image-editor/v3.15.3/tui-image-editor.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            flex-grow: 1;
        }
        .card {
            border-radius: 12px;
            background-color: #fff;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        #editor-container {
            width: 100%;
            height: 600px;
            position: relative;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            padding: 15px 25px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .tui-image-editor-header-logo {
            display: none;
        }
        .page-title {
            margin: 20px 0;
            color: #4a5568;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Edit Header Image</h1>
        
        <div class="card">
            <div id="editor-container">
                <!-- The editor will be mounted here -->
            </div>
            
            <div class="action-buttons">
                <button id="cancel-btn" class="btn btn-secondary">Cancel</button>
                <button id="save-btn" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    
    <!-- TOAST UI ImageEditor Dependencies -->
    <script type="text/javascript" src="https://uicdn.toast.com/tui.code-snippet/v1.5.0/tui-code-snippet.min.js"></script>
    <script type="text/javascript" src="https://uicdn.toast.com/tui-color-picker/v2.2.7/tui-color-picker.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/4.4.0/fabric.min.js"></script>
    <script type="text/javascript" src="https://uicdn.toast.com/tui-image-editor/v3.15.3/tui-image-editor.min.js"></script>
    
    <script>
    $(function() {
        // Variables to store data
        const businessId = <?= $business_id ?>;
        const imageUrl = '<?= $imageUrl ?>';
        let imageEditor;
        
        // Initialize the TOAST UI Image Editor
        try {
            imageEditor = new tui.ImageEditor('#editor-container', {
                includeUI: {
                    loadImage: {
                        path: imageUrl,
                        name: 'HeaderImage'
                    },
                    theme: {
                        'common.bi.image': '',  // Remove the watermark
                        'common.bisize.width': '0',
                        'common.bisize.height': '0'
                    },
                    menu: ['crop', 'flip', 'rotate', 'draw', 'shape', 'icon', 'text', 'filter'],
                    initMenu: 'crop',
                    menuBarPosition: 'bottom'
                },
                cssMaxWidth: 1100,
                cssMaxHeight: 600,
                usageStatistics: false
            });
            
            // If the image fails to load, show an error
            imageEditor.on('loadImageFailure', function() {
                $('#editor-container').html('<div class="alert alert-danger m-5">Failed to load image. Please try again or contact support.</div>');
            });
            
        } catch (error) {
            console.error('Error initializing TOAST UI Image Editor:', error);
            $('#editor-container').html(
                '<div class="alert alert-danger m-5">Could not initialize image editor: ' + error.message + '</div>'
            );
        }
        
        // Cancel button handler
        $('#cancel-btn').on('click', function() {
            // Go back to the business edit page
            window.location.href = 'business_edit.php?business_id=' + businessId;
        });
        
        // Save button handler
        $('#save-btn').on('click', function() {
            if (!imageEditor) {
                alert('Editor not available. Please try again.');
                return;
            }
            
            // Show loading state
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');
            
            try {
                // Get the edited image as a data URL
                const dataUrl = imageEditor.toDataURL({
                    format: 'jpeg',
                    quality: 0.9
                });
                
                // Send to server
                $.ajax({
                    url: 'image_editor.php',
                    type: 'POST',
                    data: {
                        action: 'save_edited',
                        business_id: businessId,
                        image_data: dataUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect back with new filename
                            window.location.href = 'business_edit.php?business_id=' + businessId + '&new_image=' + response.filename;
                        } else {
                            alert('Error saving image: ' + (response.message || 'Unknown error'));
                            $btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        alert('Error: ' + error);
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            } catch (error) {
                console.error('Error during save:', error);
                alert('Error during save: ' + error.message);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    </script>
</body>
</html>