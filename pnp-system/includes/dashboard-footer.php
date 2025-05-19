        </div><!-- /.content-wrapper -->
    </div><!-- /#main-content -->
    
    <!-- Footer Scripts -->
    <script src="<?php echo SITE_URL; ?>assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/dashboard-nav.js"></script>
    
    <?php if (isset($extraScripts) && !empty($extraScripts)): ?>
        <!-- Extra page-specific scripts -->
        <?php echo $extraScripts; ?>
    <?php endif; ?>
    
    <script>
    // Initialize tooltips and popovers
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    </script>
</body>
</html> 