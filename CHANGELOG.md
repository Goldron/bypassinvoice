# Changelog
## [1.5.1] - 2026-07-20
### Fixed
- Invoice PDF download: closed the IDOR by verifying the requested invoice actually belongs to the logged-in customer's Dolibarr societe before serving it, and switched the link encryption key from a hardcoded value to PrestaShop's own per-installation secret
- Invoice PDF download was actually failing for an unrelated reason: the hardcoded fallback Dolibarr document template ("Invoice") isn't a real template name; now defaults to "crabe" (matching the config field's own hint) and the install default was corrected too
- Dolibarr API calls had no timeout, so a real Dolibarr outage (unreachable, not just erroring) could hang requests on the checkout/payment-confirmation path for a long time; capped at 5s to connect / 15s total
- `WSonline()` treated a fully failed connection (http_code 0) as "online" because it only checked `< 400`; now requires a real 2xx response
- `$product_dol` leaked across iterations in the invoice line builder, so a product with no barcode/ref right after one that matched in Dolibarr could silently get the wrong Dolibarr product on its invoice line
- Customer <-> societe sync on signup never ran: it checked a `Customer->name` property that doesn't exist
- Credit note generation could hit a fatal error if the Dolibarr connection was broken (missing the same guard the payment-confirmation hook already had)
- Completed es.php (1 key) and it.php (7 keys) translations, now matching fr.php
- Added a UNIQUE constraint on (id_customer, id_shop) to close a race condition that could create duplicate customer/societe mappings
- Re-enabled SSL certificate verification for Dolibarr API calls (was disabled)

### Changed
- Renamed the back-office relations screen from "Sociétés Dolibarr" to "Liaisons clients Dolibarr"

## [1.5.0] - 2026-07-20
### Added
- Back-office screen ("Liaisons clients Dolibarr") to list, edit and delete the customer <-> Dolibarr societe relations, with a live-search field to reassign a societe
- Credit notes (avoirs) now include the shipping cost when "refund shipping costs" is checked, even with no product selected
- English translations completed (20 -> 89 keys, now matching fr.php)
- English README, alongside the French one

### Fixed
- Invoice lines now always match the real PrestaShop order price: dropped the Dolibarr-side customer discount override, and stopped double-applying percentage-based specific prices (invoices and credit notes)
- Correct VAT rate on discount / free-shipping invoice lines, instead of a hardcoded 20%
- No more duplicate Dolibarr contact created when one already exists for a societe
- Societe id is now cached in the bypassinvoice table when found via SIRET/email lookup, not just when created
- Credit note generation is skipped when nothing was actually selected for refund
- Fatal error when opening the "Liaisons clients Dolibarr" edit form (id_shop field collision with ObjectModel)
- Fatal error in the module's own logger on any message containing a `%` character (broke the societe search)
- SQL injection in customer search (SIRET field), broken query in the internal relations list, direct unauthenticated access to log files
- `datepaye` sent as a string to the Dolibarr payments API, matching the endpoint's documented type

### Changed
- License switched to GPL-3.0-or-later

## [1.4.0] - 2024-04-08
### Added
- add Specific price and relative discount to Dolibarr

## [1.3.8] - 2024-03-30
### Added
- fix VAT import

## [1.3.7] - 2024-03-25
### Added
- refactoring

## [1.3.6] - 2024-03-15
### Added
- fix valid field

## [1.3.5] - 2024-02-29
### Added
- fix check access to endpoints
- refactoring

## [1.3.4] - 2024-02-09
### Added
- Add of Siret in customer search.
- refactoring

## [1.3.3] - 2024-01-31
### Added
- Credit note
- Code refactoring
- Added link for logs

## [1.3.2] - 2024-01-31
### Added
- First stable version