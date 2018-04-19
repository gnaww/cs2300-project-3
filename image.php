<?php
include('includes/init.php');
$current_page = "image";

// Check if the image ID is valid, if valid show image detail if not print error message
if (isset($_GET["image_id"]) && !empty($_GET["image_id"])) {
  $valid_image_id = TRUE;
  $image_id = filter_input(INPUT_GET, 'image_id', FILTER_VALIDATE_INT);

  if (is_int($image_id)) {
    $params = array(':id' => $image_id);
    $records = exec_sql_query($db, "SELECT * FROM images WHERE id=:id", $params)->fetchAll(PDO::FETCH_ASSOC);
    if (!$records) {
      echo "<h1>That image doesn't exist!</h1>";
      $valid_image_id = FALSE;
    }
  }
  else {
    echo "<h1>Invalid image ID.</h1>";
    $valid_image_id = FALSE;
  }
}
else {
  echo "<h1>No image specified.</h1>";
  $valid_image_id = FALSE;
}

// Edit image description and source if user saves changes
if (isset($_POST["save_changes"]) && $valid_image_id) {
  $desc = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
  $source = trim(filter_input(INPUT_POST, 'image_source', FILTER_SANITIZE_STRING));

  if (strlen($desc) == 0) {
    $desc = NULL;
  }
  if (strlen($source) == 0) {
    $source = "Original Content";
  }

  $sql = "UPDATE images SET description = :desc, source = :src WHERE id = :img_id";
  $params = array(':desc' => $desc,
                  ':src' => $source,
                  ':img_id' => $image_id);

  $result = exec_sql_query($db, $sql, $params);
  if ($result) {
    array_push($gallery_notifications, "<li class='success-gallery'>Successfully saved changes!</li>");
  }
  else {
    array_push($gallery_notifications, "<li class='error-gallery'>Failed to save changes.</li>");
  }
}

// Delete tag from image
if (isset($_POST["delete_tag"]) && $valid_image_id) {
  $valid_tag = TRUE;
  $tag_id = filter_input(INPUT_POST, 'delete-existing-tag', FILTER_VALIDATE_INT);

  if ($tag_id) {
    $sql = "SELECT * FROM gallery WHERE image_id = :img_id AND tag_id = :t_id";
    $params = array(':img_id' => $image_id, ':t_id' => $tag_id);
    $records = exec_sql_query($db, $sql, $params)->fetchAll(PDO::FETCH_ASSOC);

    // Check if deleted tag ID is a tag that is already attached to image
    if ($records) {
      $sql = "DELETE FROM gallery WHERE image_id = :img_id AND tag_id = :t_id";
      $result = exec_sql_query($db, $sql, $params);
      if ($result) {
        array_push($gallery_notifications, "<li class='success-gallery'>Successfully deleted tag!</li>");
      }
      else {
        array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete tag.</li>");
      }
    }
    else {
      $valid_tag = FALSE;
      array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete tag, tag is not attached to the image!</li>");
    }
  }
  else {
    $valid_tag = FALSE;
    array_push($gallery_notifications, "<li class='error-gallery'>Failed to delete tag, invalid tag ID!</li>");
  }
}

