# Deferred Items

Cross-cutting concerns surfaced during brainstorm/plan/gate reviews that were
consciously deferred rather than fixed inline. Each entry records the concern, why it
was deferred, and the recommendation so a future ticket can pick it up with full context.

---

## DI-1 — PDP Law data-retention position on publishing deletion

**Surfaced by:** `/gate` finding #I8, plan `2026-07-20-publishing-request-delete-plan.md`
**Date:** 2026-07-20
**Severity:** Medium (compliance / legal)

### Concern

Deleting a publishing request hard-deletes its `publishing_signees` rows via FK CASCADE.
Those rows contain personal data of third-party songwriters (not just the account holder):

- `full_name`
- `email`
- `phone`
- `ktp_num` — Indonesian national identity number (Nomor Induk Kependudukan)
- signature image file (via `morphOne File`, `type = 'signature'`)

There is currently **no documented data classification** for this PII and **no stated
retention policy**. The deletion behavior arrives as a side effect of the FK constraint,
not as a deliberate compliance decision.

### Relevant regulation

Indonesia's **Personal Data Protection Law (UU No. 27/2022, "UU PDP")** grants data
subjects a right to erasure and obliges controllers to delete personal data when it is no
longer needed for its collection purpose. Under that framing, deleting signee PII when the
customer deletes an abandoned/rejected request is arguably the **correct** outcome — it
minimizes retained personal data.

The problem is not the behavior; it is that the behavior is **undocumented and
unintentional**. Compliance posture should be an explicit decision with a paper trail.

### Recommendation

1. **Classify** the `publishing_signees` PII fields as *Confidential — Personal Data* in
   whatever data-classification register the org maintains (create one if absent — see also
   ISO 27001 A.8 Asset Management).
2. **State the retention position explicitly:** signee PII is retained only while the
   parent publishing request is active; it is erased when the request is deleted or when
   the request reaches a terminal state past its retention window. Record this in a
   privacy/retention policy document, not only in code comments.
3. **Preserve a minimal, non-PII audit record** of the deletion event (see plan Task 3.2 —
   `Log::info` context now captures `title`, `status`, `signee_count`, and signee emails).
   Note the tension: the deletion log currently records signee **emails**, which is itself
   personal data. Decide whether the audit log should hash or omit emails to avoid
   re-introducing the PII the deletion was meant to remove. **This is an open question for
   the compliance owner, not resolved here.**
4. Consider whether a **soft-delete + scheduled purge** model (retain N days, then hard
   delete) better satisfies both erasure rights and audit/dispute needs than immediate
   hard delete. Currently rejected in favor of hard delete for simplicity (brainstorm
   decision); revisit if legal requires a grace/recovery window.

### Status

Deferred. Requires a compliance/legal owner, not an engineering decision. The engineering
change (linked signature-file deletion) ships in the 2026-07-20 plan; the policy
documentation and the audit-log-PII question do not.
