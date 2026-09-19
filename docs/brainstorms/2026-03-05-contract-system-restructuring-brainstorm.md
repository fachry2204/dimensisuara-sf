# Contract System Restructuring — Brainstorm

## What We're Building

Restructure the contract system to support **statement documents as first-class contracts** with sequential document numbers, while making the architecture extensible for future contract types.

Currently, statements (and NUON) are generated per publishing request without formal document numbering. The contract table only supports `aggregator` and `publishing` types, with an inconsistent binding strategy (`profileid` for aggregator, `request` morph for publishing).

## Why This Approach (Approach A: Unified Owner Morph)

### Chosen: Unified Owner Morph
- Rename `request_type`/`request_id` → `owner_type`/`owner_id` morph
- Add `statement` as a new contract type 
- Statement contract owner = `Publishing`, profileid = publishing's requestor
- NUON stays generate-only (no contract record, no doc number)
- Each type has independent sequential numbering

### Alternatives Considered
- **Approach B (Add docnum to `publishing_documents`)** — Quick fix but creates parallel numbering systems and architectural debt
- **Approach C (Abstract `NumberedDocument` base)** — Over-engineering for current needs

## Key Decisions Made

1. **Only statement needs a document number** — NUON remains generate-only
2. **Independent counters per type** — statement has its own sequence (001, 002, ...)
3. **Owner morph replaces request morph** — consistent binding across all contract types
4. **`profileid` stays** — needed for access control and ownership queries
5. **Statement number assigned during admin approval** — same flow as publishing contracts
6. **NUON stays as-is** — can be promoted to contract later if needed (YAGNI)

## Owner Morph Mapping

| Contract Type | `owner` morph points to | `profileid` derived from |
|---------------|------------------------|--------------------------|
| Aggregator    | `Profile`              | same Profile             |
| Publishing    | `Signee`               | signee's songwriter/profile |
| Statement     | `Publishing`           | publishing's requestor   |

## Open Questions

None — ready for implementation planning.
