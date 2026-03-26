# cPanel SSL Certificate Configuration Guide

**Last Updated:** 2026-03-26  
**Status:** ⚠️ Action Required - Verify CPANEL_HOST secret configuration

---

## SSL Certificate Information

### Current Certificate Details

The loungenie.com cPanel server has a **valid SSL certificate** installed:

- **Certificate ID:** www_loungenie_com_cb406_93fcd_1779162981_da21b6212bf1d43f8f14ede67348e49f
- **Issuer:** GlobalSign nv-sa (Trusted CA)
- **Key Type:** RSA, 2,048-bit
- **Expiration:** May 19, 2026 3:56:22 AM
- **IP Address:** 66.102.133.37

### Covered Hostnames

This certificate secures the following hostnames:

#### Primary Domains
- `loungenie.com`
- `www.loungenie.com`

#### cPanel/Service Subdomains
- `cpanel.loungenie.com` ⭐ **Use this for CPANEL_HOST**
- `cpcalendars.loungenie.com`
- `cpcontacts.loungenie.com`
- `mail.loungenie.com`
- `webdisk.loungenie.com`
- `webmail.loungenie.com`

#### Server Hostname Aliases
- `loungenie.com.poolsafeinc.com`
- `www.loungenie.com.poolsafeinc.com`

---

## GitHub Secret Configuration

### ⚠️ Critical: CPANEL_HOST Must Be a Hostname

The `CPANEL_HOST` GitHub secret **MUST** be set to one of the hostnames covered by the SSL certificate, **NOT** an IP address.

### ✅ Correct Configuration

```bash
# Use the cpanel subdomain:
gh secret set CPANEL_HOST --body "cpanel.loungenie.com"
```

### ❌ Incorrect Configuration

```bash
# DO NOT use the IP address:
gh secret set CPANEL_HOST --body "66.102.133.37"  # This will fail SSL verification!
```

---

## Why Hostname Matters for SSL

### How SSL Certificate Validation Works

1. **Client connects** to server using hostname or IP
2. **Server presents** SSL certificate
3. **Client checks** if the certificate covers the hostname being accessed
4. **If mismatch**: Connection fails with "SSL certificate problem" (curl exit code 60)

### Example

```yaml
# Certificate covers:
- cpanel.loungenie.com ✅
- loungenie.com ✅
- 66.102.133.37 ❌ (IP not in certificate)

# Connection attempts:
curl https://cpanel.loungenie.com:2083/... ✅ Works - hostname matches
curl https://66.102.133.37:2083/...       ❌ Fails - IP not in certificate
```

---

## Workflow Impact

### Affected Workflows

These workflows use the `CPANEL_HOST` secret and require proper hostname configuration:

1. **cpanel-pull-deploy.yml**
   - Triggers cPanel to pull from Git
   - Calls: `https://${CPANEL_HOST}:2083/execute/Git/update_repo`

2. **test-connections.yml**
   - Tests cPanel API connectivity
   - Calls: `https://${CPANEL_HOST}:2083/execute/Version/version`

### Error Symptoms

If `CPANEL_HOST` is set to an IP address, you'll see:

```
curl: (60) SSL certificate problem: unable to get local issuer certificate
```

Or workflow will fail with:
```
##[error]Process completed with exit code 60.
```

---

## Security Considerations

### Why We Don't Use `-k` Flag

Some guides suggest using `curl -k` or `--insecure` to bypass SSL verification. **We do NOT do this** because:

1. **Security Risk:** Bypassing SSL verification allows man-in-the-middle attacks
2. **Not Necessary:** The certificate is valid from a trusted CA (GlobalSign)
3. **Wrong Solution:** The proper fix is to use the correct hostname

### Best Practices

✅ **Do:**
- Use hostnames from the SSL certificate
- Maintain proper SSL certificate validation
- Update certificates before expiration (May 19, 2026)

❌ **Don't:**
- Use IP addresses in CPANEL_HOST
- Bypass SSL verification with `-k` flag
- Use hostnames not covered by the certificate

---

## Verification Steps

### 1. Check Current CPANEL_HOST Value

You cannot directly view secret values, but you can test if it's working:

```bash
# Run the test workflow:
gh workflow run test-connections.yml

# Check the results:
gh run list --workflow=test-connections.yml --limit 1
```

### 2. Test SSL Certificate

From your local machine:

```bash
# Test SSL handshake with correct hostname:
openssl s_client -connect cpanel.loungenie.com:2083 -servername cpanel.loungenie.com

# Should show:
# - Verification: OK
# - Issuer: GlobalSign nv-sa
```

### 3. Update Secret If Needed

If workflows are failing with exit code 60:

```bash
# Update to use the correct hostname:
gh secret set CPANEL_HOST --body "cpanel.loungenie.com"

# Verify by running test workflow:
gh workflow run test-connections.yml
```

---

## Certificate Renewal

### Current Expiration

- **Date:** May 19, 2026 3:56:22 AM
- **Days Remaining:** ~54 days (as of 2026-03-26)

### Renewal Process

1. **Monitor expiration** in cPanel SSL/TLS interface
2. **Renew certificate** through hosting provider or Let's Encrypt
3. **Update certificate** in cPanel before expiration
4. **No workflow changes needed** if hostname remains the same

### Automatic Renewal

If using AutoSSL or Let's Encrypt:
- Certificates renew automatically
- Usually 30-90 days before expiration
- Hostnames remain the same
- No workflow changes needed

---

## Troubleshooting

### Error: "SSL certificate problem"

**Symptom:**
```
curl: (60) SSL certificate problem: unable to get local issuer certificate
##[error]Process completed with exit code 60.
```

**Solution:**
1. Check if CPANEL_HOST is set to IP address → Change to hostname
2. Verify hostname is covered by certificate → Use cpanel.loungenie.com
3. Ensure certificate hasn't expired → Check expiration date

### Error: "Could not resolve host"

**Symptom:**
```
curl: (6) Could not resolve host: cpanel.loungenie.com
```

**Solution:**
1. Verify DNS is configured correctly
2. Check if hostname exists in DNS records
3. Try alternative hostname from certificate (e.g., loungenie.com)

### Workflow Succeeds But No Deployment

**Symptom:**
- Workflow completes without errors
- Changes not visible on staging site

**Solution:**
1. Check cPanel Git repository configuration
2. Verify repository tracking correct branch (main)
3. Check repository path: `/home/pools425/repositories/loungenie-stage`
4. Review cPanel response in workflow artifacts

---

## Additional Resources

- [cPanel API Documentation](https://api.docs.cpanel.net/)
- [SSL Certificate Best Practices](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [GIT_VERSION_CONTROL_STATUS.md](GIT_VERSION_CONTROL_STATUS.md) - Full workflow status
- [DEPLOYMENT_HEALTH_CHECK.md](DEPLOYMENT_HEALTH_CHECK.md) - Deployment health guide

---

## Quick Reference

```bash
# Recommended CPANEL_HOST value:
cpanel.loungenie.com

# Port:
2083

# Protocol:
HTTPS (with SSL verification enabled)

# Certificate Issuer:
GlobalSign nv-sa

# Expiration:
May 19, 2026
```

---

**Questions?** Open an issue or contact the repository maintainers.
