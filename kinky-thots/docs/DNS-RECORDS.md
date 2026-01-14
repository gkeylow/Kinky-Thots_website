# DNS Records for Kinky-Thots Domains

> **Linode IP:** `45.33.100.131`
>
> **Linode Nameservers:**
> - ns1.linode.com
> - ns2.linode.com
> - ns3.linode.com
> - ns4.linode.com
> - ns5.linode.com

---

## kinky-thots.com

### A Records

| Name | Value | TTL |
|------|-------|-----|
| @ | 45.33.100.131 | 300 |
| www | 45.33.100.131 | 300 |
| mail | 45.33.100.131 | 300 |

### MX Record

| Name | Value | Priority | TTL |
|------|-------|----------|-----|
| @ | mail.kinky-thots.com | 10 | 300 |

### TXT Records

| Name | Value | TTL |
|------|-------|-----|
| @ | `v=spf1 mx a ip4:45.33.100.131 ~all` | 300 |
| _dmarc | `v=DMARC1; p=quarantine; rua=mailto:admin@kinky-thots.com` | 300 |
| mail._domainkey | (see DKIM below) | 300 |

### DKIM Record (mail._domainkey)

```
v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxdo+KNAR6N3tM9W1RYo9UX6UnNISuuFo82QYx6T+kdyRz7YmbtJiVuST4bOewxS+U1rgZb/0K8lMXP/+a+XVrjC2KNvmHgqh7Qjs+YyeiQJdttxmRaF1elU2ksDtbgFzOdwR41qysMxXF4hvlX9RY8L1UxqK9ddc7m9TpmHVbsRgB6h2P9Bx1S/LwDCp9Cqt2xXvyBnZ+HSO3NvOAD+khzPfimyU3dmpQq6FFtHVprrh/W+PBIfMAb6aKrbRqxdmKMdrDoRtH7NMSjQo4ebb18phzLRonXrw9iTlhZ9HS9hxCWrsmLj9AiJAzWf2lFy2f2MyQxREgL9ajThOWj6G2QIDAQAB
```

### SRV Records (Mail Autodiscover)

| Name | Priority | Weight | Port | Target | TTL |
|------|----------|--------|------|--------|-----|
| _autodiscover._tcp | 0 | 0 | 443 | mail.kinky-thots.com | 300 |
| _submission._tcp | 0 | 0 | 587 | mail.kinky-thots.com | 300 |
| _imaps._tcp | 0 | 0 | 993 | mail.kinky-thots.com | 300 |

---

## kinky-thots.xxx

### A Records

| Name | Value | TTL |
|------|-------|-----|
| @ | 45.33.100.131 | 300 |
| www | 45.33.100.131 | 300 |

---

## Architecture Overview

```
Internet
    │
    ▼
┌─────────────────────────────┐
│  Linode (45.33.100.131)     │
│  - nginx reverse proxy      │
│  - WireGuard server         │
│  - Mail server              │
│  - SSL termination          │
└─────────────┬───────────────┘
              │ WireGuard VPN
              │ (10.100.0.0/24)
              ▼
┌─────────────────────────────┐
│  Home Server (10.100.0.2)   │
│  - Docker containers        │
│  - Apache/PHP               │
│  - Node.js backend          │
│  - RTMP streaming           │
└─────────────────────────────┘
```

## WireGuard VPN Details

| Host | VPN IP | Public IP |
|------|--------|-----------|
| Linode (server) | 10.100.0.1 | 45.33.100.131 |
| Home Server (client) | 10.100.0.2 | (behind CG-NAT) |

---

*Created: January 13, 2026*
