# Mail Server Compromise — Incident Report

**Date of Incident:** Wednesday, February 4, 2026
**Date Discovered:** Wednesday, February 5, 2026
**Date Resolved:** Wednesday, February 5, 2026
**Severity:** Critical

---

## Summary

The mail server (`mailserver` container on Linode 45.79.208.9) was compromised via stolen SMTP credentials for `admin@kinky-thots.com`. The attacker used the account to relay a massive French-language phishing campaign impersonating ANTAI (Agence Nationale de Traitement Automatisé des Infractions — the French traffic fine authority).

---

## Attack Timeline

| Time (UTC) | Event |
|------------|-------|
| Feb 4, 08:00 | First authenticated session from `172.59.116.58` (sasl_method=PLAIN) |
| Feb 4, 10:01 | Bulk sending begins from `66.102.132.192` (`sh-cp1-au.yyz2.servername.online`, Toronto) |
| Feb 4, 17:44 | Last authenticated session on Feb 4 |
| Feb 5, 02:13–10:01 | 5 more sessions from attacker (continuing next day) |
| Feb 5, ~19:35 | **Password changed, attacker IP blocked, mail queue flushed** |

**Total attack duration:** ~34 hours (Feb 4 08:00 — Feb 5 19:35 UTC)

---

## Attack Volume

### Authenticated Sessions
| Date | Sessions |
|------|----------|
| Feb 4 | 345 |
| Feb 5 (before lockdown) | 5 |
| **Total** | **350** |

