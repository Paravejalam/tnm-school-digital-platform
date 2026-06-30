# AIP-001 - AI Prompt Guide

# 1. Purpose

This document defines the AI prompt guide for the T.N. Memorial School Digital Platform. It establishes a structured approach for creating, organizing, and maintaining prompts used for repository analysis, documentation generation, architecture review, and implementation support.

---

# 2. Scope

This document applies to prompts used within the repository’s documentation and development workflows. It covers prompt structure, reuse, governance, and expected quality standards for AI-assisted work.

The scope includes prompts used for architecture review, documentation drafting, requirements analysis, testing support, and deployment-related tasks.

---

# 3. Overview

AI-assisted work should be guided by clear prompts and repository-specific context. A structured prompt approach improves consistency, reduces ambiguity, and ensures that generated outputs remain aligned with the platform’s documentation standards and technical direction.

The guide should remain aligned with the repository’s architecture, documentation conventions, security expectations, and delivery model.

---

# 4. Detailed Design

## Prompt Principles

Prompt creation should follow these principles:

- Provide clear task context and intended output.
- Reference repository terminology and existing documentation where relevant.
- Preserve the repository’s documentation structure and style.
- Avoid introducing unsupported technologies, assumptions, or speculative details.
- Keep prompts focused on the requested deliverable.

## Prompt Categories

The prompt guide may support categories such as:

- Documentation generation prompts.
- Architecture analysis prompts.
- Requirements and traceability prompts.
- Testing and deployment prompts.
- Security and operational review prompts.

## Prompt Maintenance

Prompts should be reviewed when repository documentation, architecture, or delivery practices change. Reusable templates should remain concise and clear while supporting consistent outcomes.

---

# 5. Best Practices

- Use prompts that explicitly reference the repository’s existing documents and conventions.
- Keep prompts scoped to a single task or deliverable.
- Validate generated content against repository standards before use.
- Avoid prompts that request undocumented or non-repository-specific decisions.
- Store prompt templates in a structured and maintainable location.

---

# 6. Security Considerations

AI prompts must not request or expose secrets, credentials, or restricted operational details. Prompts should be designed to support documentation and implementation work without compromising security.

Additional security considerations include:

- Avoiding prompts that require access to protected systems or sensitive data.
- Maintaining confidentiality of internal implementation choices and operational details.
- Ensuring that generated content is checked for appropriate security and privacy handling.

---

# 7. References

- ARC-001 High-Level Architecture
- REQ-001 Functional Requirements
- SEC-001 Security Standards
- TST-001 Testing Strategy
- DEP-001 Deployment Strategy

---

# Revision History

| Version | Date | Author | Changes |
|----------|------|--------|---------|
| 1.0 | 2026-07-01 | Project Team | Initial Version |

---

# Approval

| Role | Name | Status |
|------|------|--------|
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

---

© T.N. Memorial School Digital Platform
