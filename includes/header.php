<header>
  <span id="title">Photo Gallery</span>
  <nav id="navbar">
      <?php
        echo "<ul>";
        foreach($pages as $page => $page_name) {
          if ($page == $current_page) {
            echo("<li><a href='" . $page . ".php' class='current-page'>" . $page_name .
                 "</a></li>");
          }
          else {
            echo("<li><a href='" . $page . ".php'>" . $page_name . "</a></li>");
          }
        }
        echo "</ul>";
        foreach ($login_notifications as $notification) {
          echo $notification;
        }
        if ($current_user != NULL) {
          echo "<span class='welcome-message'>Hello, " . $current_user . "!</span>";
          echo "<form id='logout-form' action='" . htmlspecialchars($_SERVER["REQUEST_URI"]) . "' method='post'>
                  <button name='logout' id='logout-button' type='submit'>Log Out</button>
                </form>";
        }
        else {
          echo "<form id='login-form' action='" . htmlspecialchars($_SERVER["REQUEST_URI"]) . "' method='post'>
                  <input type='text' class='login-input' name='username' placeholder='Username' required/>
                  <input type='password' class='login-input' name='password' placeholder='Password' required/>
                  <button name='login' id='login-button' type='submit'>Log In</button>
                </form>";
        }
      ?>
  </nav>
</header>
