<?php
$pageTitle = 'Billing & Subscription Terms - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageCss = ['content.css'];

include __DIR__ . '/includes/header.php';
?>

<main class="content-main">
    <div class="content-container">
        <h1>Billing & Subscription Terms</h1>

        <p class="effective-date"><strong>Effective Date:</strong> January 30, 2026</p>

        <p>This document outlines the billing practices, subscription terms, and refund policies for Kinky-Thots paid services.</p>

        <section>
            <h2>1. Subscription Plans</h2>
            <p>We offer the following subscription tiers:</p>

            <table class="pricing-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Billing Cycle</th>
                        <th>Content Access</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Free</td>
                        <td>$0</td>
                        <td>N/A</td>
                        <td>Teaser videos (&lt; 1 minute)</td>
                    </tr>
                    <tr>
                        <td>Plus</td>
                        <td>$8</td>
                        <td>Monthly (31 days)</td>
                        <td>Plus videos (1-5 minutes)</td>
                    </tr>
                    <tr>
                        <td>Premium</td>
                        <td>$15</td>
                        <td>Monthly (31 days)</td>
                        <td>Full-length videos (all content)</td>
                    </tr>
                    <tr>
                        <td>Lifetime</td>
                        <td>$250</td>
                        <td>One-time payment</td>
                        <td>Full-length videos (all content, forever)</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>2. Payment Methods</h2>
            <p>We accept cryptocurrency payments through NOWPayments, including:</p>
            <ul>
                <li>Bitcoin (BTC)</li>
                <li>Ethereum (ETH)</li>
                <li>USDT (TRC20)</li>
                <li>Litecoin (LTC)</li>
                <li>Monero (XMR)</li>
                <li>Solana (SOL)</li>
                <li>And 200+ other cryptocurrencies</li>
            </ul>
            <p><strong>Note:</strong> We do not accept credit cards, debit cards, or PayPal.</p>
        </section>

        <section>
            <h2>3. Billing Cycle & Auto-Renewal</h2>

            <h3>Monthly Subscriptions (Basic & Premium)</h3>
            <ul>
                <li>Subscriptions are billed every 31 days</li>
                <li><strong>Auto-renewal:</strong> Monthly subscriptions automatically renew unless canceled</li>
                <li>You will receive an email reminder before each renewal</li>
                <li>The renewal payment must be completed within 24 hours of the reminder to maintain uninterrupted access</li>
            </ul>

            <h3>Lifetime Subscription</h3>
            <ul>
                <li>One-time payment with no recurring charges</li>
                <li>Access never expires as long as the Service exists</li>
                <li>If the Service is discontinued, no refund is provided for Lifetime subscriptions</li>
            </ul>
        </section>

        <section>
            <h2>4. Cancellation</h2>
            <p>You may cancel your subscription at any time:</p>
            <ol>
                <li>Go to your <a href="/profile.php">Profile</a> page</li>
                <li>Click "Cancel Subscription"</li>
                <li>Confirm cancellation</li>
            </ol>

            <p><strong>Effect of cancellation:</strong></p>
            <ul>
                <li>Your access continues until the end of your current billing period</li>
                <li>No further charges will be made</li>
                <li>You can resubscribe at any time</li>
            </ul>
        </section>

        <section>
            <h2>5. Refund Policy</h2>

            <div class="important-notice">
                <h3>Cryptocurrency Payments Are Non-Refundable</h3>
                <p>Due to the nature of cryptocurrency transactions, <strong>all payments are final and non-refundable</strong>. This includes:</p>
                <ul>
                    <li>Monthly subscription payments</li>
                    <li>Lifetime subscription payments</li>
                    <li>Partial payments or overpayments</li>
                </ul>
            </div>

            <h3>Exceptions</h3>
            <p>We may consider refunds or credits in the following exceptional circumstances:</p>
            <ul>
                <li><strong>Technical Issues:</strong> If a payment was processed but access was not granted due to a technical error on our end</li>
                <li><strong>Duplicate Charges:</strong> If you were accidentally charged multiple times for the same subscription period</li>
            </ul>
            <p>To request an exception, contact <a href="mailto:admin@kinky-thots.com">admin@kinky-thots.com</a> with your transaction ID and details.</p>
        </section>

        <section>
            <h2>6. Failed Payments</h2>
            <ul>
                <li>If a renewal payment is not received, your subscription will be downgraded to Free tier</li>
                <li>You will not be charged again until you manually resubscribe</li>
                <li>No penalty fees are applied for failed renewals</li>
            </ul>
        </section>

        <section>
            <h2>7. Price Changes</h2>
            <p>We reserve the right to change subscription prices at any time. If prices increase:</p>
            <ul>
                <li>Existing subscribers will be notified at least 30 days in advance</li>
                <li>The new price takes effect at your next renewal</li>
                <li>You may cancel before the renewal to avoid the new price</li>
                <li>Lifetime subscriptions are not affected by price changes</li>
            </ul>
        </section>

        <section>
            <h2>8. Account Termination</h2>
            <p>If your account is terminated for violating our <a href="/terms.php">Terms of Service</a>:</p>
            <ul>
                <li>No refund will be provided for any remaining subscription time</li>
                <li>You may not create a new account to circumvent the termination</li>
            </ul>
        </section>

        <section>
            <h2>9. Disputes</h2>
            <p>For billing disputes, please contact us at <a href="mailto:admin@kinky-thots.com">admin@kinky-thots.com</a> before initiating any chargeback or dispute with your cryptocurrency exchange. We will work to resolve issues promptly.</p>
        </section>

        <section>
            <h2>10. Contact</h2>
            <p>For billing questions or issues:</p>
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
.pricing-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    background: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
}
.pricing-table th,
.pricing-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #333;
}
.pricing-table th {
    background: #222;
    color: #f805a7;
    font-weight: 600;
}
.pricing-table td {
    color: #ccc;
}
.pricing-table tr:last-child td {
    border-bottom: none;
}
.important-notice {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
}
.important-notice h3 {
    color: #e74c3c;
    margin-top: 0;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer-scripts.php'; ?>
