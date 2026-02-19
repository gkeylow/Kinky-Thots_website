# Mail Server Testing Instructions

## Server Details
- **Hostname**: mail.kinky-thots.com
- **Container**: docker-mailserver (in `/opt/mailserver`)
- **Email accounts**:
  - admin@kinky-thots.com
  - sissylonglegs@kinky-thots.com

## Testing Tasks

### 1. Check Container Status
```bash
cd /opt/mailserver
docker ps
docker logs mailserver --tail 50
```

### 2. Verify Email Accounts
```bash
docker exec mailserver setup email list
```

### 3. Test SMTP (Port 25/587)
```bash
# Check if ports are listening
ss -tlnp | grep -E '25|465|587|993'

# Test local SMTP
docker exec mailserver swaks --to admin@kinky-thots.com --from test@localhost --server localhost
```

### 4. Check DNS Records
```bash
# Get server IP
curl -s ifconfig.me

# Test MX record
dig MX kinky-thots.com +short

# Test A record for mail subdomain
dig A mail.kinky-thots.com +short
```

### 5. View DKIM Key (for DNS setup)
```bash
cat /opt/mailserver/config/opendkim/keys/kinky-thots.com/mail.txt
```

### 6. Test Sending Email
```bash
# Send test email (requires external recipient)
docker exec mailserver swaks \
  --to YOUR_EXTERNAL_EMAIL@gmail.com \
  --from admin@kinky-thots.com \
  --server localhost \
  --auth-user admin@kinky-thots.com \
  --auth-password <see config/.credentials.md>
```

### 7. Check Mail Queue
```bash
docker exec mailserver mailq
```

### 8. View Mail Logs
```bash
docker exec mailserver cat /var/log/mail/mail.log | tail -100
```

## Expected Results
- Container should be running and healthy
- 2 email accounts should be listed
- Ports 25, 465, 587, 993 should be listening
- DNS should show MX record pointing to mail.kinky-thots.com
- DKIM key should exist for DNS TXT record

## Common Issues
- If container not running: `docker compose up -d`
- If emails bouncing: Check DKIM/SPF/DMARC DNS records
- If connection refused: Check firewall `ufw status`

## Report Back
After running these tests, report:
1. Container status (running/stopped)
2. Number of email accounts found
3. Which ports are listening
4. DNS record status (MX, A records)
5. Any errors in logs
6. Whether test email sent successfully
