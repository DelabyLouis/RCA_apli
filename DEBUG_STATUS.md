# 🔍 DÉBOGAGE EN COURS - API BULK-UPDATE-ORDER

## 🎯 OBJECTIF

Résoudre l'erreur 500 sur l'API `bulk-update-order` pour obtenir une vraie sauvegarde serveur.

## ❌ PROBLÈME ACTUEL

-   **Erreur JavaScript** : `JSON.parse: unexpected character at line 1 column 1`
-   **Cause probable** : Le serveur retourne du HTML d'erreur au lieu de JSON
-   **Status HTTP** : 500 (erreur serveur interne)

## 🔧 DÉBOGAGE AJOUTÉ

### Côté Serveur (PHP)

```php
// Logs ajoutés dans TransactionController::bulkUpdateOrder()
error_log("=== BULK UPDATE ORDER CALLED ===");
error_log("Raw content: " . $rawContent);
error_log("Decoded data: " . print_r($data, true));
// + logs détaillés pour chaque étape
```

### Côté Client (JavaScript)

```javascript
console.log("📤 Données envoyées:", { transactions: transactionsData });
console.log("📥 Réponse reçue:", response.status, response.statusText);
console.log("📄 Réponse brute:", text);
// + gestion d'erreur robuste avec texte complet
```

## 🧪 TEST À EFFECTUER

1. **Faire un drag & drop**
2. **Ouvrir la console** → Voir les logs détaillés
3. **Côté serveur** → Vérifier les logs PHP dans `var/log/`
4. **Identifier l'erreur exacte** qui cause l'erreur 500

## 📊 DONNÉES ATTENDUES

Le JavaScript devrait envoyer :

```json
{
    "transactions": [
        { "id": 370, "order": 1, "exercice_id": 213 },
        { "id": 371, "order": 2, "exercice_id": 213 }
    ]
}
```

## 🎯 RÉSULTAT SOUHAITÉ

-   **Si l'API marche** → Sauvegarde serveur + refresh → Ordre persisté ! ✅
-   **Si l'API plante** → Identification de l'erreur pour correction

**Test requis : Drag & drop + consulter les logs de débogage ! 🔍**
