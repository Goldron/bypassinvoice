<p align="center">
  <a href="https://www.siladel.fr">
    <img src=".github/images/siladel.svg" alt="SILADEL" width="84" height="40">
  </a>
</p>

<p align="center">
  Developed by <a href="https://www.siladel.fr">SILADEL</a> — Author: IGREJA David
</p>

# BypassInvoice

*[English version](README.md)*

Délègue la gestion des factures — ou uniquement leur numérotation — à [Dolibarr](https://www.dolibarr.org/), via son API REST.

## Compatibilité

- PrestaShop 1.7+
- PHP >= 7.2
- cURL

## Prérequis

- Une instance Dolibarr accessible en HTTPS, avec le module API/REST (`api/index.php`) activé
- Une clé API Dolibarr disposant des droits nécessaires sur : tiers (thirdparties), factures (invoices/facture), contacts, entrepôts (warehouses), comptes bancaires (bankaccounts), dictionnaires (payment_types, payment_terms, countries, contact_types)

## Fonctionnement

Le module fonctionne selon deux modes, configurables depuis son écran de réglages :

- **Mode exclusif** — la facturation PrestaShop est désactivée (`PS_INVOICE` off) ; toutes les factures sont gérées côté Dolibarr et affichées au client dans son espace "Mes factures" (avec téléchargement PDF).
- **Mode duo** — les factures PrestaShop restent actives, mais leur numérotation est alignée sur celle générée par Dolibarr.

### Ce que fait le module automatiquement

- **Synchronisation client → société Dolibarr** : à la création ou la mise à jour d'un compte client PrestaShop, le module crée ou met à jour le tiers correspondant dans Dolibarr (nom, SIRET, conditions de règlement, etc.).
- **Création de facture à la confirmation de paiement** : lignes produits, transporteur, remises panier, puis validation de la facture dans Dolibarr.
- **Enregistrement du paiement** dans Dolibarr, avec mapping du module de paiement PrestaShop vers le mode de paiement Dolibarr correspondant.
- **Avoirs** : la création d'un avoir côté PrestaShop (remboursement) génère une facture d'avoir liée dans Dolibarr, produits sélectionnés et/ou frais de port remboursés inclus. Aucun avoir n'est généré si rien n'a été sélectionné pour le remboursement.
- **Mouvement de stock** (optionnel) : si un entrepôt Dolibarr est renseigné dans les réglages, la validation de facture transmet cet entrepôt à Dolibarr — le mouvement de stock effectif dépend ensuite de la configuration propre de Dolibarr (stock décrémenté à la facture ou à la commande).
- **Paiement différé** : un statut de commande peut être désigné comme "sans paiement" ; les commandes passées par ce statut ont leur facture créée avec des conditions de règlement dédiées, sans qu'aucun paiement ne soit transmis à Dolibarr tant que ce statut est présent dans leur historique.
- **Format du numéro de facture** personnalisable : préfixe, année, mois, caractère de séparation, longueur du numéro.
- **Journal d'événements** consultable depuis l'écran de configuration du module (accès réservé aux employés connectés au back-office).

### Gestion des relations client ↔ société

L'onglet **Sociétés Dolibarr** (sous le menu Clients du back-office) liste tous les clients PrestaShop déjà associés à une société Dolibarr. Pour chaque relation, il est possible de :
- **Modifier** la société associée, via un champ de recherche en direct dans les tiers Dolibarr
- **Supprimer** la relation

Seule la société peut être modifiée depuis cet écran ; le client PrestaShop associé n'est pas modifiable (relations créées uniquement par la synchronisation automatique du module).

## Installation

1. Charger le fichier zip du module via le back-office PrestaShop (Modules > Ajouter un nouveau module)
2. Cliquer sur "Installer"
3. Renseigner l'URL et la clé API de votre instance Dolibarr dans la configuration du module
4. Compléter les réglages (entité, banque, entrepôt, format de numérotation, mode d'affichage des factures) selon vos besoins

## Licence

GPL-3.0-or-later — voir [LICENSE.txt](LICENSE.txt).

## Auteur

SILADEL — [siladel.fr](https://www.siladel.fr) — <david@siladel.fr>

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.
