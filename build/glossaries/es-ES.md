# ARS Translation Glossary — Spanish (Spain) / Español (España)

This glossary establishes consistent Spanish (Spain) translations for Akeeba Release System (ARS) terminology. Translators must follow these conventions to ensure coherence across all language files (`com_ars`, `mod_arsdownloads`, `mod_arsgraph`, and all plugins).

## Conventions

- Proper nouns and established technical trade names are kept in English.
- Software maturity levels (Alpha, Beta) are kept in English as they are industry-standard terms recognised by Spanish-speaking developers.
- Where the English abbreviation is widely used, it is shown in parentheses after the Spanish term.
- Gender of Spanish nouns: "categoría" (f), "versión" (f), "elemento" (m), "entorno" (m), "repositorio" (m), "flujo" (m), "registro" (m).

## Glossary Table

| English | Spanish (Spain) | Notes |
|---|---|---|
| Akeeba Release System | Akeeba Release System | Product name — proper noun, do not translate. |
| BleedingEdge | BleedingEdge | Technical term for the developer/nightly release category type — do not translate. Use as a single word exactly as written. |
| Bleeding Edge | Bleeding Edge | Two-word variant of the above — keep untranslated. |
| Category | Categoría | Context: a software release category grouping one or more releases. Feminine noun. |
| Release | Versión | Context: a versioned software release (e.g. "version 7.4.0"). Prefer "versión" over "lanzamiento" in this domain. Feminine noun. |
| Item | Elemento | Context: a downloadable file or link inside a release. Masculine noun. |
| Download ID | ID de descarga | Context: an authentication token used to authorise file downloads. Keep "ID" in uppercase. |
| Update Stream | Flujo de actualización | Context: an XML or INI feed consumed by the Joomla updater. Masculine noun. |
| Update stream | Flujo de actualización | Lowercase variant — same translation. |
| Environment | Entorno | Context: a software environment tag such as "PHP 8.1" or "Joomla 4". Masculine noun. |
| Auto-description | Descripción automática | Context: an automatically generated description for a release item. Feminine noun. |
| Automatic description | Descripción automática | Long-form variant — same translation. |
| Control Panel | Panel de control | Context: the ARS admin dashboard/home screen. Masculine noun. |
| Download Logs | Registros de descarga | Context: a log of file download events. Masculine plural noun. |
| Stable | Estable (Stable) | Release maturity level — highest stability. Keep "Stable" in parentheses for clarity in UI labels. |
| Release Candidate | Candidato a versión definitiva (RC) | Release maturity level — keep the abbreviation "RC" in parentheses, as it is widely used by developers. |
| Beta | Beta | Release maturity level — keep in English; universally recognised in the Spanish developer community. |
| Alpha | Alpha | Release maturity level — keep in English; universally recognised in the Spanish developer community. |
| Repository | Repositorio | Context: the entire ARS download repository containing all categories, releases, and items. Masculine noun. |
| Maturity | Madurez | Context: the maturity level of a release (Alpha → Beta → RC → Stable). Feminine noun. |
| Webservices | Webservices | Technical term for the Joomla web services API layer — do not translate. Used in the plugin name `plg_webservices_ars`. |
| Developer Releases | Versiones de desarrollo | Frontend label shown for BleedingEdge category type (`COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE`). |
| Official Releases | Versiones oficiales | Frontend label shown for normal category type (`COM_ARS_CATEGORY_TYPE_NORMAL`). |
| Latest Releases | Últimas versiones | Frontend heading for the latest-releases view. |
| Available versions | Versiones disponibles | Frontend label listing downloadable versions in a category. |
| More information | Más información | Link label on release list entries. |
| Download now | Descargar ahora | Call-to-action button label on item entries. |
| Compatibility | Compatibilidad | Label for the environments field on an item. |
| Dashboard | Panel principal | Short label for the admin dashboard menu entry. |
| Security severity | Gravedad de seguridad | Per-release field indicating how severe the security issue fixed by this release is (`COM_ARS_RELEASES_FIELD_SECURITY`). "Gravedad" is the standard Spanish term for CVE/vulnerability severity. Feminine noun. |
| Security release | Versión de seguridad | A release flagged as fixing a security issue (used in the field description and the badge label "Seguridad: %s"). |
| Critical (severity) | Crítica | Highest security severity level. Feminine to agree with "gravedad". |
| High (severity) | Alta | Security severity level. Feminine to agree with "gravedad". |
| Medium (severity) | Media | Security severity level. Feminine to agree with "gravedad". |
| Low (severity) | Baja | Security severity level. Feminine to agree with "gravedad". |
| None (severity) | Ninguna | Security severity level meaning the release has no associated security fix. Feminine to agree with "gravedad". |
