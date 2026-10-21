# Security Policy

## Supported Versions

Security fixes are released for the latest minor release of Laravel CRM 2.x. Please upgrade to the latest release before reporting an issue, as it may already be fixed.

| Version         | Supported          |
| --------------- | ------------------ |
| 2.4.x (latest)  | :white_check_mark: |
| < 2.4           | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues, discussions or pull requests.**

Report them privately using one of the following:

- **GitHub:** [Report a vulnerability](https://github.com/venturedrake/laravel-crm/security/advisories/new) via the Security tab of this repository
- **Email:** [andrew@venturedrake.com](mailto:andrew@venturedrake.com)

Please include as much of the following as you can:

- The affected version(s) of Laravel CRM, and your Laravel and PHP versions
- The type of issue (e.g. SQL injection, XSS, authorization bypass, cross-team data access)
- Step-by-step instructions to reproduce it, ideally with a proof of concept
- The impact, and how an attacker might exploit it

## What to Expect

- **Acknowledgement** within 5 business days of your report
- **An initial assessment** (confirmed, needs more information, or declined) within 14 days
- **Progress updates** while a fix is being worked on
- **A patched release**, with a GitHub Security Advisory published once users have had a chance to upgrade

If the issue is confirmed, you'll be credited in the advisory and release notes unless you'd prefer to stay anonymous. If it's declined, we'll explain why.

Laravel CRM is maintained by a small team and does not currently offer a paid bug bounty.

## Scope

This policy covers the code in this repository (the `venturedrake/laravel-crm` package). Vulnerabilities in Laravel itself or in third-party dependencies should be reported to those projects directly, though we're happy to hear about them if they affect Laravel CRM in a specific way.
