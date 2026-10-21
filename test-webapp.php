<?php
// ---------------------------------------------------------------
// Kara David's Wish Note - QAT test web application
// Single-file PHP app (HTML + CSS + vanilla JS)
// ---------------------------------------------------------------

// Local MariaDB/MySQL connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$conn = new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Database connection failed: " . htmlspecialchars($conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorMessage === "") {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");   // captured, but never saved (see flaw below)

    // ===============================================================
    // INTENTIONAL FLAW (for QA testing purposes):
    // The Location value is captured above, but the INSERT statement
    // below deliberately OMITS the Location column, both in the SQL
    // string and in bind_param(). The app still reports "Success!",
    // yet the Location column in main_corrupt_politicians stays
    // empty/NULL. A tester should catch this by comparing the form
    // entry against the database rows.
    // ===============================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // no $Location here
        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: could not save the wish note.";
        }
        $stmt->close();
    } else {
        // Fallback error if the query fails to prepare
        $errorMessage = "Error: the query could not be prepared. Please check the database table.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kara David's Wish Note</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; padding: 20px; min-height: 100vh;
    background: #eef0f3; font-family: "Segoe UI", Arial, sans-serif; color: #263238;
    display: flex; align-items: center; justify-content: center;
  }
  .card {
    width: 100%; max-width: 480px; background: #fff; padding: 32px 28px;
    border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.12);
  }
  h2 { margin: 0 0 20px; text-align: center; color: #1976d2; }
  label { display: block; margin: 14px 0 6px; font-weight: 600; font-size: 14px; }
  input[type="text"] {
    width: 100%; padding: 10px 12px; font-size: 15px;
    border: 1px solid #cfd8dc; border-radius: 6px;
  }
  input[type="text"]:focus { outline: none; border-color: #1976d2; box-shadow: 0 0 0 3px rgba(25,118,210,0.15); }
  button {
    border: none; border-radius: 6px; padding: 11px 18px; font-size: 15px;
    cursor: pointer; transition: background 0.2s;
  }
  .btn-primary { width: 100%; margin-top: 22px; background: #1976d2; color: #fff; }
  .btn-primary:hover { background: #0d47a1; }
  .btn-cancel { background: #eceff1; color: #37474f; }
  .btn-cancel:hover { background: #cfd8dc; }
  .btn-confirm { background: #1976d2; color: #fff; }
  .btn-confirm:hover { background: #0d47a1; }
  .msg { padding: 12px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
  .msg.success { background: #e3f2fd; color: #0d47a1; border: 1px solid #90caf9; }
  .msg.error { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
  .overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    align-items: center; justify-content: center; padding: 20px; z-index: 10;
  }
  .overlay.show { display: flex; }
  .modal {
    width: 100%; max-width: 420px; background: #fff; border-radius: 10px;
    padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  }
  .modal h3 { margin: 0 0 14px; color: #1976d2; }
  .modal dl { margin: 0 0 18px; }
  .modal dt { font-size: 12px; color: #607d8b; margin-top: 10px; }
  .modal dd { margin: 2px 0 0; font-weight: 600; word-break: break-word; }
  .modal .actions { display: flex; gap: 10px; justify-content: flex-end; }
</style>
</head>
<body>
<div class="card">
  <h2>Kara David's Wish Note</h2>

  <?php if ($successMessage): ?>
    <div class="msg success"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>
  <?php if ($errorMessage): ?>
    <div class="msg error"><?= htmlspecialchars($errorMessage) ?></div>
  <?php endif; ?>

  <form id="wishForm" method="POST" action="">
    <label for="FirstName">Target's First Name</label>
    <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

    <label for="LastName">Target's Last Name</label>
    <input type="text" id="LastName" name="LastName" maxlength="30" required>

    <label for="GovPosition">Target's Government Position</label>
    <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

    <label for="Location">Target's Location</label>
    <input type="text" id="Location" name="Location" maxlength="40">

    <button type="submit" class="btn-primary">Send Wish Note</button>
  </form>
</div>

<div class="overlay" id="overlay">
  <div class="modal">
    <h3>Verify Target Data</h3>
    <dl>
      <dt>Target's First Name</dt><dd id="mFirst"></dd>
      <dt>Target's Last Name</dt><dd id="mLast"></dd>
      <dt>Target's Government Position</dt><dd id="mPos"></dd>
      <dt>Target's Location</dt><dd id="mLoc"></dd>
    </dl>
    <div class="actions">
      <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
      <button type="button" class="btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
    </div>
  </div>
</div>

<script>
  var form = document.getElementById("wishForm");
  var overlay = document.getElementById("overlay");

  form.addEventListener("submit", function (e) {
    e.preventDefault();                       // intercept submission
    if (!form.reportValidity()) { return; }   // respect required fields
    document.getElementById("mFirst").textContent = form.FirstName.value;
    document.getElementById("mLast").textContent  = form.LastName.value;
    document.getElementById("mPos").textContent   = form.GovPosition.value;
    document.getElementById("mLoc").textContent   = form.Location.value || "(none)";
    overlay.classList.add("show");
  });

  document.getElementById("cancelBtn").addEventListener("click", function () {
    overlay.classList.remove("show");
  });

  document.getElementById("confirmBtn").addEventListener("click", function () {
    form.submit();                            // bypasses the interception
  });
</script>
</body>
</html>