### Email Volume (Feb 4 — the main attack day)
| Metric | Count |
|--------|------:|
| Unique recipients targeted | 16,146 |
| Successfully delivered | 23,412 |
| Bounced (user doesn't exist) | 11,051 |
| Deferred (queued/throttled) | 90,942 |
| **Total delivery attempts** | **125,405** |

### Feb 5 (before lockdown)
| Metric | Count |
|--------|------:|
| Sent | 11 |
| Deferred | 33 |
| Bounced | 5 |

### Target Domains
| Domain | Delivery Attempts |
|--------|------------------:|
| hotmail.fr | 87,890 |
| hotmail.com | 18,145 |
| live.fr | 14,382 |
| msn.com | 3,065 |
| kinky-thots.com (bounce-backs) | 1,254 |
| outlook.fr | 545 |
| outlook.com | 154 |
| hotmail.co.uk | 84 |
| live.com | 83 |
| hotmail.it | 49 |
| hotmail.es | 28 |
| msn.fr | 25 |
| hotmail.de | 15 |
| Others | ~16 |

All targets were Microsoft-hosted email services. The campaign was specifically aimed at French-speaking users.

---

## Attacker Infrastructure

| IP Address | Hostname | Role | Sessions |
|------------|----------|------|----------|
| 66.102.132.192 | sh-cp1-au.yyz2.servername.online | Primary bulk mailer (Toronto, CA) | 345 |
| 172.59.116.58 | (unknown) | Initial access / testing | 2 |
| 54.212.131.181 | ec2-54-212-131-181.us-west-2.compute.amazonaws.com | AWS relay (Oregon) | 1 |
| 84.239.48.9 | (unknown) | Single session | 1 |
| 151.240.205.75 | (unknown) | Single session | 1 |

**Authentication methods used:** PLAIN, LOGIN
**Compromised account:** `admin@kinky-thots.com`
**Compromised password:** `REDACTED_OLD_MAIL_PASSWORD` (the same password listed in CLAUDE.md for the mail webui)

---

## Phishing Campaign Details

The spam was a French government impersonation phishing attack:

- **Impersonated:** ANTAI (Agence Nationale de Traitement Automatisé des Infractions)
- **Lure:** Unpaid traffic fine of €135.00, threatening increase to €675.99 and license point deduction
- **From name:** "ANTAI" with spoofed sender `antai_amendes_gouv@kinky-thots.com`
- **Phishing URLs:**
  - `https://www.ibraitv.com.br//dd/UIU/antai/redirect` (Brazilian compromised site)
  - `https://app-68404442c1ac1808a47f1ce3.closte.com/wp-content/SJ/antai/redirect.php` (Closte hosting)
- **Language:** French
- **DKIM signed:** Yes — emails passed DKIM validation using our `mail` selector, making them appear legitimate

---

## Sample Spam Email (Full Headers + Body)

Below is a complete copy of one successfully delivered phishing email, as received in the admin inbox:

```
Return-Path: <admin@kinky-thots.com>
Delivered-To: admin@kinky-thots.com
Received: from mail.kinky-thots.com
	by mail.kinky-thots.com with LMTP
	id cN0ODT5Ig2ncGREAHj8HjA
	(envelope-from <admin@kinky-thots.com>)
	for <admin@kinky-thots.com>; Wed, 04 Feb 2026 13:23:10 +0000
Received: from localhost (localhost [127.0.0.1])
	by mail.kinky-thots.com (Postfix) with ESMTP id 2ECD74EDDA;
	Wed,  4 Feb 2026 13:23:10 +0000 (UTC)
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=kinky-thots.com;
	s=mail; t=1770211389;
	bh=5pfRpTHh8deO1q2BqaXgPQgzoeJCav8Lx7ZWPQCSOWE=;
	h=To:From:Reply-To:Subject;
	b=Jlg+v/XNEJV6exu7kp1B4cnyoaAKLx1ZJs1HSzxFc8yprpU94fyWKGo5EYCRMwVvW
	 6ZlgOEEkgy2Pma5wmA/b1ho5iWDUBZUBUbR+VTZbg7y+VclW+DrRy4qdroOIjZXH/x
	 L2xf3vCG11mYi4U5JSou6fAoeLEnw1Y9553MYBd9zuPJ2ikcERojYnqUJ1z8yRKywq
	 I4CELrXa5kJ22wtlMatvL4EmR9AS7WPmbGoL1E6vuvBs7Uq0pWkQAXpEw0p9u2DTNu
	 +e7b3fCnwlrJc6ljtKQsR23+NYiiTGwc43kzPY9prvWsxmx7+N1gnS32fsRS6Jy9eY
	 FDAUbgsRP1mJg==
Date: Wed, 4 Feb 2026 13:23:06 +0000
To: =?UTF-8?B?YW50YWk=?= <admin@kinky-thots.com>
From: =?UTF-8?B?QU5UQUk=?= <antai_amendes_gouv@kinky-thots.com>
Reply-To: =?UTF-8?B?QU5UQUk=?= <admin@kinky-thots.com>
Subject: =?UTF-8?B?QW1lbmRlIGltcGF5w6llIDogUsOpZ3VsYXJpc2F0aW9uIGltbcOpZGlhdGUgbsOpY2Vzc2FpcmUgLSA5MjQzNzIwMzQ1?=
Message-ID: <9mzwzfkn5jmaamnm2hje@gmail.com>
X-Priority: 3
Auto-Submitted: auto-generated
X-Abuse: Please report abuse here <mailto:abuse@gmail.com?c=1179858065>
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="b1=_MMwtDz6PPSbelVzAbfZZvPpbYZKk0Go59zc2ITdsFM"
```

**Decoded Subject:** `Amende impayée : Régularisation immédiate nécessaire - 9243720345`
("Unpaid fine: Immediate payment required")

**Decoded From name:** `ANTAI`

**Plain text body:**
```
Bonjour,

Nous vous informons que votre amende de 135,00 €, émise par l'Agence Nationale
de Traitement Automatisé des Infractions (ANTAI), est toujours en attente de
paiement.

Le montant actuellement dû s'élève à 292,99 €. Nous vous demandons d'effectuer
le règlement dans un délai de 12 heures à compter de la réception de ce message.

Attention : Passé ce délai, la somme sera majorée à 675,99 € et des poursuites
judiciaires pourront être engagées, pouvant entraîner un retrait de 3 points sur
votre permis de conduire.

Veuillez procéder au paiement en cliquant sur le bouton ci-dessous :

PAYER MON AMENDE

Conditions de paiement : En payant immédiatement, vous évitez toute majoration et
pouvez bénéficier d'une restitution sous 12 heures pour excès de paiement.

Pour toute question, notre support client est disponible du lundi au vendredi de
8h30 à 17h30.

Ce message est généré automatiquement.

ANTAI - CS 20055 - 44046 Nantes Cedex 1 - France

© 2026 ANTAI - Tous droits réservés
```

**Phishing link in HTML body:**
- Visible text: `PAYER MON AMENDE` ("PAY MY FINE")
- Actual href: `https://www.ibraitv.com.br//dd/UIU/antai/redirect`
- Title attribute: `https://app-68404442c1ac1808a47f1ce3.closte.com/wp-content/SJ/antai/redirect.php`

---

## Remediation Actions Taken

| # | Action | Time (UTC) |
|---|--------|------------|
| 1 | Changed `admin@kinky-thots.com` password | Feb 5, 19:35 |
| 2 | Blocked attacker IP `66.102.132.192` via iptables (persisted) | Feb 5, 19:35 |
| 3 | Flushed mail queue (2 stuck outbound spam messages deleted) | Feb 5, 19:35 |
| 4 | Updated backend `.env` with new SMTP password | Feb 5, 19:38 |
| 5 | Recreated backend container to pick up new credentials | Feb 5, 19:39 |
| 6 | Banned `66.102.132.192` in fail2ban custom jail (180-day ban) | Feb 5, 19:40 |
| 7 | Tightened fail2ban: maxretry 6→3, findtime 1w→10min | Feb 5, 19:45 |
| 8 | Added Postfix rate limits: 30 msg/min, 50 recipients/msg, 100 conn/min | Feb 5, 19:45 |
| 9 | Checked blacklists — IP 45.79.208.9 **not blacklisted** (as of Feb 5) | Feb 5, 19:38 |

### New Security Configuration

**Fail2ban:**
| Setting | Before | After |
|---------|--------|-------|
| Max retries before ban | 6 | 3 |
| Find time window | 1 week | 10 minutes |
| Ban duration | 1 week | 1 week |
| Attacker 66.102.132.192 | — | Banned 180 days |

**Postfix rate limiting (new):**
| Limit | Value |
|-------|-------|
| Messages per client per minute | 30 |
| Recipients per message | 50 |
| Connections per client per minute | 100 |

**Config files saved:**
- `/opt/mailserver/config/fail2ban-jail.cf`
- `/opt/mailserver/config/postfix-main.cf`

---

## Root Cause

The `admin@kinky-thots.com` password (`REDACTED_OLD_MAIL_PASSWORD`) was compromised. This password was:
- Used for the mail webui login
- Used as the SMTP relay password for the backend application
- Documented in plaintext in `CLAUDE.md` (project documentation)

The password was either:
1. Harvested from a public repository or leaked documentation
2. Brute-forced (unlikely given fail2ban was active with 6-retry threshold)
3. Obtained through credential stuffing

---

## Lessons Learned & Recommendations

1. **Never document credentials in version-controlled files** — use `.credentials.md` (gitignored) or a secrets manager
2. **Use strong, unique passwords** for mail accounts — the compromised password was relatively weak
3. **Monitor mail logs regularly** — 125K delivery attempts went unnoticed for ~34 hours
4. **Rate limiting is essential** — even with valid credentials, rate limits would have capped damage to ~30 messages/minute instead of thousands
5. **Monitor blacklists** over the next 2 weeks at [multirbl.valli.org](https://multirbl.valli.org/lookup/45.79.208.9.html) — some blacklists update slowly
6. **Consider DMARC policy** — set `p=reject` to prevent spoofed From addresses like `antai_amendes_gouv@kinky-thots.com`
7. **Set up log alerts** — automated alerting on unusual mail volume would catch this faster
