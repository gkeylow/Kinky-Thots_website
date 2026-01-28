<?php
$pageTitle = 'Checkout - Kinky Thots';
$pageRobots = 'noindex,nofollow';
$pageStyles = '
    .checkout-container {
        max-width: 600px;
        margin: 100px auto 40px;
        padding: 0 20px;
    }
    .checkout-card {
        background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        border: 1px solid rgba(11, 208, 243, 0.3);
        border-radius: 16px;
        padding: 2rem;
    }
    .checkout-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .checkout-header h1 {
        font-size: 1.75rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }
    .checkout-header p { color: #888; }
    .order-summary { margin-bottom: 2rem; }
    .order-summary h2 { font-size: 1.1rem; color: #fff; margin-bottom: 1rem; }
    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: rgba(0,0,0,0.3);
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }
    .order-item-name { font-weight: 600; color: #fff; }
    .order-item-desc { font-size: 0.85rem; color: #888; margin-top: 4px; }
    .order-item-price {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .order-item-price span { font-size: 0.85rem; -webkit-text-fill-color: #888; }
    .features-list { list-style: none; padding: 0; margin: 1rem 0; }
    .features-list li { padding: 0.5rem 0; color: #ccc; font-size: 0.9rem; }
    .features-list li::before { content: "✓"; color: #2ecc71; margin-right: 10px; }
    .payment-section { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
    .payment-section h2 { font-size: 1.1rem; color: #fff; margin-bottom: 1rem; }
    .login-required {
        text-align: center;
        padding: 2rem;
        background: rgba(231, 76, 60, 0.1);
        border: 1px solid rgba(231, 76, 60, 0.3);
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .login-required p { color: #e74c3c; margin-bottom: 1rem; }
    .login-required a { color: #0bd0f3; text-decoration: none; }
    .login-required a:hover { text-decoration: underline; }
    .payment-container {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .payment-loading { color: #888; text-align: center; }
    .checkout-btn {
        display: block;
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: opacity 0.2s;
    }
    .checkout-btn:hover { opacity: 0.9; }
    .checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #0bd0f3; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
    .error-message {
        color: #e74c3c;
        text-align: center;
        padding: 1rem;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 8px;
        margin-bottom: 1rem;
        display: none;
    }
    .success-message { text-align: center; padding: 2rem; }
    .success-message h2 { color: #2ecc71; font-size: 1.5rem; margin-bottom: 1rem; }
    .success-message p { color: #ccc; margin-bottom: 1.5rem; }
    .crypto-payment {
        text-align: center;
        padding: 2rem;
        background: rgba(11, 208, 243, 0.05);
        border: 1px solid rgba(11, 208, 243, 0.2);
        border-radius: 12px;
    }
    .currency-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 1.5rem 0; }
    .currency-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1rem 0.5rem;
        background: rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        color: #fff;
    }
    .currency-btn:hover { border-color: rgba(11, 208, 243, 0.5); background: rgba(11, 208, 243, 0.1); }
    .currency-btn.selected { border-color: #0bd0f3; background: rgba(11, 208, 243, 0.2); }
    .currency-btn .icon { font-size: 1.5rem; margin-bottom: 0.25rem; }
    .currency-btn .name { font-size: 0.75rem; color: #888; }
    .payment-details { background: rgba(0,0,0,0.4); border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
    .qr-container { background: #fff; padding: 1rem; border-radius: 8px; display: inline-block; margin: 1rem 0; }
    .qr-container canvas { display: block; }
    .pay-address {
        background: rgba(0,0,0,0.5);
        padding: 0.75rem 1rem;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.8rem;
        word-break: break-all;
        color: #0bd0f3;
        margin: 0.5rem 0;
    }
    .pay-amount { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0.5rem 0; }
    .pay-amount .currency { color: #0bd0f3; font-size: 1rem; }
    .copy-btn {
        background: linear-gradient(135deg, #f805a7, #0bd0f3);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        color: #fff;
        font-size: 0.85rem;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: opacity 0.2s;
    }
    .copy-btn:hover { opacity: 0.9; }
    .copy-btn.copied { background: #2ecc71; }
    .memo-warning {
        background: rgba(243, 156, 18, 0.2);
        border: 1px solid rgba(243, 156, 18, 0.5);
        padding: 0.75rem;
        border-radius: 6px;
        margin: 1rem 0;
        font-size: 0.85rem;
        color: #f39c12;
    }
    .payment-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border-radius: 6px;
        margin-top: 1rem;
        font-weight: 600;
    }
    .payment-status.waiting { background: rgba(52, 152, 219, 0.2); color: #3498db; }
    .payment-status.confirming { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
    .payment-status.confirmed, .payment-status.finished { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
    .payment-status.expired, .payment-status.failed { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
    .status-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .countdown { font-size: 0.9rem; color: #888; margin-top: 0.5rem; }
    .countdown.urgent { color: #e74c3c; }
    .network-info { font-size: 0.8rem; color: #888; margin-top: 0.5rem; }
    @media (max-width: 480px) {
        .checkout-container { margin-top: 80px; }
        .checkout-card { padding: 1.5rem; }
    }
';
include __DIR__ . '/includes/header.php';
?>

<main class="checkout-container">
    <div class="checkout-card">
        <div class="checkout-header">
            <h1>Complete Your Subscription</h1>
            <p>Unlock exclusive content</p>
        </div>

        <div id="errorMessage" class="error-message"></div>

        <div id="orderSummary" class="order-summary">
            <!-- Populated by JavaScript -->
        </div>

        <div class="payment-section">
            <h2>Payment Method</h2>
            <div id="loginRequired" class="login-required" style="display: none;">
                <p>You must be logged in to subscribe</p>
                <a href="#" id="loginLink">Click here to login or register</a>
            </div>
            <div id="paymentContainer" class="payment-container">
                <div class="payment-loading">Loading payment options...</div>
            </div>
        </div>

        <a href="/subscriptions.php" class="back-link">← Back to plans</a>
    </div>

    <!-- Success state (hidden by default) -->
    <div id="successState" class="checkout-card" style="display: none;">
        <div class="success-message">
            <h2>Subscription Activated!</h2>
            <p>Thank you for subscribing. Your account has been upgraded and you now have access to exclusive content.</p>
            <a href="/free-content.php" class="checkout-btn">Browse Content</a>
        </div>
    </div>

    <!-- Partial payment state (hidden by default) -->
    <div id="partialState" class="checkout-card" style="display: none;">
        <div class="partial-message">
            <h2 style="color: #f39c12;">Partial Payment Received</h2>
            <p>We received a partial payment for your subscription. This can happen if:</p>
            <ul style="text-align: left; color: #888; margin: 1rem 0; padding-left: 1.5rem;">
                <li>Network fees reduced the amount received</li>
                <li>The crypto amount sent was slightly less than required</li>
            </ul>
            <p style="margin-top: 1rem;">Please contact us to resolve this issue:</p>
            <a href="mailto:admin@kinky-thots.xxx" class="checkout-btn" style="background: linear-gradient(135deg, #f39c12, #e67e22);">Contact Support</a>
            <a href="/subscriptions.php" class="back-link" style="margin-top: 1rem;">Try Again</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- QR Code library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    const AUTH_TOKEN_KEY = 'kt_auth_token';
    const AUTH_USER_KEY = 'kt_auth_user';

    // Get params from URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedTier = urlParams.get('tier') || 'basic';
    const isSuccess = urlParams.get('status') === 'success';
    const isFailed = urlParams.get('status') === 'failed';
    const isPartial = urlParams.get('status') === 'partial';

    // Popular currencies with icons
    const POPULAR_CURRENCIES = [
        { id: 'btc', name: 'Bitcoin', icon: '₿' },
        { id: 'eth', name: 'Ethereum', icon: 'Ξ' },
        { id: 'usdttrc20', name: 'USDT TRC20', icon: '₮' },
        { id: 'ltc', name: 'Litecoin', icon: 'Ł' },
        { id: 'xmr', name: 'Monero', icon: 'ɱ' },
        { id: 'sol', name: 'Solana', icon: '◎' }
    ];

    // Currencies that require memo/tag
    const MEMO_CURRENCIES = ['xrp', 'xlm', 'eos', 'bnbmainnet', 'atom', 'xem'];

    let tierData = null;
    let currentPayment = null;
    let statusPollInterval = null;
    let countdownInterval = null;
    let authToken = null;

    async function init() {
        const user = JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null');
        authToken = localStorage.getItem(AUTH_TOKEN_KEY);

        // Handle payment return status
        if (isSuccess) {
            showSuccess();
            return;
        }

        if (isFailed) {
            showError('Payment was not completed. Please try again.');
        }

        if (isPartial) {
            showPartialPayment();
            return;
        }

        // Check login status
        if (!user || !authToken) {
            document.getElementById('loginRequired').style.display = 'block';
            document.getElementById('paymentContainer').innerHTML = '';
            const loginLink = document.getElementById('loginLink');
            const redirectUrl = encodeURIComponent(window.location.href);
            loginLink.href = `/login.php?redirect=${redirectUrl}`;
            return;
        }

        // Load tier data
        try {
            const response = await fetch('/api/subscriptions/tiers');
            const data = await response.json();
            tierData = data.tiers.find(t => t.id === selectedTier);

            if (!tierData) {
                showError('Invalid subscription tier');
                return;
            }

            // Check if already subscribed to this tier or higher
            if (user.subscription_tier === selectedTier ||
                user.subscription_tier === 'yearly' ||
                (user.subscription_tier === 'premium' && selectedTier === 'basic') ||
                user.subscription_tier === 'vip') {
                showError('You already have this subscription or better');
                document.getElementById('paymentContainer').innerHTML =
                    '<a href="/profile.php" class="checkout-btn">View Your Profile</a>';
                return;
            }

            renderOrderSummary(tierData);
            showCurrencySelector();
        } catch (err) {
            showError('Failed to load subscription data');
        }
    }

    function renderOrderSummary(tier) {
        const container = document.getElementById('orderSummary');
        const isYearly = selectedTier === 'yearly';
        const priceDisplay = isYearly
            ? `$${tier.price}<span>/year</span>`
            : `$${tier.price}<span>/mo</span>`;
        const tierLabel = isYearly ? 'Yearly Premium' : `${tier.name} Subscription`;
        const accessDesc = isYearly ? '12 months of full premium access' : 'Full premium content access';

        container.innerHTML = `
            <h2>Order Summary</h2>
            <div class="order-item">
                <div>
                    <div class="order-item-name">${tierLabel}</div>
                    <div class="order-item-desc">${accessDesc}</div>
                </div>
                <div class="order-item-price">${priceDisplay}</div>
            </div>
            <ul class="features-list">
                ${tier.features.map(f => `<li>${f}</li>`).join('')}
            </ul>
            ${isYearly ? '<p style="color: #FFD700; text-align: center; margin-top: 1rem; font-size: 0.9rem;">&#11088; Save $60 vs monthly - one payment for 12 months!</p>' : ''}
        `;
    }

    function showError(message) {
        const errorEl = document.getElementById('errorMessage');
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }

    function hideError() {
        document.getElementById('errorMessage').style.display = 'none';
    }

    function showSuccess() {
        document.querySelector('.checkout-card').style.display = 'none';
        document.getElementById('successState').style.display = 'block';
        clearIntervals();
    }

    function showPartialPayment() {
        document.querySelector('.checkout-card').style.display = 'none';
        document.getElementById('partialState').style.display = 'block';
        clearIntervals();
    }

    function clearIntervals() {
        if (statusPollInterval) clearInterval(statusPollInterval);
        if (countdownInterval) clearInterval(countdownInterval);
    }

    function showCurrencySelector() {
        const container = document.getElementById('paymentContainer');
        const isYearly = selectedTier === 'yearly';
        const priceLabel = isYearly ? '/year' : '/mo';

        container.innerHTML = `
            <div class="crypto-payment">
                <p style="color: #fff; margin-bottom: 0.5rem; font-weight: 600;">Select Payment Currency</p>
                <p style="color: #888; margin-bottom: 1rem; font-size: 0.85rem;">
                    Choose your preferred cryptocurrency
                </p>
                <div class="currency-selector" id="currencySelector">
                    ${POPULAR_CURRENCIES.map(c => `
                        <button class="currency-btn" data-currency="${c.id}">
                            <span class="icon">${c.icon}</span>
                            <span class="name">${c.name}</span>
                        </button>
                    `).join('')}
                </div>
                <p id="cryptoEstimate" style="display: none; color: #0bd0f3; font-size: 1.1rem; margin: 0.5rem 0 1rem; font-family: monospace;"></p>
                <button id="createPaymentBtn" class="checkout-btn" disabled>
                    Select a currency to continue
                </button>
                <p style="color: #666; margin-top: 1rem; font-size: 0.8rem;">
                    Powered by NOWPayments
                </p>
            </div>
        `;

        let selectedCurrency = null;

        // Currency selection
        document.querySelectorAll('.currency-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                document.querySelectorAll('.currency-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedCurrency = btn.dataset.currency;
                const currencyName = POPULAR_CURRENCIES.find(c => c.id === selectedCurrency)?.name || selectedCurrency.toUpperCase();
                const payBtn = document.getElementById('createPaymentBtn');

                payBtn.disabled = true;
                payBtn.textContent = 'Checking minimum amount...';
                hideError();

                try {
                    const [minResponse, estimateResponse] = await Promise.all([
                        fetch(`/api/payments/min-amount/${selectedCurrency}`),
                        fetch(`/api/payments/estimate?amount=${tierData.price}&currency=${selectedCurrency}`)
                    ]);
                    const minData = await minResponse.json();
                    const estimateData = await estimateResponse.json();

                    if (minData.min_amount && tierData.price < minData.min_amount) {
                        showError(`Minimum payment for ${currencyName} is $${minData.min_amount.toFixed(2)} USD. Please choose a different currency or upgrade to a higher tier.`);
                        payBtn.textContent = `Below minimum ($${minData.min_amount.toFixed(2)})`;
                        payBtn.disabled = true;
                        return;
                    }

                    const estimateEl = document.getElementById('cryptoEstimate');
                    if (estimateData.estimated_amount) {
                        const symbol = POPULAR_CURRENCIES.find(c => c.id === selectedCurrency)?.icon || '';
                        estimateEl.innerHTML = `≈ <strong>${estimateData.estimated_amount}</strong> ${symbol} ${selectedCurrency.toUpperCase()}`;
                        estimateEl.style.display = 'block';
                    } else {
                        estimateEl.style.display = 'none';
                    }

                    payBtn.disabled = false;
                    payBtn.textContent = `Pay $${tierData.price}${priceLabel} with ${currencyName}`;
                } catch (err) {
                    document.getElementById('cryptoEstimate').style.display = 'none';
                    payBtn.disabled = false;
                    payBtn.textContent = `Pay $${tierData.price}${priceLabel} with ${currencyName}`;
                }
            });
        });

        document.getElementById('createPaymentBtn').addEventListener('click', async () => {
            if (!selectedCurrency) return;
            await createPayment(selectedCurrency);
        });
    }

    async function createPayment(currency) {
        const btn = document.getElementById('createPaymentBtn');
        btn.disabled = true;
        btn.textContent = 'Creating payment...';
        hideError();

        try {
            const response = await fetch('/api/payments/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                },
                body: JSON.stringify({
                    tier: selectedTier,
                    pay_currency: currency
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to create payment');
            }

            currentPayment = data;
            showPaymentDetails(data);

        } catch (err) {
            showError(err.message);
            btn.disabled = false;
            btn.textContent = 'Try Again';
        }
    }

    async function showPaymentDetails(payment) {
        const container = document.getElementById('paymentContainer');
        const currencyUpper = payment.pay_currency.toUpperCase();
        const needsMemo = MEMO_CURRENCIES.includes(payment.pay_currency.toLowerCase());

        container.innerHTML = `
            <div class="payment-details">
                <p style="color: #888; margin-bottom: 1rem;">Send exactly this amount:</p>
                <div class="pay-amount">
                    ${payment.pay_amount} <span class="currency">${currencyUpper}</span>
                </div>
                <p style="color: #666; font-size: 0.85rem;">= $${payment.price_amount} USD</p>
                ${payment.burning_percent || payment.network_precision ? `
                    <div style="background: rgba(255,255,255,0.05); border-radius: 6px; padding: 0.5rem; margin: 0.75rem 0; font-size: 0.8rem;">
                        <p style="color: #888; margin: 0;">
                            ${payment.burning_percent ? `<span style="color: #f39c12;">Network fee: ~${payment.burning_percent}%</span>` : ''}
                            ${payment.network_precision ? ` Precision: ${payment.network_precision} decimals` : ''}
                        </p>
                    </div>
                ` : ''}

                <div class="qr-container">
                    <canvas id="qrCode"></canvas>
                </div>

                <p style="color: #888; margin-bottom: 0.5rem; font-size: 0.85rem;">To this address:</p>
                <div class="pay-address" id="payAddress">${payment.pay_address}</div>
                <button class="copy-btn" id="copyAddressBtn">Copy Address</button>

                ${needsMemo && payment.payin_extra_id ? `
                    <div class="memo-warning">
                        <strong>IMPORTANT:</strong> You must include this memo/tag with your transaction:
                        <div class="pay-address" style="margin-top: 0.5rem;" id="payMemo">${payment.payin_extra_id}</div>
                        <button class="copy-btn" id="copyMemoBtn" style="background: #f39c12;">Copy Memo</button>
                    </div>
                ` : ''}

                ${payment.network ? `<p class="network-info">Network: ${payment.network}</p>` : ''}

                <div class="payment-status waiting" id="paymentStatus">
                    <div class="status-spinner"></div>
                    <span>Waiting for payment...</span>
                </div>

                <div class="countdown" id="countdown"></div>

                <button class="checkout-btn" style="margin-top: 1.5rem; background: rgba(255,255,255,0.1);" id="changeCurrencyBtn">
                    Change Currency
                </button>
            </div>
        `;

        const qrData = needsMemo && payment.payin_extra_id
            ? `${payment.pay_address}?amount=${payment.pay_amount}&memo=${payment.payin_extra_id}`
            : payment.pay_address;

        QRCode.toCanvas(document.getElementById('qrCode'), qrData, {
            width: 200,
            margin: 0,
            color: { dark: '#000000', light: '#ffffff' }
        });

        document.getElementById('copyAddressBtn').addEventListener('click', () => {
            copyToClipboard(payment.pay_address, 'copyAddressBtn', 'Address Copied!');
        });

        const memoBtn = document.getElementById('copyMemoBtn');
        if (memoBtn) {
            memoBtn.addEventListener('click', () => {
                copyToClipboard(payment.payin_extra_id, 'copyMemoBtn', 'Memo Copied!');
            });
        }

        document.getElementById('changeCurrencyBtn').addEventListener('click', () => {
            clearIntervals();
            showCurrencySelector();
        });

        startStatusPolling(payment.payment_id);

        if (payment.valid_until) {
            startCountdown(new Date(payment.valid_until));
        }
    }

    function copyToClipboard(text, btnId, successText) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById(btnId);
            const originalText = btn.textContent;
            btn.textContent = successText;
            btn.classList.add('copied');
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('copied');
            }, 2000);
        });
    }

    function startStatusPolling(paymentId) {
        const pollStatus = async () => {
            try {
                const response = await fetch(`/api/payments/${paymentId}`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                if (!response.ok) return;

                const data = await response.json();
                updatePaymentStatus(data.payment_status);

                if (data.payment_status === 'finished' || data.payment_status === 'confirmed') {
                    clearIntervals();
                    setTimeout(() => showSuccess(), 1500);
                } else if (data.payment_status === 'partially_paid') {
                    clearIntervals();
                    showPartialPayment();
                } else if (data.payment_status === 'expired' || data.payment_status === 'failed') {
                    clearIntervals();
                }
            } catch (err) {
                console.error('Status poll error:', err);
            }
        };

        pollStatus();
        statusPollInterval = setInterval(pollStatus, 10000);
    }

    function updatePaymentStatus(status) {
        const statusEl = document.getElementById('paymentStatus');
        if (!statusEl) return;

        const statusConfig = {
            waiting: { class: 'waiting', text: 'Waiting for payment...', spinner: true },
            confirming: { class: 'confirming', text: 'Payment detected, confirming...', spinner: true },
            confirmed: { class: 'confirmed', text: 'Payment confirmed!', spinner: false },
            finished: { class: 'finished', text: 'Payment complete!', spinner: false },
            partially_paid: { class: 'confirming', text: 'Partial payment received', spinner: false },
            expired: { class: 'expired', text: 'Payment expired', spinner: false },
            failed: { class: 'failed', text: 'Payment failed', spinner: false }
        };

        const config = statusConfig[status] || statusConfig.waiting;
        statusEl.className = `payment-status ${config.class}`;
        statusEl.innerHTML = `
            ${config.spinner ? '<div class="status-spinner"></div>' : ''}
            <span>${config.text}</span>
        `;
    }

    function startCountdown(expiryDate) {
        const countdownEl = document.getElementById('countdown');
        if (!countdownEl) return;

        const updateCountdown = () => {
            const now = new Date();
            const diff = expiryDate - now;

            if (diff <= 0) {
                countdownEl.textContent = 'Payment expired';
                countdownEl.classList.add('urgent');
                clearInterval(countdownInterval);
                return;
            }

            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            countdownEl.textContent = `Payment expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (minutes < 5) {
                countdownEl.classList.add('urgent');
            }
        };

        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);
    }

    init();
</script>
</body>
</html>
