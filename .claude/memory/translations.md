# Translation flow

ARS ships machine-translated language files for el-GR, fr-FR, de-DE, es-ES, it-IT and pt-PT in
addition to the canonical en-GB.

**Why:** GitHub issue #248 requested machine translations for these six locales as a first-time
localisation effort.

**How to apply:** when adding new translations or updating existing ones:

- The canonical source is always `en-GB`; all other locales are derived from it.
- Glossaries live in `build/glossaries/{lang-code}.md` — consult and update them before each
  translation run for consistency.
- Process files in ~10–12 KiB chunks (the large backend `com_ars.ini` is ~36.5 KB / 581 lines — use 4
  chunks of ~145 lines).
- After writing translated files, update ALL 8 XML manifests: `component/ars.xml`,
  `build/templates/pkg_ars.xml`, and the 6 plugin/module manifests — add `<language tag="XX-XX">`
  entries for every new locale.
- Language file directory pattern: replace `en-GB` in the path with the new locale code (e.g.
  `component/backend/language/fr-FR/com_ars.ini`).
- The root `pkg_ars.xml` (if it exists alongside `build/templates/pkg_ars.xml`) must also be updated.
