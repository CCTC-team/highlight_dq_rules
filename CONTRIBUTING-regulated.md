# Contributing to a regulated repo

This repository is regulated under UK clinical-trial law (see
[`.compliance.yml`](./.compliance.yml) for the exact frameworks that apply).
That changes what you can do in PRs and merges. The rules below are
**enforced** — some by CI, some by reviewers, some by org rulesets.

If you think a rule is wrong for your change, flag it in the PR description.
Don't quietly route around it.

## The merge rules

1. **No direct pushes to `main`.** Every change goes via PR with at least
   one review from someone other than the author. Force-pushes to `main`
   are blocked by org ruleset.
   - **Signed commits are required.** First-time setup at
     [CCTC-team/compliance-engine → `docs/commit-signing-setup.md`](https://github.com/CCTC-team/compliance-engine/blob/main/docs/commit-signing-setup.md).
     Unsigned commits will be rejected by the org ruleset.
2. **PR description must state regulatory impact.** Even "no impact" is
   fine — the inspector wants to see you considered it. Use the PR
   template's "Clinical / compliance checklist".
3. **Schema or audit-trail changes need a V&V issue.** Open one with the
   **Regulated feature (V&V)** template
   ([`.github/ISSUE_TEMPLATE/regulated_feature.yml`](.github/ISSUE_TEMPLATE/regulated_feature.yml))
   and link it from the PR. Record the structured V&V data — Risk ID,
   Requirement ID, Critical-to-Quality, Test Type, and the Acceptance / QA
   sign-offs (approver + date) — on the **project board card**, not in the
   issue body; the card panel is canonical. This applies to: database
   migrations, changes to logged fields, changes to electronic signature
   flow, changes that alter what users see or can do.
4. **Reason-for-change in commit messages.** Not just *what* — *why*. An
   MHRA inspector reading your `git log` should be able to tell why a
   change was made without asking you.

## Data handling rules

5. **No PID / CPI in the repo, ever.** Not in code, tests, fixtures,
   logs, screenshots, error messages, or PR comments. If you need
   realistic test data, generate or pseudonymise it. If you find PID in
   the repo, treat it as an incident — see [SECURITY.md](https://github.com/CCTC-team/.github/blob/main/SECURITY.md).
6. **Audit-trail behaviour is preserved unless a V&V issue says
   otherwise.** Migrations that drop, truncate, or rename audit-trail
   columns will be rejected on review. Tests that disable audit logging
   to make assertions easier will be rejected on review.
7. **No shared credentials.** Service accounts must be individually
   named and attributable. Anything that lets two humans share an
   identity for a GCP-relevant action breaks ALCOA+.

## Change-control rules

8. **`.compliance.yml` is owned by the QA lead.** Changes to it need
   QA-lead approval and cannot be self-approved. The `last_reviewed`
   date and `audit_trail_kind` / `account_model` / `pid_boundary` fields
   are inspector-facing — treat them as you'd treat a signed document.
9. **Validation evidence (`csv_evidence`) must be kept current.** If you
   change behaviour that the URS/FS/IQ/OQ/PQ pack describes, the pack
   gets updated in the same PR or a linked follow-up tracked before
   release.
10. **Dependency bumps need review like any other change.** No
    auto-merging dependabot PRs in this repo — every dependency change
    is a potential validation impact. Cyber Essentials Plus expects
    triaged security advisories within 14 days.

## Bring your feature's data (if this repo has stores)

*Applies only if this repo declares persistent stores in
[`.compliance.yml`](./.compliance.yml) (`state_lifecycle.stores`). A repo with no
stores can skip this section.*

A feature is **not acceptance-ready** until UAT can exercise it end-to-end, which
means it must **bring its own schema changes and its own test data**. When your
feature changes any store:

13. **Add an additive migration *and* a synthetic seed, in the same PR.** Append
    an **additive, forward-only** migration to that store's ordered set, and a
    **deterministic synthetic** seed that exercises your feature. Both land in the
    PR that adds the feature — your feature carries its own data so it can be
    acceptance-tested the moment it deploys mid-cycle, not only after the next UAT
    rebuild. A store change with no migration/seed **fails the conformance gate**.
    - **Forward-only + idempotent.** Never rewrite or reorder a merged migration —
      append. The seed applies only the deltas not yet present (a full rebuild
      replays the whole set), so it must be idempotent.
    - **Synthetic only.** Never production clinical data in a seed (the same rule
      as #5). Seeds are committed with a hash so the accepted baseline is
      reproducible.
    - **Breaking changes use expand/contract.** Add the new shape, migrate, then
      remove the old — each step backward-compatible — so a migration never breaks
      a running store mid-deploy.
    - Use your store's own tooling (EF Core migrations, the `neo4j-migrations` CLI,
      RabbitMQ topology-as-code); the contract is store-agnostic. The full rules —
      the `state:*` verbs, the clean-rebuild ≡ incremental-forward guarantee, and
      the baseline-version tuple — are in
      [`CCTC-team/compliance-engine → docs/data-state-lifecycle-contract.md`](https://github.com/CCTC-team/compliance-engine/blob/main/docs/data-state-lifecycle-contract.md).
    - **If this repo uses requirement bundles, those artefacts live in the bundle,
      not loose**, and the manifest declares them per store — `stores.<name>.migrations`,
      and `stores.<name>.seed` as **either** `contribution` (a type name, for a store
      seeded by registered code) **or** `artefacts` (paths, for a store seeded by
      committed data). Never both: a store has one seed, and two declarations of it
      could disagree. `artefacts` paths are reconciled **both ways** — declared-but-absent
      *and* present-but-undeclared — so an artefact cannot be added, renamed or removed
      without the manifest saying so. ⚠️ A `contribution` cannot get that (no file list
      reconciles against a type name), so it carries the weaker existence-only check.
      Structure and rationale: the same document, *"Requirement bundles"*.

14. **Declaring a store commits you to servicing it — declare only what you
    service.** A declared store needs working `state:*` bindings for every verb.
    A **destructive** verb (`rebuild` / `restore`) additionally requires
    `prod_isolation` attested true *and* the resolved live target to match an entry
    in `state_lifecycle.nonprod_targets` exactly; an absent allowlist means
    destructive verbs are refused, which is the safe default rather than a fault.
    ⚠️ Note `prod_isolation` is a **block-level** boolean — the engine reads it once
    and applies it to every declared store — so it must be true of all of them at
    once, which constrains where non-production stores may live.

## Working with AI assistance (Claude, Copilot, etc.)

AI assistance is allowed in this repo, with two hard rules:

11. **Never paste PID or CPI into a prompt.** The same rule as #5
    applies to prompts, even transient ones. If you wouldn't send it in
    a Slack DM, don't send it to a model.
12. **Review AI-generated code like any other code.** The author of a
    PR is responsible for everything it contains. "Claude wrote it"
    isn't a defence at audit.

## Lifecycle board

Regulated work moves card-by-card through a forward-only project board.
Each regulated repo is mapped to a specific lifecycle board via its
`LIFECYCLE_PROJECT_NUMBER` repo variable; the boards under enforcement
are listed in
[`CCTC-team/compliance-engine/.github/project-enforcement.yml`](https://github.com/CCTC-team/compliance-engine/blob/main/.github/project-enforcement.yml)
under `projects:`. Ask the QA lead which board this repo lives on if
you're not sure.

The board is **enforced** by automation — moves that skip steps are
commented on, labelled `process-violation`, and (as checks graduate)
reverted.

- **Status order is enforced.** Forward moves advance one column at a
  time. Backward moves and side exits (`Redundant`, `Archived`) are
  always allowed. From `Redundant` or `Archived`, the only legal
  restoration target is `Triage` — a card cannot launder its history by
  being archived and dropped back into a later column.
- **Approver fields must be GitHub usernames.** `Acceptance Approver` and
  `QA Approver` are free-text on the card; type the username without
  the `@`. The automation validates the login via the GitHub API and
  refuses the move if it doesn't resolve.
- **Segregation of duties is required.** The PR author, Acceptance Approver,
  and QA Approver must be three distinct people. The audit flags any
  card that ends in `QA approved` with fewer than three identities.
- **Acceptance / QA sign-offs are recorded on the card, not as checkboxes.**
  Set `Acceptance Approver` + `Acceptance Signoff Date` **and `Acceptance Build`**
  before moving the card to `User acceptance`, and `QA Approver` +
  `QA Signoff Date` before `QA approved` — attributable, dated sign-offs (who,
  when, **and what was tested**), not anonymous ticks. `User acceptance` is the
  feature-level acceptance sign-off (the feature meets the URS in a dev/test
  environment); it is **not** the formal Performance Qualification, which is
  performed on the built release candidate at the release-pipeline authorisation
  gate.
- **`Acceptance Build` records *what* the user tested — record the commit SHA.**
  Set it to the `develop` commit SHA (or nightly tag) of the build acceptance
  was performed against. **Prefer the full commit SHA**: a tag can later be
  pruned, but a SHA is permanent in history, and the engine fingerprints the
  feature's edited files at that commit so it can detect — and **invalidate** —
  an acceptance if the feature changes before QA or release. If that happens the
  card is sent back to re-accept against a fresh build; because the compare is
  against a moving integration branch, an occasional re-acceptance (after a
  rename or a shared-file change) is expected and fail-safe, not a fault.
- **`Risk ID` / `Requirement ID` live on the card, not the issue body.**
  The card panel is canonical — set them there; the issue body carries no
  copy and is not read for them.
- **The URS and feature tests are declared against your `Requirement ID` —
  authored in two moments, and in one of two places.** When the feature card
  is raised, the requirement owner sets `Requirement ID` (and `Risk ID` /
  `Critical-to-Quality`) on the panel; the `.spec`/`.feature` files do not
  exist yet. When you write those files, declare them in the **same PR**, so
  the URS, the Gherkin and the declaration land and are reviewed together.

  ⭐ **Which place depends on whether this repo has adopted requirement
  bundles. Check before you write — using the wrong one creates a second
  source of truth that can disagree.** Does `requirements/<id>/manifest.yml`
  exist?

  **Bundles (preferred).** The manifest is the traceability source and
  **supersedes** the `.compliance.yml` `traceability` block. Declare there,
  and do **not** also add a block entry:

  ```yaml
  # requirements/REQ-014/manifest.yml
  requirement: REQ-014
  title: <what the user asked for>
  status: draft            # draft · in-development · accepted · retired
  urs: requirements/REQ-014/urs/<name>.spec
  feature_tests:
    - features/<area>/<name>.feature
  ```

  **No bundles (fallback).** Add the matching `traceability:` entry to
  `.compliance.yml`, keyed by that `Requirement ID`:

  ```yaml
  traceability:
    REQ-014:
      urs: user_requirement_specification/<area>/<name>.spec
      features:
        - features/<area>/<name>.feature
  ```

  The engine reads the manifest set first and falls back to the block, so a
  repo migrates by preference-with-fallback; the block is retired once every
  consumer resolves through manifests. ⚠️ A manifest matching by id but
  carrying **no** evidence does not shadow a resolving fallback entry — so a
  half-filled manifest fails quietly to the old path rather than erroring.

  Either way, the `V&V tests pass` precondition resolves your `Requirement ID`
  through the declaration and checks each path exists; the board-evidence
  report embeds their content at the release tag. `Requirement ID` is the join
  key between the board card and this repo — a foreign key, not a duplicated
  value.

  Where the URS *file* lives is the repo's choice — the declaration names a
  path either way. A bundle repo may co-locate it under `requirements/<id>/urs/`
  (nothing globs a `.spec`); a feature test stays where its runner's glob
  expects it and is referenced, never moved.
- **Bypasses are single-use and admin-gated.** If you genuinely need
  to skip a step, ask an org admin to apply
  `process-override:approved` on the linked issue. The label is
  honoured for one transition and cleared by the bot afterwards.
  Every bypass is recorded in the nightly audit issue.
- **Rolling audit issue.** A daily sweep maintains
  `Project enforcement drift — <board name>` in
  `CCTC-team/compliance-engine`. Findings appear there overnight; the issue is
  auto-closed when the board is clean.

## Releases & milestones

Regulated changes are released in **milestones**, not per-commit. The
mechanics:

- **Assign your issue to the target milestone (`vX.Y.Z`).** One milestone
  groups the requirement set for exactly one release. The release notes
  trace every requirement in the milestone from its CtQ factor down — an
  issue with no milestone is invisible to that matrix.
- **A Release is a published, evidenced artifact — not a tag.** Cutting a
  release builds a container image in CI, pushes it to GHCR by immutable
  digest, attaches the validation report, SBOM, checksums, the signed release
  manifest, the CtQ traceability matrix and the **board-evidence report**, and
  records who authorised it. The server then *pulls* that verified image;
  nothing pushes into production.
- **Board evidence is a mandatory regulated artifact.** Every regulated release
  also attaches a board-derived inspector report — a full snapshot of the
  lifecycle board's `Released` features with a "what's new in this milestone"
  lead and the URS/`.feature` content embedded by `Requirement ID`. It reads the
  board named by the **`LIFECYCLE_PROJECT_NUMBER`** repo variable: set it to your
  board's number, or a regulated release **hard-fails** at the board-evidence
  step. (The board read uses the CCTC Project Enforcement App credentials the
  release caller passes — see `release-caller.yml`.)
- **A release cannot publish without a green validation report, board evidence,
  and a signed release manifest.** The release workflow fails if the
  `validation-docs` or `board-evidence` target produces no report or the manifest
  cannot be signed, and the on-server agent **refuses to deploy** any digest not
  covered by a manifest whose SSH signature verifies against its
  `allowed_signers`. "It built" is not "it released".
- **The version tag must be signed**, the same as your commits. The build's
  `tag` target creates a signed tag; the release workflow refuses an
  unsigned one, and a tag ruleset stops a published `v*` tag being moved or
  deleted.
- **Production publish is gated by a `production` Environment approval** from
  the QA-approver group, bound to the exact image digest. This aligns with
  the board's `QA approved → Released` step — it happens after both
  `User acceptance` and `QA approved`. Note the GitHub approval is the
  technical gate; the formal re-authenticated electronic signature of record
  is captured per the CTU SOP (in-app or in the QMS/eTMF), referencing the
  release digest.

The full model — the three layers, the artifact set and the clause each
answers — is in
[`CCTC-team/compliance-engine → docs/release-process.md`](https://github.com/CCTC-team/compliance-engine/blob/main/docs/release-process.md).

## How CI enforces what it can

- `.compliance.yml` must parse and validate against the schema.
- README must carry the `<!-- compliance:banner -->` marker.
- `last_reviewed` must be within `review_cadence_months`.
- `schema_version` must be in the validator's supported set.

Everything else above is enforced by reviewers and rulesets, not CI.
Be a good reviewer.

---

This file is canonical at
[`CCTC-team/compliance-engine/templates/compliance/CONTRIBUTING-regulated.md`](https://github.com/CCTC-team/compliance-engine/blob/main/templates/compliance/CONTRIBUTING-regulated.md)
and pushed into each regulated repo by the compliance-drift workflow. To
change the rules for the whole org, edit it there.
