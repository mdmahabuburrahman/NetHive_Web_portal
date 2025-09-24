<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get user input
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Read users from JSON file
  $users_json = file_get_contents(__DIR__ . '/data/users.json');
  $users_data = json_decode($users_json, true);

  // Check credentials
  $authenticated = false;
  foreach ($users_data['users'] as $user) {
    if ($user['username'] === $username && password_verify($password, $user['password'])) {
      $authenticated = true;
      $_SESSION['user'] = [
        'username' => $user['username'],
        'fullName' => $user['fullName'],
        'role' => $user['role']
      ];
      header('Location: index.php');
      exit;
    }
  }

  if (!$authenticated) {
    $error_message = "Invalid username or password";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>NetHive Admin</title>
  <!-- Layout styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <style>
    @keyframes spinner {
      to {
        transform: rotate(360deg);
      }
    }

    .spinner {
      width: 16px;
      height: 16px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top: 3px solid #ffffff;
      border-radius: 50%;
      animation: spinner 0.8s linear infinite;
      vertical-align: middle;
      margin-left: 10px;
      position: relative;
      top: -1px;
      display: none;
    }

    .spinner.show {
      display: inline-block !important;
    }

    .auth-form-btn {
      display: flex !important;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .content-wrapper.auth {
      min-height: 100vh !important;
    }

    .auth .auth-form-light {
      max-width: 100%;
      margin: 0 auto;
      border-radius: 10px;
    }

    .brand-logo img {
      margin: 0 auto;
      max-height: 40px;
      width: auto;
    }

    .form-group {
      text-align: left;
      margin-bottom: 1.5rem;
    }

    .alert {
      text-align: center;
      margin-bottom: 1.5rem;
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth">
        <div class="row flex-grow">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-center p-5">
              <div class="brand-logo text-center mb-4">
                <img src="assets/images/logo.png">
              </div>
              <h4 class="mb-3">Hello! let's get started</h4>
              <h6 class="font-weight-light mb-4">Sign in to continue.</h6>
              <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                  <?php echo htmlspecialchars($error_message); ?>
                </div>
              <?php endif; ?>
              <form class="pt-3" method="POST" action="">
                <div class="form-group">
                  <input type="text" name="username" class="form-control form-control-lg" placeholder="Username" required>
                </div>
                <div class="form-group">
                  <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
                </div>
                <div class="mt-3 d-grid gap-2">
                  <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">
                    <span>SIGN IN</span>
                    <div class="spinner" id="loginSpinner"></div>
                  </button>
                </div>
              </form>
              <script>
                document.addEventListener('DOMContentLoaded', function() {
                  const form = document.querySelector('form');
                  const spinner = document.getElementById('loginSpinner');
                  const submitButton = form.querySelector('button[type="submit"]');

                  form.addEventListener('submit', function(e) {
                    spinner.classList.add('show');
                    submitButton.disabled = true;
                  });
                });
              </script>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>