if (isset($_POST["add_tag"]) && $valid_image_id) {
  $existing_tag = filter_input(INPUT_POST, 'add-existing-tag', FILTER_VALIDATE_INT);
  $new_tag = strtolower(trim(filter_input(INPUT_POST, 'add-new-tag', FILTER_SANITIZE_STRING)));

  $valid_tag = validate_tags($existing_tag, $new_tag, $image_id);

  if ($valid_tag) {
    add_tags($existing_tag, $new_tag, $image_id, TRUE);
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
  <title>Photo Detail</title>
</head>

<body>
  <div id="wrapper">
    <?php include("includes/header.php") ?>
    <div id="content">
      <?php
      print_gallery_notifications();

      // If the GET input image_id is valid, display the image information
      if ($valid_image_id) { ?>
        <div class="grid-container">
          <div class="picture">
          <?php
            $params = array(':id' => $image_id);
            $records = exec_sql_query($db, "SELECT * FROM images WHERE id=:id", $params)->fetchAll(PDO::FETCH_ASSOC);
            // Display full image
            if ($records) {
              $image = $records[0];
              $image_file_name = $image['id'];
              $image_file_extension = $image['file_extension'];
              $image_alt = $image['file_name'];
              echo "<img src='/uploads/images/$image_file_name.$image_file_extension' alt='$image_alt'>";
            }
          ?>
          </div>
          <div class="picture-info">
            <div class="picture-info-content">
              <?php
                // Get tags attached to image
                $sql = "SELECT tags.*
                        FROM tags
                        JOIN gallery
                        ON tags.id = gallery.tag_id
                        JOIN images
                        ON gallery.image_id = images.id
                        WHERE gallery.image_id = :id
                        ORDER BY tags.tag_name ASC";
                $params = array(':id' => $image_id);
                $attached_tags = exec_sql_query($db, $sql, $params)->fetchAll(PDO::FETCH_ASSOC);
                echo "<p class='picture-info-header'>Tags:</p>";
                if ($attached_tags) {
                  print_tags($attached_tags);
                }
                else {
                  echo "No tags attached to image.";
                }
                echo "<p class='picture-info-header'>Description:</p>";
                $records = exec_sql_query($db, "SELECT * FROM images WHERE id=:id", $params)->fetchAll(PDO::FETCH_ASSOC);
                $image = $records[0];
                if (!is_null($image['description'])) {
                  echo $image['description'];
                }
                else {
                  echo "No description.";
                }
                echo "<p class='picture-info-header'>Image Source:</p>";
                echo $image['source'];
                echo "<p class='picture-info-header'>Uploaded By: " . $image['upload_user'] . "</p>";
              ?>
            </div>
          </div>
        </div>
        <?php
        // Only display form to edit image information if the logged in user is the image uploader
        if ($current_user && strcmp($current_user, $image['upload_user']) == 0) {
        ?>
          <div class="edit-label">
            Edit Image Information
          </div>
          <div class="edit">
            <form id="edit-form" action="image.php?image_id=<?php echo $image_id; ?>" method="post">
               Description: <textarea name="description" cols="100" rows="3" class="description-input" placeholder="Write a short description of the image."><?php echo $image['description']; ?></textarea>
               <br> <br>
               Image Source: <input type="text" name="image_source" class="source-input-edit" value="<?php echo $image['source']; ?>" placeholder="Give the original creator credit. Default: Original Content.">
               <button name="save_changes" type="submit" class="save-button">Save Changes</button>
            </form>
            <form id="delete-image-form" action="index.php" method="post">
              <button name="delete_image" value="<?php echo $image_id; ?>" type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this image?')">Delete Image</button>
            </form>
          </div>
          <div class="edit-label">
            Delete Tag
          </div>
          <div class="edit">
            <form id="delete-tag-form" action="image.php?image_id=<?php echo $image_id; ?>" method="post">
              Delete Existing Tag: <select name="delete-existing-tag" class="tag-dropdown" required>
                       <option value="" selected disabled>Select a Tag</option>
                        <?php
                          // Populate dropdown with the tags that are attached to image
                          $sql =  "SELECT tags.*
                                   FROM tags
                                   JOIN gallery
                                   ON tags.id = gallery.tag_id
                                   JOIN images
                                   ON gallery.image_id = images.id
                                   WHERE gallery.image_id = :id
                                   ORDER BY tags.tag_name ASC";
                          $records = exec_sql_query($db, $sql, array(':id' => $image_id))->fetchAll(PDO::FETCH_ASSOC);
                          foreach($records as $record) {
                            echo "<option value='" . $record['id'] . "'>" . $record['tag_name'] . "</option>";
                          }
                        ?>
                       </select>
               <br>
               <button name="delete_tag" type="submit" class="delete-button">Delete Tag</button>
            </form>
          </div>
       <?php
        }
       ?>
       <div class="edit-label">
         Add Tags
       </div>
       <div class="edit">
         <form id="add-tag-form" action="image.php?image_id=<?php echo $image_id; ?>" method="post">
           Add Tag: <select name="add-existing-tag" class="tag-dropdown">
                    <option value="" selected disabled>Select a Tag</option>
                     <?php
                       // Populate dropdown with already existing tags that aren't already attached
                       $records = exec_sql_query($db, "SELECT * FROM tags ORDER BY tag_name ASC", array())->fetchAll(PDO::FETCH_ASSOC);
                       foreach($records as $record) {
                         if (!in_array($record, $attached_tags)) {
                           echo "<option value='" . $record['id'] . "'>" . $record['tag_name'] . "</option>";
                         }
                       }
                     ?>
                    </select>
           Add New Tag: <input type="text" name="add-new-tag" maxlength="30" class="tag-input">
           <button name="add_tag" type="submit" class="save-button">Add Tags</button>
         </form>
       </div>
      <?php
      }
      ?>
    </div>
  </div>
</body>
</html>
