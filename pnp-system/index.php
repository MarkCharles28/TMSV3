<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get latest system announcements
$stmt = $conn->prepare("SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include 'includes/header.php';
?>

<section class="hero-section position-relative overflow-hidden mb-5">
    <div class="hero-bg"></div>
    <div class="container position-relative py-5 z-10">
        <div class="row align-items-center">
            <div class="col-lg-6 py-5">
                <h1 class="display-4 fw-bold mb-3 text-shadow">Welcome to Municipal Ticket Monitoring System</h1>
                <p class="lead mb-4 text-shadow-sm">Streamlining municipal services and payments for a better citizen experience</p>
                
                <?php if (!isLoggedIn()): ?>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="login.php" class="btn btn-light btn-lg shadow-sm">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i> Register
                    </a>
                </div>
                <?php else: ?>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="dashboard.php" class="btn btn-light btn-lg shadow-sm">
                        <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                    </a>
                    <a href="create-ticket.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-ticket-alt me-2"></i> Create New Ticket
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 d-none d-lg-flex justify-content-center">
                <div class="hero-image">
                    <img src="assets/images/hero-illustration.svg" alt="Municipal Services" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hero Wave -->
    <div class="wave-container">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 200">
            <path fill="#ffffff" fill-opacity="1" d="M0,32L48,53.3C96,75,192,117,288,117.3C384,117,480,75,576,85.3C672,96,768,160,864,165.3C960,171,1056,117,1152,96C1248,75,1344,85,1392,90.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
</section>

<div class="container">
    <div class="row mb-5">
        <div class="col-md-8">
            <div class="card border-0 shadow">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">System Announcements</h5>
                </div>
                <div class="card-body">
                    <?php if (count($announcements) > 0): ?>
                        <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700 me-3">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            </div>
                            <p class="text-muted small ms-5 ps-1">
                                <i class="fas fa-calendar-alt me-1"></i> 
                                <?php echo formatDate($announcement['created_at']); ?>
                            </p>
                            <div class="ms-5 ps-1">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="icon-circle mx-auto mb-3">
                                <i class="fas fa-bell-slash fa-2x"></i>
                            </div>
                            <p class="text-muted">No announcements at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <ul class="link-list">
                        <li>
                            <a href="#" class="link-list-item">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <span>Payment Services</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="link-list-item">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <span>Permits &amp; Licenses</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="link-list-item">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700">
                                    <i class="fas fa-gavel"></i>
                                </div>
                                <span>Ordinances</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="link-list-item">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <span>Events &amp; Programs</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="link-list-item">
                                <div class="icon-circle-sm bg-primary-100 text-primary-700">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <span>Citizen Assistance</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0 shadow contact-card">
                <div class="card-body">
                    <h5 class="card-title">Need Help?</h5>
                    <p class="text-muted mb-4">Our support team is ready to assist you with any questions or concerns.</p>
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle-sm bg-primary-100 text-primary-700 me-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Hotline</div>
                            <strong>(123) 456-7890</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle-sm bg-primary-100 text-primary-700 me-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Email</div>
                            <strong>info@municipal-pnp.gov</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle-sm bg-primary-100 text-primary-700 me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Office Hours</div>
                            <strong>Mon-Fri, 8AM-5PM</strong>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary w-100">
                        <i class="fas fa-headset me-2"></i> Request Support
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-12">
            <h3 class="text-center mb-4 fw-bold">Our Municipal Services</h3>
            <p class="text-center text-muted mb-5 col-lg-8 mx-auto">We provide a range of essential services to support our community and make government processes more accessible and efficient.</p>
        </div>
        <div class="col-md-4 mb-4">
            <div class="service-card">
                <div class="service-icon bg-primary-100 text-primary-700">
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
                <h5 class="mt-4 mb-3">Bills &amp; Payments</h5>
                <p class="text-muted">Pay your municipal bills online, track payment history, and receive digital receipts.</p>
                <a href="#" class="btn btn-outline-primary mt-2">Learn More</a>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="service-card">
                <div class="service-icon bg-success-light text-success-dark">
                    <i class="fas fa-id-card fa-2x"></i>
                </div>
                <h5 class="mt-4 mb-3">Permits &amp; Licenses</h5>
                <p class="text-muted">Apply for business permits, building permits, and other municipal licenses.</p>
                <a href="#" class="btn btn-outline-success mt-2">Learn More</a>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="service-card">
                <div class="service-icon bg-info-light text-info-dark">
                    <i class="fas fa-question-circle fa-2x"></i>
                </div>
                <h5 class="mt-4 mb-3">Support &amp; Inquiries</h5>
                <p class="text-muted">Get help with municipal services, submit inquiries, and track request status.</p>
                <a href="#" class="btn btn-outline-info mt-2">Learn More</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Hero Section */
.hero-section {
    background-color: var(--primary-600);
    color: white;
    margin-top: -1.5rem;
}

.hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('assets/images/pattern-bg.svg');
    background-size: cover;
    opacity: 0.1;
}

.text-shadow {
    text-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.text-shadow-sm {
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.z-10 {
    z-index: 10;
}

.wave-container {
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    line-height: 0;
}

.wave-container svg {
    display: block;
}

/* Link List */
.link-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.link-list-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-200);
    color: var(--gray-700);
    transition: all 0.2s ease;
}

.link-list-item:last-child {
    border-bottom: none;
}

.link-list-item:hover {
    color: var(--primary-700);
    transform: translateX(5px);
}

.link-list-item .icon-circle-sm {
    margin-right: 0.875rem;
}

/* Service Card */
.service-card {
    background-color: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    padding: 2rem;
    height: 100%;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--gray-200);
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}

.service-icon {
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Contact Card */
.contact-card {
    background: linear-gradient(to bottom right, var(--primary-50), white);
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 