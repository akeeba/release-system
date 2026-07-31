# ARS Translation Glossary — German (Germany) / Deutsch (Deutschland)

This glossary defines the preferred German translations for ARS-specific terminology. Translators must follow these conventions consistently across all language files (`com_ars.ini`, `com_ars.sys.ini`, module and plugin language files).

## Conventions

- Product names and established technical terms are kept in English.
- Where the English term is kept in parentheses after the German translation, it aids recognition for users familiar with the English UI.
- Gender assignments follow standard German computing/software usage.

## Glossary

| English | German (Germany) | Notes |
|---|---|---|
| Akeeba Release System | Akeeba Release System | Product name / proper noun — do not translate. |
| BleedingEdge | BleedingEdge | Technical term for the developer-release category type — do not translate. Treat as a single compound word with no space (matches the category type identifier used in code). |
| Bleeding Edge | Bleeding Edge | Two-word variant used in prose — do not translate. |
| Category | Kategorie | *die Kategorie* — software release category in ARS. |
| Release | Version | *die Version* — a versioned software release. Prefer "Version" over "Veröffentlichung" in the ARS context because German-speaking Joomla communities consistently use "Version" for numbered software releases. |
| Item | Datei / Download-Datei | *die Datei* — a downloadable file or link inside a release. Use "Datei" in short labels (e.g., table headers); use "Download-Datei" in longer descriptive text. |
| Download ID | Download-ID | *die Download-ID* — authentication token for downloading files. Hyphenated compound; keep "ID" in uppercase. |
| Update Stream | Update-Stream | *der Update-Stream* — XML/INI feed consumed by the Joomla updater. Hyphenated compound; both words are treated as technical terms. |
| Update stream | Update-Stream | Lowercase variant used in prose — same translation rule applies. |
| Environment | Umgebung | *die Umgebung* — software environment descriptor (e.g., PHP 8.1, Joomla 4). |
| Auto-description | Automatische Beschreibung | *die automatische Beschreibung* — automatically generated item description rule. |
| Automatic description | Automatische Beschreibung | Long-form variant used in menu titles (`COM_ARS_TITLE_AUTODESCRIPTIONS`). |
| Control Panel | Kontrollzentrum | *das Kontrollzentrum* — the ARS admin dashboard / overview page. This follows the convention used in Joomla's own German translation (Joomla uses "Kontrollzentrum" for its own Control Panel). |
| Download Logs | Download-Protokoll | *das Download-Protokoll* — log of file download events. Use singular "Protokoll" as a collective noun in titles; use plural "Download-Protokolle" where the context requires it. |
| Stable | Stabil (Stable) | Release maturity level. The English word in parentheses aids recognition. Adjective used attributively: "stabile Version". |
| Release Candidate | Veröffentlichungskandidat (RC) | Release maturity level. Keep the "RC" abbreviation in parentheses as it is widely recognised in the software community. |
| Beta | Beta | Release maturity level — keep untranslated. "Beta" is a universally recognised term in German software contexts. |
| Alpha | Alpha | Release maturity level — keep untranslated. Same reasoning as Beta. |
| Repository | Repository | *das Repository* — the entire ARS download repository. The English loanword is standard in German IT/software usage; do not translate to "Ablage" or "Depot". |
| Maturity | Reifegrad | *der Reifegrad* — release maturity level classification (Alpha → Beta → RC → Stable). |
| Webservices | Webservices | Technical term — do not translate. Keep as one word matching Joomla's own plugin group name (`plg_webservices_ars`). |
| Dashboard | Dashboard | Keep untranslated — the English loanword is standard in German UI contexts and is used by Joomla's own German translation. |
| Repository Browser | Repository-Browser | *der Repository-Browser* — frontend view that lets users browse all categories. |
| Official Releases | Offizielle Versionen | Used for the Normal category type label (`COM_ARS_CATEGORY_TYPE_NORMAL`). |
| Developer Releases | Entwickler-Versionen | Used for the BleedingEdge category type label (`COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE`). |
| Minimum maturity | Mindestreifegrad | Filter label for the minimum release maturity to display. |
| Compatibility | Kompatibilität | Label for the Environments field on an Item (`COM_ARS_ITEM_FIELD_ENVIRONMENTS`). |
| Security severity | Sicherheits-Schweregrad | Per-release field indicating how severe the security issue fixed by this release is (`COM_ARS_RELEASES_FIELD_SECURITY`). Hyphenated compound, matching the convention for other technical compounds (Update-Stream, Download-ID). |
| Security release | Sicherheitsversion | A release flagged as fixing a security issue (used in the field description and the badge label "Sicherheit: %s"). |
| Critical (severity) | Kritisch | Highest security severity level. |
| High (severity) | Hoch | Security severity level. |
| Medium (severity) | Mittel | Security severity level. |
| Low (severity) | Niedrig | Security severity level. |
| None (severity) | Ohne | Security severity level meaning the release has no associated security fix. |
