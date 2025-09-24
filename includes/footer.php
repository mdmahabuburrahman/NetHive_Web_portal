          <!-- partial:partials/_footer.html -->
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2025 <a href="https://mrsagor.com/" target="_blank">Md. Mahabubur Rahman</a>. All rights reserved.</span>
              <!-- <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i class="mdi mdi-heart text-danger"></i></span> -->
            </div>
          </footer>
          <!-- partial -->
          </div>
          <!-- main-panel ends -->
          </div>
          <!-- page-body-wrapper ends -->
          </div>
          <!-- container-scroller -->
          <!-- plugins:js -->
          <script src="assets/vendors/js/vendor.bundle.base.js"></script>
          <!-- endinject -->
          <!-- Plugin js for this page -->
          <script src="assets/vendors/chart.js/chart.umd.js"></script>
          <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
          <!-- jQuery MUST come first -->
          <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
          <!-- Then DataTables -->
          <script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
          <!-- End plugin js for this page -->
          <!-- Toast Manager -->
          <script src="assets/js/toast-manager.js"></script>
          <!-- endinject -->
          <!-- Custom js for this page -->
          <!-- Conditional NAS Management JS -->
          <?php
          // Load navbar connection manager for specific pages
          $navbar_connection_pages = ['dashboard', 'hotspot', 'queue', 'userlogs', 'webBlocking', 'reports'];
          if (in_array($page, $navbar_connection_pages)) {
            echo '<script src="assets/js/views/navbar-connection.js"></script>';
          }
          ?>
          <!-- End custom js for this page -->
          <!-- Load SortableJS before our queue script -->
          <script src="assets/vendors/sortablejs/sortable.min.js"></script>
          <script>
            // Debug information
            console.log('SortableJS loaded:', typeof Sortable !== 'undefined');
            console.log('jQuery loaded:', typeof $ !== 'undefined');

            // Force SortableJS to be available globally
            if (typeof Sortable !== 'undefined') {
              window.Sortable = Sortable;
            }
          </script>


          <!-- add script files based on page -->
          <?php
          $page_js = "assets/js/views/{$page}.js";
          if (file_exists($page_js)) {
            echo "<script src='{$page_js}'></script>";
          }
          // else {
          //     echo "<!-- No script file found for {$page} -->";
          // }
          ?>
          </body>

          </html>