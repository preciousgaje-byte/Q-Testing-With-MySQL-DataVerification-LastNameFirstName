<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB, root user, no password)
// ---------------------------------------------------------------
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

// ---------------------------------------------------------------
// Handle the POST request
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$conn->connect_error) {
    // Capture all four variables from the form
    $firstName   = trim($_POST["FirstName"]   ?? "");
    $lastName    = trim($_POST["LastName"]    ?? "");
    $govPosition = trim($_POST["GovPosition"] ?? "");
    $location    = trim($_POST["Location"]    ?? ""); // captured, but NEVER saved (see flaw below)

    // ===========================================================
    // INTENTIONAL FLAW (for QA testing purposes):
    // The form collects four fields, and $location is captured
    // above. However, the INSERT statement below deliberately
    // OMITS the Location column, both in the SQL string and in
    // bind_param(). As a result, the app shows a success message
    // but the Location value is never stored in the database.
    // A tester must catch this by checking the table directly.
    // ===========================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $firstName, $lastName, $govPosition); // Location omitted on purpose
        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: could not save the wish note.";
        }
        $stmt->close();
    } else {
        // Fallback error message if the query fails to prepare
        $errorMessage = "Error: the query could not be prepared.";
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
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f5;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #1f2d3d;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            padding: 32px 28px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        h2 {
            margin: 0 0 24px;
            text-align: center;
            color: #1e73be;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            margin-bottom: 18px;
            border: 1px solid #c5d0dc;
            border-radius: 6px;
            font-size: 15px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #1e73be;
            box-shadow: 0 0 0 3px rgba(30, 115, 190, 0.18);
        }
        .btn {
            padding: 11px 18px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary { width: 100%; background: #1e73be; color: #fff; }
        .btn-primary:hover { background: #155a96; }
        .btn-cancel { background: #e4e9ef; color: #1f2d3d; }
        .btn-cancel:hover { background: #d0d7df; }
        .btn-confirm { background: #1e73be; color: #fff; }
        .btn-confirm:hover { background: #155a96; }
        .message {
            padding: 12px 14px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .success { background: #e3f4ea; color: #1d7a42; border: 1px solid #b7e0c6; }
        .error   { background: #fdeaea; color: #b3261e; border: 1px solid #f3c1be; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 100;
        }
        .overlay.show { display: flex; }
        .modal {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 26px;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.3);
        }
        .modal h3 { margin: 0 0 16px; color: #1e73be; }
        .modal p { margin: 8px 0; font-size: 15px; word-break: break-word; }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>

        <?php if ($successMessage): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
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

            <button type="submit" class="btn btn-primary">Send Wish Note</button>
        </form>
    </div>

    <!-- Verification modal -->
    <div class="overlay" id="modalOverlay">
        <div class="modal">
            <h3>Verify Target Data</h3>
            <p><strong>First Name:</strong> <span id="mFirstName"></span></p>
            <p><strong>Last Name:</strong> <span id="mLastName"></span></p>
            <p><strong>Government Position:</strong> <span id="mGovPosition"></span></p>
            <p><strong>Location:</strong> <span id="mLocation"></span></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        const form      = document.getElementById("wishForm");
        const overlay   = document.getElementById("modalOverlay");
        const cancelBtn = document.getElementById("cancelBtn");
        const confirmBtn = document.getElementById("confirmBtn");

        // Intercept the submission and show the verification modal
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            document.getElementById("mFirstName").textContent   = form.FirstName.value;
            document.getElementById("mLastName").textContent    = form.LastName.value;
            document.getElementById("mGovPosition").textContent = form.GovPosition.value;
            document.getElementById("mLocation").textContent    = form.Location.value || "(none)";

            overlay.classList.add("show");
        });

        // Cancel closes the modal
        cancelBtn.addEventListener("click", function () {
            overlay.classList.remove("show");
        });

        // Confirm & Send bypasses the interception and posts the form
        confirmBtn.addEventListener("click", function () {
            form.submit();
        });
    </script>
</body>
</html>
