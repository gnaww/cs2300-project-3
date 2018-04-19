<?php
include('includes/init.php');
$current_page = "index";

// Delete image button was pressed by uploader, $_POST["delete_image"] contains deleted image ID
if (isset($_POST["delete_image"])) {
  // Make sure image ID submitted through delete image button is valid
  $valid_image_id = TRUE;
  $image_id = filter_input(INPUT_POST, 'delete_image', FILTER_VALIDATE_INT);
  if (is_int($image_id)) {
    $params = array(':id' => $image_id);
    $records = exec_sql_query($db, "SELECT * FROM images WHERE id=:id", $params)->fetchAll(PDO::FETCH_ASSOC);
    if (!$records) {
      array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete image because image ID doesn't exist.</li>");
      $valid_image_id = FALSE;
    }
  }
  else {
    array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete image because image ID is invalid.</li>");
    $valid_image_id = FALSE;
  }

  if ($valid_image_id) {
    $file_ext = $records[0]['file_extension'];
    $file_path = IMAGE_UPLOADS_PATH . $image_id . "." . $file_ext;

    // Delete image from images database
    $sql = "DELETE FROM images WHERE id = :img_id";
    $params = array(':img_id' => $image_id);
    $result_images = exec_sql_query($db, $sql, $params);

    // Delete tags associated with deleted image in gallery database
    $sql = "DELETE FROM gallery WHERE image_id = :img_id";
    $result_gallery = exec_sql_query($db, $sql, $params);

    if ($result_images && $result_gallery) {
      // Delete actual file from server
      if(unlink($file_path)) {
        array_push($gallery_notifications, "<li class='success-gallery'>Successfully deleted image!</li>");
      }
      else {
        array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete image.</li>");
      }
    }
    else {
      array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete image.</li>");
    }
  }
}

// If user tried to upload a new image to gallery, process user input and upload image if valid
if (isset($_POST["submit_upload"])) {
  $upload_info = $_FILES["image_file"];
  $desc = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
  $source = trim(filter_input(INPUT_POST, 'image_source', FILTER_SANITIZE_STRING));
  $file_name = filter_var(pathinfo($upload_info['name'])['filename'], FILTER_SANITIZE_STRING);
  $file_ext = strtolower(filter_var(pathinfo($upload_info['name'])['extension'], FILTER_SANITIZE_STRING));
  $file_size = $upload_info['size'];
  $existing_tag = filter_input(INPUT_POST, 'existing-tag', FILTER_VALIDATE_INT);
  $new_tag = strtolower(trim(filter_input(INPUT_POST, 'new-tag', FILTER_SANITIZE_STRING)));

  if (strlen($desc) == 0) {
    $desc = NULL;
  }
  if (strlen($source) == 0) {
    $source = "Original Content";
  }

  $valid_tag = validate_tags($existing_tag, $new_tag, NULL);

  if ($upload_info['error'] == 0 && $valid_tag) {
    // Check if file has valid image file format
    if (in_array($file_ext, $image_formats)) {
      // Check if file is too large
      if ($file_size <= 2000000) {
        $sql = "INSERT INTO images (file_name, file_extension, description, upload_user, source) VALUES (:fname, :ext, :desc, :user, :src)";
        $params = array(':ext' => $file_ext,
                        ':fname' => $file_name,
                        ':desc' => $desc,
                        ':user' => $current_user,
                        ':src' => $source);
        $result = exec_sql_query($db, $sql, $params);
        // Check if images database was successfully updated
        if ($result) {
          $image_id = $db->lastInsertId("id");
          $file_path = IMAGE_UPLOADS_PATH . $image_id . "." . $file_ext;
          // Check if file was successfully uploaded to server
          if (move_uploaded_file($upload_info['tmp_name'], $file_path)){
            array_push($gallery_notifications, "<li class='success-gallery'>Successfully uploaded image!</li>");
            add_tags($existing_tag, $new_tag, $image_id, FALSE);
          }
          else {
            array_push($gallery_notifications, "<li class='error-gallery'>Image failed to upload.</li>");
          }
        }
        else {
          array_push($gallery_notifications, "<li class='error-gallery'>Image failed to upload.</li>");
        }
      }
      else {
        array_push($gallery_notifications, "<li class='error-gallery'>Image file size too large!</li>");
      }
    }
    else {
      array_push($gallery_notifications, "<li class='error-gallery'>Unsupported file format.</li>");
    }
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
  <title>Photo Gallery</title>
</head>

<body>
  <div id="wrapper">
    <?php include("includes/header.php") ?>
    <div id="content">
      <?php
      print_gallery_notifications();

      if (!is_null($current_user)) { ?>
        <div id="upload-label">
          Upload New Image
        </div>
        <div id="upload">
          <form id="upload-form" action="index.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
            Upload File: <input type="file" name="image_file" id="upload-file" required>
            Image Source: <input type="text" name="image_source" class="source-input" placeholder="Give the original creator credit. Default: Original Content.">
            <br> <br>
            Add Tag: <select name="existing-tag" class="tag-dropdown">
                     <option value="" selected disabled>Select a Tag</option>
                      <?php
                        // Populate dropdown with already existing tags in tags database
                        $records = exec_sql_query($db, "SELECT * FROM tags ORDER BY tag_name ASC", array())->fetchAll(PDO::FETCH_ASSOC);
                        foreach($records as $record) {
                          echo "<option value='" . $record['id'] . "'>" . $record['tag_name'] . "</option>";
                        }
                      ?>
                     </select>
            Add New Tag: <input type="text" name="new-tag" maxlength="30" class="tag-input">
            <br> <br>
            Description: <textarea name="description" cols="100" rows="3" class="description-input" placeholder="Write a short description of the image."></textarea>
            <button name="submit_upload" type="submit" id="upload-button">Upload</button>
          </form>
        </div>
      <?php
      }
      ?>
        <div id="gallery">
          <?php
            // Show all images in gallery
            $sql = "SELECT * FROM images";
            $records = exec_sql_query($db, $sql, array())->fetchAll(PDO::FETCH_ASSOC);
            if ($records) {
              print_images($records);
            }
            else {
              echo "<h1>No images in gallery.</h1>";
            }
          ?>
        </div>
    </div>
  </div>
</body>
</html>
