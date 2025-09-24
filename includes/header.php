<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>NetHive - <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></title>
  <!-- Preload critical resources -->
  <link rel="preload" href="assets/vendors/css/vendor.bundle.base.css" as="style">
  <link rel="preload" href="assets/css/style.css" as="style">
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">
  <!-- Layout styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Toast Manager CSS -->
  <link rel="stylesheet" href="assets/css/toast-manager.css">
  <!-- page specific css -->
  <?php
  $page_css = "assets/css/views/{$page}.css";
  if (file_exists($page_css)) {
    echo "<link rel='stylesheet' href='{$page_css}'>";
  }
  // else {
  //     echo "<!-- No script file found for {$page} -->";
  // }
  ?>
  <!-- End layout styles -->
  <link rel="shortcut icon" href="assets/images/favicon.ico" />
</head>

<body>
  <div class="container-scroller">