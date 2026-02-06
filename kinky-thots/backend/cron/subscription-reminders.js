#!/usr/bin/env node
/**
 * Subscription Expiring Reminder Cron Job
 * Sends email reminders to users whose subscriptions expire in 3 or 7 days
 * Run daily via cron: 0 9 * * * docker exec kinky-backend node /app/cron/subscription-reminders.js
 */

// Use app's node_modules when running from Docker
const path = require('path');
const appDir = process.env.NODE_ENV === 'production' ? '/app' : path.join(__dirname, '..');
process.chdir(appDir);

// Load env from container environment (already set in docker-compose)
const mariadb = require('mariadb');
const nodemailer = require('nodemailer');

const SITE_NAME = 'Kinky Thots';
const SITE_URL = process.env.SITE_URL || 'https://kinky-thots.xxx';

// Database pool
const pool = mariadb.createPool({
  host: process.env.MARIADB_HOST || 'db',
  user: process.env.MARIADB_USER,
  password: process.env.MARIADB_PASSWORD,
  database: process.env.MARIADB_DATABASE,
  connectionLimit: 5
});

// Email transporter
const emailTransporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: parseInt(process.env.SMTP_PORT) || 587,
  secure: false,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS
  },
  tls: { rejectUnauthorized: false }
});

const SMTP_FROM = `"${process.env.SMTP_FROM_NAME || SITE_NAME}" <${process.env.SMTP_FROM_EMAIL || process.env.SMTP_USER}>`;

/**
 * Send expiring subscription reminder email
 */
async function sendExpiringEmail(user, daysUntilExpiry, expiresAt) {
  const urgency = daysUntilExpiry <= 3 ? 'urgent' : 'reminder';
  const subject = daysUntilExpiry <= 1
    ? `⚠️ Your subscription expires TOMORROW - ${SITE_NAME}`
    : `Your subscription expires in ${daysUntilExpiry} days - ${SITE_NAME}`;

  const html = `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
      <h2 style="color: ${urgency === 'urgent' ? '#e74c3c' : '#f805a7'}; margin-bottom: 20px;">
        ${urgency === 'urgent' ? '⚠️ ' : ''}Subscription Expiring Soon
      </h2>
      <p style="color: #fff;">Hi ${user.username},</p>
      <p style="color: #ccc;">Your <strong style="color: #0bd0f3;">${user.subscription_tier}</strong> subscription will expire on <strong style="color: #f805a7;">${expiresAt.toLocaleDateString()}</strong>.</p>
      ${daysUntilExpiry <= 1
        ? `<p style="color: #e74c3c; font-weight: bold;">That's tomorrow! Don't lose access to premium content.</p>`
        : `<p style="color: #ccc;">Renew now to keep your access to all premium content!</p>`
      }
      <p style="text-align: center; margin: 30px 0;">
        <a href="${SITE_URL}/subscriptions.html" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 14px 35px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">Renew Now</a>
      </p>
      <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p style="color: #888; font-size: 14px; margin: 0;">After expiration, you'll lose access to:</p>
        <ul style="color: #ccc; font-size: 14px; margin: 10px 0;">
          <li>Extended videos (1-5 minutes)</li>
          <li>Full-length premium content (5+ minutes)</li>
          <li>Premium chat badge</li>
        </ul>
      </div>
      <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
      <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
      <p style="color: #555; font-size: 11px;">You're receiving this because your subscription is expiring. <a href="${SITE_URL}/profile.html" style="color: #0bd0f3;">Manage preferences</a></p>
    </div>
  `;

  try {
    await emailTransporter.sendMail({
      from: SMTP_FROM,
      to: user.email,
      subject,
      html
    });
    console.log(`✓ Sent ${daysUntilExpiry}-day reminder to ${user.email}`);
    return true;
  } catch (err) {
    console.error(`✗ Failed to send reminder to ${user.email}:`, err.message);
    return false;
  }
}

/**
 * Main cron job function
 */
async function runReminders() {
  console.log(`\n=== Subscription Reminder Job Started: ${new Date().toISOString()} ===\n`);

  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.error('Email not configured - SMTP_USER and SMTP_PASS required');
    process.exit(1);
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Find users with subscriptions expiring in exactly 7 days or 3 days or 1 day
    // Exclude lifetime (subscription_expires_at far in future or null) and already cancelled
    const query = `
      SELECT id, username, email, subscription_tier, subscription_expires_at
      FROM users
      WHERE subscription_status = 'active'
        AND subscription_tier IN ('plus', 'premium')
        AND subscription_expires_at IS NOT NULL
        AND (
          DATE(subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          OR DATE(subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
          OR DATE(subscription_expires_at) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        )
    `;

    const users = await conn.query(query);
    console.log(`Found ${users.length} users with expiring subscriptions\n`);

    let sent = 0;
    let failed = 0;

    for (const user of users) {
      const expiresAt = new Date(user.subscription_expires_at);
      const now = new Date();
      const daysUntilExpiry = Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24));

      console.log(`Processing: ${user.username} (${user.email}) - expires in ${daysUntilExpiry} days`);

      const success = await sendExpiringEmail(user, daysUntilExpiry, expiresAt);
      if (success) sent++;
      else failed++;

      // Small delay between emails to avoid rate limiting
      await new Promise(resolve => setTimeout(resolve, 500));
    }

    console.log(`\n=== Job Complete ===`);
    console.log(`Sent: ${sent}, Failed: ${failed}, Total: ${users.length}`);

  } catch (err) {
    console.error('Cron job error:', err);
    process.exit(1);
  } finally {
    if (conn) conn.release();
    await pool.end();
  }
}

// Run the job
runReminders().then(() => process.exit(0)).catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
