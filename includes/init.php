<?php
$pages = array("index" => "Home",
               "tags" => "Tags");

$login_notifications = array();
$gallery_notifications = array();

$image_formats = array("jpg", "jpeg", "jpe", "jif", "jfif", "jfi", "gif", "png", "apng", "svg", "bmp", "ico");

const IMAGE_UPLOADS_PATH = "uploads/images/";

// Print images with link to image.php page
function print_images($images) {
  foreach($images as $image) {
    echo "<div class='gallery-image'>
            <a href='image.php?image_id=". $image['id'] . "'>
              <img src='/uploads/images/" . $image['id'] . "." . $image['file_extension'] . "' alt='" . $image['file_name'] . "'>
            </a>
          </div>";
  }
}

// Print tags with comma separator and link to tags.php page
function print_tags($tags) {
  foreach ($tags as $tag) {
    if (next($tags)) {
      echo "<a href='tags.php?tag=" . $tag['id'] . "'>";
      echo $tag['tag_name'] . "</a>, ";
    }
    else {
      echo "<a href='tags.php?tag=" . $tag['id'] . "'>";
      echo $tag['tag_name'];
      echo "</a>";
    }
  }
}

// Add tags to image ID given
function add_tags($existing_tag, $new_tag, $image_id, $notifications_on) {
  global $gallery_notifications;
  global $db;

  // Tag image with existing tag if it was inputted
  if (!empty($existing_tag)) {
    $sql = "INSERT INTO gallery (image_id, tag_id) VALUES (:img_id, :tag_id)";
    $params = array(':img_id' => $image_id,
                    ':tag_id' => $existing_tag);
    $result = exec_sql_query($db, $sql, $params);
    if ($result && $notifications_on) {
      array_push($gallery_notifications, "<li class='success-gallery'>Successfully added existing tag!</li>");
    }
    elseif (!$result) {
      array_push($gallery_notifications, "<li class='error-gallery'>Failed to add existing tag.</li>");
    }
  }
  // Tag image with new tag if it was inputted
  if (!empty($new_tag)) {
    $result = exec_sql_query($db, "INSERT INTO tags (tag_name) VALUES (:tag_name)", array(':tag_name' => $new_tag));
    $new_tag_id = $db->lastInsertId("id");

    $sql = "INSERT INTO gallery (image_id, tag_id) VALUES (:img_id, :tag_id)";
    $params = array(':img_id' => $image_id,
                    ':tag_id' => $new_tag_id);
    $result = exec_sql_query($db, $sql, $params);
    if ($result && $notifications_on) {
      array_push($gallery_notifications, "<li class='success-gallery'>Successfully added new tag!</li>");
    }
    elseif (!$result) {
      array_push($gallery_notifications, "<li class='error-gallery'>Failed to add new tag.</li>");
    }
  }
}

// Make sure tags are valid to be attached to image
function validate_tags($existing_tag, $new_tag, $image_id) {
  global $gallery_notifications;
  global $db;

  $valid_tag = TRUE;

  // Check if user tried to edit dropdown value to a noninteger ID
  if (!is_int($existing_tag) && !is_null($existing_tag)) {
    $valid_tag = FALSE;
    array_push($gallery_notifications, "<li class='error-gallery'>Add Tag: Invalid tag inputted.</li>");
  }
  elseif ($existing_tag != NULL) {
    // Check if inputted existing tag ID from dropdown is in tags database or not
    $records = exec_sql_query($db, "SELECT * FROM tags WHERE id=:tag_id", array(':tag_id' => $existing_tag))->fetchAll(PDO::FETCH_ASSOC);
    if (!$records || !is_int($existing_tag)) {
      array_push($gallery_notifications, "<li class='error-gallery'>Add Tag: The inputted tag doesn't exist yet!</li>");
      $valid_tag = FALSE;
    }
    elseif (!is_null($image_id)) { // Tag exists, so if adding tag to an existing image check if tag is already attached
      $sql = "SELECT * FROM gallery WHERE image_id = :img_id AND tag_id = :t_id";
      $params = array(':img_id' => $image_id, ':t_id' => $existing_tag);
      $records = exec_sql_query($db, $sql, $params)->fetchAll(PDO::FETCH_ASSOC);

      if ($records) {
        array_push($gallery_notifications, "<li class='error-gallery'>Added tag is already attached to image!</li>");
        $valid_tag = FALSE;
      }
    }
  }

  // Check if inputted new tag from text input is already in tags database or not
  if ($new_tag) {
    $records = exec_sql_query($db, "SELECT * FROM tags WHERE tag_name=:t_name", array(':t_name' => $new_tag))->fetchAll(PDO::FETCH_ASSOC);
    if ($records) {
      array_push($gallery_notifications, "<li class='error-gallery'>Add New Tag: The tag \"" . $new_tag . "\" already exists!</li>");
      $valid_tag = FALSE;
    }
  }

  return $valid_tag;
}

