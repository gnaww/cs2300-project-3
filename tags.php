<?php
include('includes/init.php');
$current_page = "tags";

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
  <title>Tags</title>
</head>

<body>
  <div id="wrapper">
    <?php include("includes/header.php") ?>
    <div id="content">
      <div class="tags-list">
        <p class="tags-list-header">Tags</p>
        <?php
        // Get list of all tags
        $records = exec_sql_query($db, "SELECT * FROM tags ORDER BY tag_name ASC", array())->fetchAll(PDO::FETCH_ASSOC);
        if ($records) {
          print_tags($records);
        }
        else {
          echo "No tags available.";
        }
        ?>
      </div>
      <?php
      // Check if the chosen tag is valid, if valid show matching images if not print error message
      if (isset($_GET["tag"]) && !empty($_GET["tag"])) {
        $valid_image_tag = TRUE;
        $tag = filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_STRING);

        if (ctype_digit($tag)) { // If user used tag ID
          $records = exec_sql_query($db, "SELECT * FROM tags WHERE id=:id", array(':id' => $tag))->fetchAll(PDO::FETCH_ASSOC);
          if (!$records) {
            echo "<h1>That tag ID doesn't exist!</h1>";
            $valid_image_tag = FALSE;
          }
          else {
            $tag = (int)$tag;
            $tag_name = $records[0]['tag_name'];
          }
        }
        else { // If user used tag name
          $records = exec_sql_query($db, "SELECT * FROM tags WHERE tag_name=:t_name", array(':t_name' => $tag))->fetchAll(PDO::FETCH_ASSOC);
          if (!$records) {
            echo "<h1>The tag \"$tag\" doesn't exist yet!</h1>";
            $valid_image_tag = FALSE;
          }
          else {
            $tag = $records[0]['id']; // Convert tag name to corresponding tag ID
            $tag_name = $records[0]['tag_name'];
          }
        }
      }
      else {
        echo "<h1>No tag chosen.</h1>";
        $valid_image_tag = FALSE;
      }

      if ($valid_image_tag) {
        // Show images with matching tag
        $sql = "SELECT images.*
                FROM images
                JOIN gallery
                ON images.id = gallery.image_id
                JOIN tags
                ON gallery.tag_id = tags.id
                WHERE gallery.tag_id = :tag";

        $records = exec_sql_query($db, $sql, array(':tag' => $tag))->fetchAll(PDO::FETCH_ASSOC);
        if ($records) {
          echo "<p class='tags-images-header'>Showing images tagged \"$tag_name\":</p>";
          echo "<div id='gallery'>";
          print_images($records);
        }
        else {
          echo "<h1>No images tagged \"$tag_name\".</h1>";
        }
        echo "</div>";
      }
      ?>
    </div>
  </div>
</body>
</html>
