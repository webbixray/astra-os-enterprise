# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

Astra OS takes security seriously. We appreciate your efforts in responsibly discluting vulnerabilities.

### Process

1. **DO NOT** create a public GitHub issue for security vulnerabilities.
2. Email your report to **security@astraos.io** with:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Any suggested fixes (optional)

3. You will receive a response within **48 hours** confirming receipt.
4. We will provide a timeline for the fix and notify you when it's resolved.
5. Public disclosure will be coordinated after the fix is released.

### What to Include

- Type of issue (XSS, SQL injection, RCE, etc.)
- Full paths of source files related to the issue
- Location of affected source code (tag/branch/commit/direct URL)
- Any special configuration required to reproduce
- Step-by-step instructions to reproduce
- Proof-of-concept or exploit code (if possible)
- Impact of the issue (what an attacker might be able to do)

## Scope

### In Scope

- Astra OS core application
- API endpoints
- Authentication and authorization mechanisms
- Data handling and encryption

### Out of Scope

- Third-party dependencies (report to their respective maintainers)
- Infrastructure unrelated to Astra OS
- Physical security
- Social engineering attacks

## Security Best Practices

For developers working with Astra OS:

1. **Environment Variables**: Never commit secrets to version control. Use `.env` files or external secret management.
2. **API Keys**: Rotate API keys regularly. Use environment-specific keys.
3. **Database**: Use strong, unique passwords. Restrict network access to database servers.
4. **Dependencies**: Keep dependencies updated. Use `composer audit` regularly.
5. **Authentication**: Always use HTTPS. Implement rate limiting on auth endpoints.
6. **Input Validation**: Validate all input on the server side. Never trust client-side validation alone.
7. **Encryption**: Encrypt sensitive data at rest and in transit.

## Disclosure Policy

- We will acknowledge receipt of vulnerability reports within 48 hours.
- We will provide a timeline for resolution.
- We will notify reporters when the vulnerability is fixed.
- We will publicly acknowledge security researchers (with permission).

## Recognition

We maintain a Security Hall of Fame for researchers who responsibly disclose vulnerabilities. Contributors will be recognized in our release notes (with permission).

---

**Contact**: security@astraos.io  
**Last Updated**: July 2026