// Print success/error notifications to user related to photo gallery
function print_gallery_notifications() {
  global $gallery_notifications;

  echo "<ul>";
  foreach ($gallery_notifications as $notification) {
    echo $notification;
  }
  echo "</ul>";
}

// show database errors during development.
function handle_db_error($exception) {
  echo '<p><strong>' . htmlspecialchars('Exception : ' . $exception->getMessage()) . '</strong></p>';
}

// execute an SQL query and return the results.
function exec_sql_query($db, $sql, $params = array()) {
  try {
    $query = $db->prepare($sql);
    if ($query and $query->execute($params)) {
      return $query;
    }
  } catch (PDOException $exception) {
    handle_db_error($exception);
  }
  return NULL;
}

// open connection to database
function open_or_init_sqlite_db($db_filename, $init_sql_filename) {
  if (!file_exists($db_filename)) {
    $db = new PDO('sqlite:' . $db_filename);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_init_sql = file_get_contents($init_sql_filename);
    if ($db_init_sql) {
      try {
        $result = $db->exec($db_init_sql);
        if ($result) {
          return $db;
        }
      } catch (PDOException $exception) {
        // If we had an error, then the DB did not initialize properly,
        // so let's delete it!
        unlink($db_filename);
        throw $exception;
      }
    }
  } else {
    $db = new PDO('sqlite:' . $db_filename);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
  }
  return NULL;
}

// Create database
$db = open_or_init_sqlite_db('data.sqlite', "init/init.sql");

// Get user that is currently logged into the website
function current_user() {
  global $db;

  if (isset($_COOKIE['session'])) {
    $sess_id = $_COOKIE['session'];

    $records = exec_sql_query($db, "SELECT * FROM users WHERE session_id = :sess_id",
                              array(':sess_id' => $sess_id))->fetchAll(PDO::FETCH_ASSOC);

    // Should only find one user
    if ($records) {
      $user = $records[0];
      return $user['username'];
    }
  }
  else {
    // No user is logged in
    return NULL;
  }
}

// Log in user
function login($uname, $pw) {
  global $db;
  global $login_notifications;

  if (!empty($uname) && !empty($pw)) {
    $records = exec_sql_query($db, "SELECT * FROM users WHERE username = :uname",
                              array(':uname' => $uname))->fetchAll(PDO::FETCH_ASSOC);
    // Should only find one user
    if (count($records) == 1) {
        $user = $records[0];

        // Check matching passwords
        if (password_verify($pw, $user['password'])) {
          // Give logging in user a unique session ID
          $sess_id = uniqid();
          $sql = "UPDATE users SET session_id = :sess_id WHERE username = :uname";
          $params = array(':sess_id' => $sess_id,
                          ':uname' => $user['username']);
          $result = exec_sql_query($db, $sql, $params);

          // Successfully updated user's session ID
          if ($result) {
            setcookie("session", $sess_id, time()+86400);
            array_push($login_notifications, "<span class='success-login'>Log in successful!</span>");
            return $uname;
          }
          else {
            array_push($login_notifications, "<span class='error-login'>Failed to log in.</span>");
          }
        }
        else {
          array_push($login_notifications, "<span class='error-login'>Incorrect username or password.</span>");
        }
    }
    else {
      array_push($login_notifications, "<span class='error-login'>Incorrect username or password.</span>");
    }
  }
  else {
    array_push($login_notifications, "<span class='error-login'>Username and password required to log in.</span>");
  }
}

// Log out user
function logout() {
  global $db;
  global $current_user;
  global $login_notifications;

  // There is a currently logged in user
  if ($current_user != NULL) {
    $sql = "UPDATE users SET session_id = :sess_id WHERE username = :uname;";
    $params = array (':uname' => $current_user,
                     ':sess_id' => NULL);

    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      setcookie("session", "", time()-86400);
      $current_user = NULL;
      array_push($login_notifications, "<span class='success-login'>Successfully logged out!</span>");
    }
    else {
      array_push($login_notifications, "<span class='error-login'>Failed to log out.</span>");
    }
  }
}

// Check if user tried to log in
if (isset($_POST["login"])) {
  $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
  $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
  $current_user = login($username, $password);
}
else {
  // Check if there is an already logged in user
  $current_user = current_user();
}

if (isset($_POST["logout"])) {
  logout();
  if ($current_user = NULL) {
    array_push($login_notifications, "<span class='success-login'>Successfully logged out!</span>");
  }
}
