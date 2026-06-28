# Security Policy

## Supported Versions

Currently supported versions with security updates:

| Version | Supported          | Security Updates |
|---------|---------------------|------------------|
| 1.x.x  | :white_check_mark: | Yes              |
| < 1.0   | :x:                 | No               |

## Reporting a Vulnerability

**Do NOT** report security vulnerabilities through public issues.

### How to Report

Send an email to: **security@example.com**

Include:
- Vulnerability description
- Steps to reproduce
- Potential impact
- Proposed fix (if known)

### Response Timeline

- **Initial response:** Within 48 hours
- **Investigation:** Within 7 days
- **Fix release:** As soon as feasible, based on severity

### What to Expect

1. Acknowledgment of receipt (within 48 hours)
2. Request for additional information (if needed)
3. Confirmation of vulnerability
4. Estimated timeline for fix
5. Notification when fix is released

---

## Security Best Practices

### For Bot Developers

1. **Environment Variables**
   - Never commit `.env` file
   - Use strong bot tokens
   - Rotate tokens regularly

2. **Webhooks**
   - Use HTTPS only
   - Validate secret tokens
   - Verify request source

3. **User Input**
   - Sanitize all user input
   - Validate callback data
   - Escape output appropriately

4. **API Keys**
   - Store securely in environment
   - Never expose in logs
   - Use `.gitignore` for sensitive files

### Example Secure Webhook

```php
// Validate webhook secret
$secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if ($secret !== 'your-secret-token') {
    http_response_code(403);
    exit('Forbidden');
}

// Validate signature (if implemented)
// Process update
$update = $bot->getWebhookUpdates();
```

---

## Security Features in Framework

### Built-in Protections

- **Type Safety:** Strict types prevent type coercion vulnerabilities
- **Input Validation:** Auto-escaping prevents injection attacks
- **Error Handling:** Structured exceptions prevent information leakage
- **SSL Configuration:** Configurable verification for different environments

### Recommended Configurations

**Production:**
```php
$config = new BotConfig(
    token: getenv('TELEGRAM_BOT_TOKEN'),
    throwExceptions: true,
    loggingEnabled: true,
    logLevel: 'WARNING'
);
```

**Development:**
```php
$config = new BotConfig(
    token: getenv('TELEGRAM_BOT_TOKEN'),
    throwExceptions: true,
    loggingEnabled: true,
    logLevel: 'DEBUG'
);
```

---

## Vulnerability Disclosure

### Disclosure Process

1. **Private Report:** Vulnerability reported privately
2. **Investigation:** Team investigates and validates
3. **Fix Development:** Fix is developed and tested
4. **Release:** Security update released
5. **Public Disclosure:** Details published after fix release

### Coordination

We coordinate disclosure with:
- Timeline expectations
- Credit to reporter
- Publication of details

---

## Security Audits

### Past Audits

- **Date:** TBD
- **Scope:** Core framework
- **Results:** Will be published after first audit

### Future Audits

Scheduled security audits will be announced in advance.

---

## Receiving Security Updates

### Monitoring Updates

Watch this repository for security advisories:
- Star the repository
- Watch releases
- Subscribe to security advisory emails

### Update Process

```bash
# Check current version
composer show ahmcho/telegram

# Update to latest version
composer update ahmcho/telegram

# Review CHANGELOG.md for security fixes
```

---

## Security Contacts

- **Security Issues:** security@example.com
- **General Questions:** Open a GitHub issue
- **Security Research:** See RESEARCH.md

---

## Related Policies

- [Privacy Policy](PRIVACY.md)
- [Terms of Service](TERMS.md)
- [Bug Bounty Program](BUG_BOUNTY.md)

---

## Credits

Security researchers who responsibly disclose vulnerabilities will be credited in release notes, unless they wish to remain anonymous.

Thank you for helping keep AhmCho\Telegram secure! 🔒
