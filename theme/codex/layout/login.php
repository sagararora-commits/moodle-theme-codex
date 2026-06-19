<?php
defined('MOODLE_INTERNAL') || die();

global $CFG, $SESSION;

$loginerrormsg = '';
if (!empty($SESSION->loginerrormsg)) {
    $loginerrormsg = $SESSION->loginerrormsg;
    unset($SESSION->loginerrormsg);
}

$bodyattributes = $OUTPUT->body_attributes(['login-codex']);
echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $bodyattributes; ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="codex-hidden-main"><?php echo $OUTPUT->main_content(); ?></div>

<div class="codex-login-wrapper">
    <div class="codex-login-left">
        <div class="brand">
            <img src="<?php echo $OUTPUT->image_url('logo', 'theme'); ?>" alt="Codex Education">
        </div>

        <h1 class="hero-title"><span class="accent">AI-Powered</span><br>Teaching Platform</h1>
        <p class="hero-sub">Transforming Assessment into Intelligence</p>

        <div class="feature-pills">
            <span class="pill"><span class="check">✓</span> Bloom's Taxonomy</span>
            <span class="pill"><span class="check">✓</span> SEL Profiling</span>
            <span class="pill"><span class="check">✓</span> 7-Day Reports</span>
        </div>

        <div class="illustration-wrap">
            <div class="illustration-backdrop"></div>
            <img class="hero-illustration" src="<?php echo $OUTPUT->image_url('login-hero', 'theme'); ?>" alt="">
            <div class="floating-badge badge-1">
                <div class="badge-icon" style="background: #fef3c7; color: #d97706;">📝</div>
                <div class="badge-text"><strong>Assessments</strong><span>Smart Diagnostics</span></div>
            </div>
            <div class="floating-badge badge-2">
                <div class="badge-icon" style="background: #dcfce7; color: #16a34a;">📊</div>
                <div class="badge-text"><strong>Analytics</strong><span>Real-time Insights</span></div>
            </div>
            <div class="floating-badge badge-3">
                <div class="badge-icon" style="background: #dbeafe; color: #2563eb;">📈</div>
                <div class="badge-text"><strong>Reports</strong><span>Bloom's Aware</span></div>
            </div>
        </div>

        <div class="stats-strip">
            <div class="stat"><strong>250+</strong><span>CBSE Schools</span></div>
            <div class="stat-divider"></div>
            <div class="stat"><strong>1,00,000+</strong><span>Students</span></div>
            <div class="stat-divider"></div>
            <div class="stat"><strong>9</strong><span>States</span></div>
        </div>
    </div>

    <div class="codex-login-right">
        <div class="login-card">
            <h2>Welcome Back!</h2>
            <p class="subtitle">Sign in to continue to Codex Education</p>

            <?php if ($loginerrormsg): ?>
                <div class="login-error"><?php echo s($loginerrormsg); ?></div>
            <?php endif; ?>

            <form action="<?php echo $CFG->wwwroot; ?>/login/index.php" method="post" id="login" class="codex-form">
                <input type="hidden" name="anchor" value="">
                <input type="hidden" name="logintoken" value="<?php echo s(\core\session\manager::get_login_token()); ?>">

                <div class="form-field">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <i class="fa fa-user input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" autofocus>
                    </div>
                </div>

                <div class="form-field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                    </div>
                </div>

                <div class="form-meta">
                    <label class="remember-check">
                        <input type="checkbox" name="rememberusername" value="1"> Remember me
                    </label>
                    <a href="<?php echo $CFG->wwwroot; ?>/login/forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" id="loginbtn" class="codex-signin-btn">Sign In</button>
            </form>

            <div class="powered-by">Powered by<br><strong>Codex Education</strong></div>
        </div>
    </div>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
