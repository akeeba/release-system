# ARS Translation Glossary — Italian (Italy) / Italiano (Italia)

This glossary defines the canonical Italian (Italy) translations for Akeeba Release System (ARS) domain-specific terms. Translators must use these terms consistently across all language files (`it-IT.com_ars.ini`, `it-IT.com_ars.sys.ini`, plugin/module language files, etc.).

---

## Glossary

| English | Italian (Italy) | Notes |
|---|---|---|
| Akeeba Release System | Akeeba Release System | Product name / proper noun — do not translate. |
| BleedingEdge | BleedingEdge | Technical term for the developer-release category type — do not translate. One word, capital B and E, as used in code and UI. |
| Bleeding Edge | Bleeding Edge | Two-word variant used in prose descriptions — do not translate. |
| Category | Categoria | Context: a software release category (e.g. a product line). Plural: Categorie. |
| Release | Versione | Context: a versioned software release (e.g. v7.4.0). Prefer "versione" over "rilascio" for software releases. Plural: Versioni. |
| Item | File | Context: a downloadable file or link bundled inside a release. The source distinguishes "file" and "link" item types; use "File" as the generic label and "collegamento" for link-type items if distinction is needed. Plural: File. |
| Download ID | ID di download | Context: an authentication token used when downloading files. Keep "ID" as an acronym (uppercase). |
| Update Stream | Flusso di aggiornamento | Context: an XML or INI feed consumed by the Joomla updater. Plural: Flussi di aggiornamento. |
| Update stream | Flusso di aggiornamento | Lower-case variant used in body text — same translation. |
| Environment | Ambiente | Context: a software environment descriptor such as "PHP 8.1" or "Joomla 4". Plural: Ambienti. |
| Auto-description | Descrizione automatica | Context: a rule that auto-generates item descriptions. Alternative label in source: "Automatic description". Plural: Descrizioni automatiche. |
| Automatic description | Descrizione automatica | Longer label variant used in admin menus — same translation as Auto-description. |
| Control Panel | Pannello di controllo | Context: the ARS admin dashboard / landing page (equivalent to Joomla's Control Panel). |
| Download Logs | Registro dei download | Context: a log of file download events. Use "Registro" (singular log/journal) rather than "Registri" when used as the section heading. Plural when referring to multiple entries: "Registri dei download". |
| Repository | Repository | Context: the entire ARS download repository (the collection of all categories, releases, and items). The English loanword is well established in Italian IT contexts; keep untranslated. Alternatively "Archivio" may be used if the target audience is non-technical. |
| Maturity | Maturità | Context: the release maturity level (Alpha → Beta → Release Candidate → Stable). |
| Stable | Stabile (Stable) | Maturity level. Include the English term in parentheses on first mention in UI where space allows, e.g. "Stabile". Abbreviation: — |
| Release Candidate | Release Candidate (RC) | Maturity level. Universally understood in Italian IT; keep the English term. Abbreviation RC is acceptable and preferred in space-constrained contexts. |
| Beta | Beta | Maturity level. Keep in Italian as-is; the English/Greek term is standard across languages. |
| Alpha | Alpha | Maturity level. Keep in Italian as-is; the English/Greek term is standard across languages. |
| Webservices | Webservices | Technical term for the REST API plugin group — do not translate. |
| Security severity | Gravità di sicurezza | Per-release field indicating how severe the security issue fixed by this release is (`COM_ARS_RELEASES_FIELD_SECURITY`). "Gravità" is the standard Italian term for CVE/vulnerability severity. |
| Security release | Versione di sicurezza | A release flagged as fixing a security issue (used in the field description). |
| Critical (severity) | Critica | Highest security severity level. Agrees in gender with "gravità" (f.). |
| High (severity) | Alta | Security severity level. |
| Medium (severity) | Media | Security severity level. |
| Low (severity) | Bassa | Security severity level. |
| None (severity) | Nessuna | Security severity level meaning the release has no associated security fix. Agrees in gender with "gravità" (f.). |

---

## Usage Notes

1. **Genere grammaticale**: "Categoria" (f.), "Versione" (f.), "Ambiente" (m.), "Flusso" (m.), "Repository" (m. or f. — invariable loanword).
2. **Capitalisation**: Italian nouns are lowercase in running text. Capitalise only at the start of a sentence or for proper nouns/product names.
3. **"Versione" vs "Rilascio"**: Use "Versione" for a software release because Italian technical users and the Joomla Italian community consistently use "versione". Reserve "rilascio" only in narrative/marketing contexts ("il rilascio di Joomla 5").
4. **"File"**: Invariable in Italian (same form singular and plural). Accepted by the Accademia della Crusca in technical contexts.
5. **"Download"**: The verb "scaricare" and noun "scaricamento" are formal alternatives, but "download" (invariable loanword) is overwhelmingly preferred in Italian software UI. Keep "download" in compound terms like "ID di download" and "Registro dei download".
6. **BleedingEdge / Bleeding Edge**: Always render exactly as in this glossary (no translation, no italics, no quotes) when used as a category-type label. When used in prose to explain what it is, a parenthetical explanation is welcome: "BleedingEdge (versioni di sviluppo)".
