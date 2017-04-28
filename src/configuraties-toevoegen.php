<?php session_start(); ?>
<?php
$request_uri = substr($_SERVER["REQUEST_URI"], 1);
// load menuItems.json
$menuItemsFile = fopen("menuItems.json", "r") or die("unable to open menuItems.json");
$menuItems = json_decode(fread($menuItemsFile, filesize("menuItems.json")), true);
fclose($menuItemsFile);

// security if user doesn't have permission to visit this page the page doesn't load
foreach ($menuItems as $key => $value) {
  foreach ($value as $key => $value) {
    if ($key != "menuItem") {
      foreach ($value["menuItems"] as $key => $value) {
        if ($value["path"] == $request_uri) {
          $found = false;
          foreach ($value["type"] as $key => $value) {
            if ($value == $_SESSION["user"]["toegangs_level"]) {
              $found = true;
            }
          }
          if (!$found) {
            die("U heeft geen toestemming om hier te komen");
          }
        }
      }
    }
  }
}
// load functions.php
include "functions.php";
// the webpage url(needed for logout)
$thisPage = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/$request_uri";
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MA Twente</title>
    <link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Rubik" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
  </head>
  <body>
    <header>
      <div class="profileBar">
        <?php
        if (empty($_SESSION["user"]["naam"]) || !$_SESSION["user"]["naam"]) {
          echo "<div class='profile'>";
          echo "<a href='index.php'>login</a></div>";
        } else {
          echo "<div class='profile'><a href=''#'>";
          echo "<img src=\"images/profiles/".$_SESSION["user"]["profile_path"]."\" alt=\"profile\" class=\"profilePicture\">";
          echo "</a><ul><li>";
          echo "<a href='$thisPage?logout=true'>logout</a>";
          echo "</li><li><a href='user-settings.php'>settings</a></li></ul></div>";
          echo "<div class='status'><span>".$_SESSION["user"]["naam"]."</span><br>
          <a href='$thisPage?logout=true'>logout</a></div>";
        }
        ?>
        <?php if (isset($_GET["logout"])) {
          logout();
        } ?>

      </div>
      <img src="images/icon.svg" alt="logo" class="logo logoGone" style="display: none">
      <nav class="navClosed">
        <ul>
          <?php
          if (!empty($_SESSION["user"]["toegangs_level"])) {
            foreach ($menuItems as $key => $value) {
              if ($value["menuItem"]) {
                echo "<li>";
                foreach ($value as $key => $value) {
                  if ($key != "menuItem") {
                    echo "<a href='#'>$key</a><ul>";
                    foreach ($value as $key => $value) {
                      foreach ($value as $key => $value) {
                        $found = false;
                        foreach ($value["type"] as $key => $valueType) {
                          if ($valueType == $_SESSION["user"]["toegangs_level"]) {
                            $found = true;
                          }
                        }
                        if ($found == true) {
                          echo "<li><a href='".$value["path"]."'>".$value["title"]."</a><li>";
                        }
                      }
                    }
                    echo "</ul>";
                  }
                }
                echo "</li>";
              }
            }
          }
          ?>
        </ul>
      </nav>
      <?php
      if (!empty($_SESSION["user"]["naam"])) {
        echo '<img class="navArrow navArrowOpen" src="images/leftArrow.svg" alt="leftArrow">';
      }
      ?>
    </header>
    <main class="new-user">
      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
        <input required type="text" name="pc_nummer" value="<?php echo $_POST["pc_nummer"]; ?>" placeholder="pc_nummer">
        <input required type="text" name="onderhoud_datum" value="<?php echo $_POST["onderhoud_datum"]; ?>" placeholder="onderhoud_datum">
        <select required name="apparaat_id">
          <option value="false">configuratie</option>
          <?php
          $configuraties = sqlSelect("83.82.240.2", "user", "pass", "project", "SELECT apparaat_id FROM configuraties");
          foreach ($configuraties as $key => $value) {
            echo "<option ";
            if ($_POST["apparaat_id"] == $value["apparaat_id"]) {
              echo "selected";
            }
            echo " value='".$value["apparaat_id"]."'>".$value["apparaat_id"]."</option>";
          }
          ?>
          <?php #form handler
        if (isset($_POST["submit"])) {

          // clean user input
          $pc_nummer = ucfirst(trim($_POST["pc_nummer"]));
          $onderhoud_datum = trim($_POST["onderhoud_datum"]);
          $apparaat_id = trim($_POST["apparaat_id"]);
          $email = trim($_POST["email"]);
          $gebruikersnaam = trim($_POST["gebruikersnaam"]);
          $wachtwoord = trim($_POST["wachtwoord"]);
          $configuraties_nummer = $_POST["configuraties_nummer"];
          $geslacht = $_POST["geslacht"];

          if ($_POST["afdelingen_id"] == false) {
            $afdelingen_id = null;
          } else {
            $afdelingen_id = $_POST["afdelingen_id"];
          }
          $toegangs_level = $_POST["toegangs_level"];
          $wachtwoord = hash("sha256", $wachtwoord);

          //file upload
          $uploadfile = "images/profiles/" . basename($_FILES["profile_path"]["name"]);
          if ($_FILES["profile_path"]["size"] < 300000) {
            if (move_uploaded_file($_FILES["profile_path"]["tmp_name"], $uploadfile)) {
              $file_uploaded = true;
              $profile_path = $_FILES["profile_path"]["name"];
            }else {
              if ($_FILES["profile_path"]["error"] == UPLOAD_ERR_NO_FILE) {
                $profile_path = "defaultProfile.svg";
              } else {
                echo "<meta http-equiv=\"refresh\" content=\"0; url=$thisPage?error=er ging iets fout met de afbeelding probeer het opnieuw of laat hem leeg voor een standaard afbeelding.&name=$_POST[name]&lastname=$_POST[lastname]&gender=$_POST[gender]&department_id=$_POST[department_id]&pc_nummer=$_POST[pc_nummer]&intern_tel=$_POST[intern_tel]&email=$_POST[email]&username=$_POST[username]\" />";
              }
            }
            $upload = true;
          } else {
            echo "<meta http-equiv=\"refresh\" content=\"0; url=$thisPage?error=bestandsgrootte is te groot, kies een bestand tussen de 0 en 0.3MB&name=$_POST[name]&lastname=$_POST[lastname]&gender=$_POST[gender]&department_id=$_POST[department_id]&pc_nummer=$_POST[pc_nummer]&intern_tel=$_POST[intern_tel]&email=$_POST[email]&username=$_POST[username]\" />";
          }
          if ($upload) {
            $sql = "INSERT INTO gebruikers(geslacht, voornaam, achternaam, afdelingen_id, intern_tel, email, configuraties_nummer, gebruikersnaam, wachtwoord, profile_path, toegangs_level)
            VALUES('$geslacht', \"$voornaam\",\"$achternaam\", $afdelingen_id, $intern_tel, '$email', '$configuraties_nummer', '$gebruikersnaam', '$wachtwoord', '$profile_path', '$toegangs_level')";
            $insert = dataToDb("83.82.240.2", "user", "pass", "project", "gebruikers", $sql);
            if ($insert === true) {
              if ($_SESSION["user"]["toegangs_level"] == "admin") {
                echo "<succes>account succesvol aangemaakt</succes><meta http-equiv=\"refresh\" content=\"2; url=user-overview.php\" />";
              } else {
                echo "<succes>account succesvol aangemaakt <a href='index.php'>login</a></succes><meta http-equiv=\"refresh\" content=\"2; url=index.php\" />";
              }
            } else {
              echo "<error>gebruikersnaam is al in gebruik</error>";
            }
          }
        }
        ?>
        </select>
      </form>
    </main>
    <script src="javascript/nav.js" charset="utf-8"></script>
  </body>
</html>
