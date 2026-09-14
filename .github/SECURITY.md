# Security Policy

## Supported Versions

We provide security updates only for the latest tagged version of the software (see [Tags](https://github.com/akeeba/release-system/tags) on the GitHub repo).

## Reporting a Vulnerability

**Do NOT report security vulnerabilities through public GitHub issues.**

Use one of these private channels:

### GitHub Security Advisories (Preferred)

1. Go to the [Security tab](https://github.com/akeeba/release-system/security/advisories)
2. Click "Report a vulnerability"
3. Fill out the advisory form

This creates a private discussion visible only to maintainers.

### Email

- Encrypt your plain text message with our [publicly verifiable GPG key](https://keybase.io/nikosdion).
- Go to our [Contact Us](https://www.akeeba.com/contact-us.html) page.
- Choose the "Pre-sales Request"
- Type in your name, and email. Double check these are correct.
- Type in your subject, prefixing it with `[SECURITY]`. This also helps in case your message ends in my spam inbox. 
- Paste your encrypted (ASCII-armoured) message in the Message Text field.
- Select the checkbox about giving your consent to processing your personal information (GDPR requirement).
- Interact with the CAPTCHA to verify it's not a bot submission.
- Click on "Send Your Message"

Kindly note that this method should not be abused for non-security content. If I receive non-security content in an encrypted message and/or a message whose subject has the `[SECURITY]` tag in it, I will not respond. Moreover, I will block that email address from ever communicating with me in the future. I take security seriously. Trying to exploit that to "jump the queue" is guaranteed to make me completely inaccessible to you forever. 

## Response Timeline

Our usual response timeline is as follows:

- **Initial response**: Within two business days
- **Status update**: Within 7 business days
- **Resolution target**: Within 90 days (varies by severity; we aim for a single-digit number of days whenever practical)

Kindly note that this is a tiny, single-person company. There is no multi-person security or development team behind the product. Life can get in the way of responding to you: participating in conferences, illness, holidays, offline commitments, etc. While I try to stick to the aforementioned response timeline, if I'm not available, it _will_ take longer. Unless I am deathly ill or somewhere with completely unreliable (or not available at all) Internet connectivity, I will try to give you an initial response within a single-digit number of days, letting you know if I need more time to get back to you with a status update. Please _do not_ assume I am ghosting you.

Likewise, I expect security researchers to be humans, too. If I propose a resolution target which is too soon for you, please, do not hesitate to let me know. I would rather wait before releasing a security update than rush you beyond what you feel comfortable with. 

## Disclosure Policy

- **Coordinated disclosure**: We work with reporters to agree on disclosure timeline
- **Credit**: Security reporters are credited in release notes and advisories (unless they prefer anonymity)
- **CVE assignment**: We request CVEs for verified vulnerabilities, as long as they are submitted through GitHub.

## Security Measures

This project employs:

- **Dependency scanning**: [Dependabot] for automated updates.
- **AI code auditing**: We use AI code assistants with purpose-built prompts to scan our software for security issues.

## Bug Bounty

We do not offer a bug bounty programme, nor are we likely to in the future. Security reports are greatly appreciated and credited.

As noted above, this is a single-person company. We simply do not have the resources – financial and manpower – to consider a bug bounty programme.

## AI-generated security reports

We understand and accept that AI code assistants are invaluable tools in detecting, documenting, and reporting security issues. As noted above, we use the same tools ourselves.

That said, the responsiblity of what you submit and how you interact with us lies solely on you, the human operator. We, therefore, ask you to take into account the following.

**Are you willing to work with us on a technical level?** If we ask a technical question, and you respond with something like "my AI code assistant told me to submit this, I can't answer any questions", you have merely wasted both our time.

**Please mark it with an `[AI]` flag.** This will NOT get your report deprioritized. It will, however, provide useful context to us when replying back to you. We know the typical mistakes AI does. Knowing the report was AI-generated will allow us to see past those mistakes and reply appropriately.

## Guidelines for security reports

Before submitting a security issue, please consider whether it really is a security issue. Here are some guidelines to help you evaluate your issue the same way we would.

**Is it appropriate for the intended use case?** For example, a report that data at rest is stored unencrypted is invalid on the basis that this software runs inside Joomla, on commodity hosting, making encrypted data at rest impractical and outside the scope of the intended use case.

**Is it reproducible?** If you cannot provide instructions to reproduce the issue, the issue cannot be evaluated. Having a reproducible issue is a prerequisite to it being a bug, which is itself a prerequisite for it being a security issue.

**Does it pertain to a feature Joomla does not (yet) have?** For example, extension updates are only verified against a checksum (e.g. SHA-1) they publish in their update site – the same place the download URL for the installable package is found. A MITM attack between the site and the update site can indeed allow an attacker to install arbitrary code on the site. This is not a security issue on our side as Joomla does not offer a better solution at this time. Reporting this to us will result in a shrug and "yeah, we know, but the solution is not up to us at this moment".

**Does it require nonsensical configuration or deliberate misconfiguration?** An issue which requires purposefully misconfiguring the host, site, or software is not a security issue. For example, if you give the Guest user elevated privileges for the software, anyone visiting the site will be able to take administrative actions with security repercussions without authenticating. This is not a security issue; it's a configuration issue. You explicitly asked Joomla and our software to do something nonsensical; they did exactly what you asked for. Software is like a genie: it does _exactly_ what you ask it to do, not what you _meant_ to ask it to do.

**Does it devolve to "I can hack myself"?** If the source and target of the attack is the same user, it's not a security issue. I can launch an XSS against myself and myself only is not a vulnerability. Do note that this _does not_ apply to CSRF issues since the source of the attack is NOT the same user, it's a page under the control of the attacker.

**Does it devolve to "Trusted users, such as Super Users or users explicitly given elevated permissions, can hack the site"?** The nature of trusted users in Joomla is such that they have full control of the site. A malicious user with elevated privileges can indeed harm the site, by definition. Preventing that requires a read-only site which is NOT Joomla's use case. Since our software runs inside Joomla, a read-only site is not our use case either.

**Does it require filesystem access?** If you need to modify a file to trigger the alleged vulnerability, it's not a security issue. An attacker with filesystem access has already hacked your site.

**Does it require command line access?** If you need access to the command line (CLI) to trigger the alleged vulnerability, it's not a security issue. An attacker with CLI access has already hacked your site. Do keep in mind that Joomla's security model DOES NOT include access control for CLI commands. Anyone with CLI access is a _de facto_ Super User. 

**Is it exploitable?** Can you demonstrate that the issue you found can be reproduced in such a way that compromises Confidentiality, Integrity, or Availability? If none is affected, the CVS score of the issue will be 0.0 which makes it a non-security bug. Please report these findings as public GitHub issues instead.

**Do you have clear and concise summary, impact, and technical analysis sections in your report?** Code assistants tend to generate “sloppy” reports: too verbose, hard to follow, making it unclear what is going on, what it affects, and how. If we have to do hours of work just for triage, we are likely to dismiss this as a low-effort AI submission of a non-issue. Put the work in before you ask us to put an equal or higher amount of work into it as well.