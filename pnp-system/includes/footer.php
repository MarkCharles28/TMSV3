    </main>
    
    <footer class="footer mt-auto py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-section">
                        <h5 class="text-primary-700 mb-4 fw-semibold">Contact Information</h5>
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-center mb-3">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span>123 Municipal Ave, City</span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <span>(123) 456-7890</span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span>info@municipal-pnp.gov</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="footer-section">
                        <h5 class="text-primary-700 mb-4 fw-semibold">Quick Links</h5>
                        <div class="row">
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="footer-link">Home</a></li>
                                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>about.php" class="footer-link">About Us</a></li>
                                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>services.php" class="footer-link">Services</a></li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>contact.php" class="footer-link">Contact Us</a></li>
                                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>faq.php" class="footer-link">FAQ</a></li>
                                    <li class="mb-2"><a href="#" class="footer-link">Privacy Policy</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="footer-section">
                        <h5 class="text-primary-700 mb-4 fw-semibold">Office Hours</h5>
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-center mb-3">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <strong>Monday - Friday:</strong><br>
                                    <span class="text-muted">8:00 AM - 5:00 PM</span>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <strong>Saturday:</strong><br>
                                    <span class="text-muted">8:00 AM - 12:00 PM</span>
                                </div>
                            </li>
                            <li class="d-flex align-items-center">
                                <div class="icon-circle-sm me-3 bg-primary-100 text-primary-700">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <strong>Sunday:</strong><br>
                                    <span class="text-muted">Closed</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <hr class="my-4 bg-gray-200">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-md-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-icons">
                        <a href="#" class="social-icon me-3" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon me-3" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon me-3" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript Dependencies -->
    <!-- Bootstrap JS is already loaded in the header -->
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) == 'reports.php' || basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php'): ?>
    <script src="<?php echo SITE_URL; ?>assets/js/charts.js"></script>
    <?php endif; ?>
</body>
</html> 