# Copilot Instructions for T.N. Memorial School Digital Platform

## 1. Project Overview

This repository contains the T.N. Memorial School Digital Platform, an enterprise-grade school website and ERP platform. The project is designed to support modern school operations through a professional web experience, a role-based management system, and structured technical documentation. All work should reflect enterprise software engineering standards, maintainability, and long-term scalability.

## 2. Tech Stack

- Frontend: Next.js
- Backend: PHP
- Database: MySQL
- API Style: REST API
- Documentation and delivery: GitHub Markdown, Git, and repository-based SDLC practices

Use only the technologies and patterns that are consistent with this repository. Do not introduce new stacks or frameworks outside the project scope.

## 3. Repository Folder Structure

The repository is organized into the following major areas:

- .github/ for repository-level automation and instructions
- apps/erp/ for the ERP application frontend
- apps/website/ for the public website frontend
- backend/ for API and service implementation
- database/ for schema, procedures, views, and related artifacts
- docs/ for all SDLC, architecture, API, UI, security, and testing documentation
- deployment/ for deployment assets and scripts
- tests/ for automated test suites
- scripts/, assets/, archive/, templates/, config/, and tools/ for supporting project artifacts

Always respect the existing repository structure when creating files, folders, or documentation.

## 4. Documentation Writing Standards

All documentation must be professional, concise, production-ready, and aligned with the repository’s existing SDLC approach. Write in clear enterprise language and avoid placeholder content, informal wording, or speculative statements.

Follow existing repository documentation first. If a related document already exists, use it as the authoritative reference for tone, structure, terminology, and level of detail.

## 5. Markdown Formatting Rules

Use GitHub Markdown only.

For every new markdown document, preserve the existing repository structure and formatting conventions. Do not introduce new sections unless they already exist in the repository’s documentation pattern.

When creating a document, use the established heading hierarchy and numbering style already used in the repository. The standard documentation structure is:

- # Title
- ## 1. Purpose
- ## 2. Scope
- ## 3. Overview
- ## 4. Detailed Design
- ## 5. Best Practices
- ## 6. Security Considerations
- ## 7. References
- Revision History
- Approval
- Footer

Use consistent markdown formatting, readable tables when already used by the repository, and the footer line:

© T.N. Memorial School Digital Platform

Do not add metadata tables to documents unless the existing repository pattern explicitly requires them.

## 6. Naming Conventions

Follow repository naming patterns already in use.

- Use descriptive document names with clear prefixes such as UI-001, UI-002, ARC-001, and similar domain-specific identifiers.
- Use hyphenated, readable names for markdown files.
- Keep terminology consistent with the existing documentation set.
- Do not introduce new naming patterns that are not already present in the repository.

## 7. Architecture Documentation Conventions

Architecture documents must be written in a formal, implementation-oriented style. They should describe system structure, component responsibilities, integration points, and design decisions in a way that supports delivery and maintenance.

When creating or updating architecture documentation:

- Maintain consistency with existing architecture documents.
- Cross-reference related requirements and business documentation where appropriate.
- Use professional terminology already present in the repository.
- Avoid introducing technologies or patterns that are not part of the project.

## 8. API Documentation Conventions

API documentation must reflect the repository’s REST API approach and remain consistent with the system architecture.

When documenting APIs:

- Use clear, resource-oriented descriptions.
- Describe expected behavior, request structure, response structure, and relevant status outcomes.
- Keep terminology aligned with the backend architecture and existing API documentation.
- Cross-reference the related database and module documentation where applicable.

## 9. Database Documentation Conventions

Database documentation must describe data structures, entities, relationships, constraints, and design intent clearly and precisely.

When creating database documentation:

- Use terminology already established in the repository.
- Keep descriptions aligned with architecture and API documentation.
- Avoid introducing database technologies or patterns outside the project scope.
- Ensure documentation remains clear for implementation and future maintenance.

## 10. UI Documentation Conventions

UI documentation must remain consistent with the existing UI documentation set.

When creating UI documentation:

- Follow the structure already used in UI documents.
- Preserve the repository’s terminology for design systems, component libraries, and interface standards.
- Keep documentation aligned with the Next.js frontend architecture.
- Do not add new sections or non-standard formatting.

## 11. Coding Standards

Code and documentation should reflect professional software engineering practices.

- Write clear, maintainable, and readable code.
- Keep modules organized and scoped to their purpose.
- Follow the existing project structure rather than creating ad-hoc patterns.
- Prefer clarity and consistency over clever implementations.
- Keep business logic and presentation concerns appropriately separated.
- Ensure new work integrates cleanly with the existing architecture.

## 12. Security Expectations

Security must be treated as a first-class concern in all work.

- Do not introduce insecure handling of input, output, or authentication.
- Preserve secure coding habits and validate assumptions.
- Avoid exposing sensitive data in documentation or implementation.
- Follow the repository’s documented security expectations.
- Do not include secrets, credentials, or private data in generated content.

## 13. Git Commit Message Conventions

Use clear, professional commit messages that describe the change precisely.

Preferred style:

- docs(ui): add component library documentation
- docs(api): update endpoint specifications
- feat(erp): add student management workflow
- fix(backend): resolve authentication issue

Commit messages should be concise, specific, and action-oriented.

## 14. Cross-Reference Rules

Cross-reference related documents whenever appropriate.

- Reference earlier documents by their document IDs and titles when relevant.
- Reuse terminology already present in the repository.
- Avoid duplicating content that is already documented elsewhere.
- Ensure related documents remain aligned with one another.

## 15. Revision History Rules

Every new document must include a revision history section.

- Preserve the existing table structure used in the repository.
- Use the columns Version, Date, Author, and Changes.
- Record the initial version clearly.
- Do not rewrite or alter previous revision history entries.

## 16. Approval Section Rules

Every document must include an approval section using the repository’s existing format.

Use the standard approval table:

| Role | Name | Status |
| Tech Lead | ChatGPT | Pending |
| Project Manager | You | Pending |

Do not change the approval format for previously approved documents unless explicitly requested.

## 17. AI Behavior

GitHub Copilot must behave as a disciplined repository contributor.

- Always follow existing repository documentation.
- Never invent document structures.
- Follow existing numbering.
- Follow existing writing style.
- Follow existing headings.
- Follow existing approval format.
- Follow existing revision history.
- Reuse terminology already present.
- Never change previously approved documents.
- Keep every new document consistent with the repository.
- When uncertain, consult the existing documentation before writing or editing anything.
