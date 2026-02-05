<?php ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);  

// Step 1: Start session
session_start();
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';
requireLogin();
$userId = currentUserId();

// Permission check
if (!hasPermission('view')) {
    die("You are not allowed to view forms.");
}

// Fetch ONLY logged-in user's forms
$stmt = $conn->prepare("
    SELECT id, first_name, last_name, grand_total, created_at
    FROM admissions
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();


$showLoginPopup = false;

if (isset($_SESSION['login_success'])) {
    $showLoginPopup = true;
    unset($_SESSION['login_success']);
}


// Step 2: Check if user is logged in
// if (!isset($_SESSION["user_id"])) {
//     // If user not logged in, send them back to login page
//     header("Location: ../auth/login.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

    <div class="container mt-5">

        <div class="card shadow p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2>Welcome, <?= htmlspecialchars($_SESSION["user_name"]) ?> 👋</h2>
        <p class="mb-0">Your email: <?= htmlspecialchars($_SESSION["user_email"]) ?></p>
    </div>

    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown">
            ⚙️
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php if (isAdmin()): ?>
                <li>
                    <a class="dropdown-item" href="../settings/add_user.php">
                        Add Roles
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a class="dropdown-item" href="../settings/change_password.php">
                    Change Password
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="../settings/users_list.php">Users List</a>
            </li>
        </ul>
    </div>
</div>

         

            <a href="../logout/logout.php" class="btn btn-danger mt-3">Logout</a>
              <div style="margin-top : 20px;  ">
                 <a href="../academy/academy_form.php" class="btn btn-primary">
                        Academy form
                 </a>
              </div>
        </div>

    </div>
   
    <h2>My Academy Forms</h2>

<table border="1" width="100%" cellpadding="8">
    <thead>
        <tr>
            <th>ID</th>
            <th>Student Name</th>
            <th>Grand Total</th>
            <th>Submitted On</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td>₹<?= $row['grand_total'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>

                <!-- VIEW -->
                <?php if (hasPermission('view')): ?>
                    <a href="view_admission.php?id=<?= $row['id'] ?>">View</a>
                <?php endif; ?>

                <!-- EDIT -->
                <?php if (hasPermission('edit')): ?>
                    | <a href="edit_form.php?id=<?= $row['id'] ?>">Edit</a>
                <?php endif; ?>

                <!-- DELETE -->
                <?php if (hasPermission('delete')): ?>
                    | <a href="delete_form.php?id=<?= $row['id'] ?>"
                       onclick="return confirm('Are you sure?')">Delete</a>
                <?php endif; ?>

            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="5">No forms submitted yet.</td>
    </tr>
<?php endif; ?>

    </tbody>
</table>

<script>
    const showLoginPopup = <?php echo $showLoginPopup ? 'true' : 'false'; ?>;

    if (showLoginPopup) {
        alert("Login successful!");
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
