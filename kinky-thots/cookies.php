<?php
$pageTitle = 'Cookie Policy - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageCss = ['content.css'];

include __DIR__ . '/includes/header.php';
?>

<main class="content-main">
    <div class="content-container">
        <h1>Cookie Policy</h1>

        <p class="effective-date"><strong>Effective Date:</strong> January 30, 2026</p>

        <p>This Cookie Policy explains how Kinky-Thots ("we," "us," or "our") uses cookies and similar technologies when you visit our website at kinky-thots.xxx.</p>

        <section>
            <h2>1. What Are Cookies?</h2>
            <p>Cookies are small text files that are stored on your device (computer, tablet, or mobile) when you visit a website. They help the website remember your preferences and understand how you use the site.</p>
        </section>

        <section>
            <h2>2. Types of Cookies We Use</h2>

            <h3>2.1 Essential Cookies</h3>
            <p>These cookies are necessary for the website to function properly. They cannot be disabled.</p>
            <table class="cookie-table">
                <thead>
                    <tr>
                        <th>Cookie Name</th>
                        <th>Purpose</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>kt_auth_token</td>
                        <td>Authentication - keeps you logged in</td>
                        <td>7 days</td>
                    </tr>
                    <tr>
                        <td>kt_session</td>
                        <td>Session management</td>
                        <td>Session</td>
                    </tr>
                    <tr>
                        <td>cf_clearance</td>
                        <td>Cloudflare security verification</td>
                        <td>1 year</td>
                    </tr>
                </tbody>
            </table>

            <h3>2.2 Functional Cookies</h3>
            <p>These cookies enable enhanced functionality and personalization.</p>
            <table class="cookie-table">
                <thead>
                    <tr>
                        <th>Cookie Name</th>
                        <th>Purpose</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>kt_preferences</td>
                        <td>Stores user preferences (theme, volume, etc.)</td>
                        <td>1 year</td>
                    </tr>
                    <tr>
                        <td>kt_age_verified</td>
                        <td>Remembers age verification</td>
                        <td>30 days</td>
                    </tr>
                </tbody>
            </table>

            <h3>2.3 Analytics Cookies</h3>
            <p>These cookies help us understand how visitors interact with our website.</p>
            <table class="cookie-table">
                <thead>
                    <tr>
                        <th>Cookie Name</th>
                        <th>Purpose</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>_ga, _gid</td>
                        <td>Google Analytics - anonymous usage statistics</td>
                        <td>2 years / 24 hours</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>3. Third-Party Cookies</h2>
            <p>Some cookies are placed by third-party services that appear on our pages:</p>
            <ul>
                <li><strong>Cloudflare:</strong> Security and performance optimization</li>
                <li><strong>Turnstile:</strong> Bot protection (anti-spam verification)</li>
            </ul>
        </section>

        <section>
            <h2>4. Local Storage</h2>
            <p>In addition to cookies, we use browser local storage to store:</p>
            <ul>
                <li><strong>kt_auth_token:</strong> Your authentication token</li>
                <li><strong>kt_auth_user:</strong> Basic user information for display</li>
                <li><strong>kt_chat_color:</strong> Your chosen chat color</li>
            </ul>
        </section>

        <section>
            <h2>5. Managing Cookies</h2>
            <p>You can control and manage cookies in several ways:</p>

            <h3>Browser Settings</h3>
            <p>Most browsers allow you to:</p>
            <ul>
                <li>View what cookies are stored and delete them individually</li>
                <li>Block third-party cookies</li>
                <li>Block all cookies</li>
                <li>Clear all cookies when you close the browser</li>
            </ul>

            <p>Instructions for common browsers:</p>
            <ul>
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener">Mozilla Firefox</a></li>
                <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank" rel="noopener">Safari</a></li>
                <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Microsoft Edge</a></li>
            </ul>

            <p><strong>Note:</strong> Blocking essential cookies may prevent you from using certain features of our website, including logging in.</p>
        </section>

        <section>
            <h2>6. Do Not Track</h2>
            <p>Some browsers have a "Do Not Track" feature. Our website does not currently respond to Do Not Track signals, as there is no industry standard for how to handle them.</p>
        </section>

        <section>
            <h2>7. Changes to This Policy</h2>
            <p>We may update this Cookie Policy from time to time. The updated policy will be posted on this page with a new "Effective Date."</p>
        </section>

        <section>
            <h2>8. Contact Us</h2>
            <p>If you have questions about our use of cookies, please contact us:</p>
            <p>Email: <a href="mailto:admin@kinky-thots.com">admin@kinky-thots.com</a></p>
        </section>

        <div class="legal-nav">
            <a href="/terms.php">Terms of Service</a>
            <a href="/privacy.php">Privacy Policy</a>
            <a href="/2257.php">2257 Compliance</a>
            <a href="/dmca.php">DMCA Policy</a>
        </div>
    </div>
</main>

<style>
.cookie-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    background: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
}
.cookie-table th,
.cookie-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #333;
}
.cookie-table th {
    background: #222;
    color: #f805a7;
    font-weight: 600;
}
.cookie-table td {
    color: #ccc;
}
.cookie-table tr:last-child td {
    border-bottom: none;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
