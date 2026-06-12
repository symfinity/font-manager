# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 0.2.x   | Yes       |
| 0.1.x   | No        |

## Reporting a Vulnerability

If you discover a security vulnerability, **do not** open a public issue. Email **dev@symfinity.net** with:

- Type of vulnerability
- Full paths of source file(s) related to the issue
- The location of the affected code (tag, branch, commit, or URL)
- Step-by-step reproduction instructions
- Proof-of-concept or exploit code (if possible)
- Impact and plausible attack scenario

We aim to acknowledge within 48 hours and provide a detailed response within 7 days.

## Security best practices

When using this bundle:

1. Keep Symfony and other dependencies updated
2. Use HTTPS for font delivery in production
3. Lock fonts locally in production (`php bin/console fonts:lock`)
4. Review locked font manifests regularly
5. Prefer privacy-friendly providers (Bunny Fonts, Fontsource, local fonts) when GDPR matters

## Security contact

**dev@symfinity.net**
