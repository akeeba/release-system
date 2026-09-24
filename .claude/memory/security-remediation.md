# Security-audit remediation

The main rules — models never do authentication/authorisation, and every remediation fix ships with a
unit test — are recorded in `.claude/security-audit-triage.md` ("Models must never perform
authentication/authorisation" and "Process notes"). Read that first. This file keeps the two details
that are not written there.

## Flag the architecture before adding an ACL check to a model

Do not propose "add a redundant check inside the model" as a fix for a "model method has no ACL check
of its own" finding without flagging the architectural tension first and letting the operator decide.

**Why:** during the 2026-09 remediation session the operator invalidated finding L1 ("`ItemModel`
download methods lack their own access checks") outright: "Models must NOT do authentication /
authorisation. The Controller does. Models are supposed to be medium level abstractions with only the
persistence layer (database) at a lower level than them. Joomla is wrong in putting authorisation in
backend models (AdminModel and ListModel). I am not going to repeat that gross mistake in my code."

**How to apply:** check whether the model call is reachable without going through the controller's
access-control chain (e.g. `ControllerCRIAccessTrait`'s `accessControlItem()` /
`accessControlRelease()` / `accessControlCategory()`) before raising it at all; if it is, raise it as a
question, not a patch.

## Run the whole unit suite after adding a security-fix test

After adding a test for a fix, run the full `phpunit` suite, not just the new file.

**Why:** the operator's rule during the 2026-09 remediation session was "everything we fix MUST have a
test", and new tests often need a new Joomla stub in `UnitTest/`, which can break other tests.

**How to apply:** `phpunit` from the repo root after every added test; fix any cross-test breakage from
stub changes before moving on.
