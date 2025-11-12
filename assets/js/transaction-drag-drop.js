console.log("=== TRANSACTION DRAG & DROP LOADED ===");

// Variables globales
let TRANSACTION_REORDER_URL = "/transaction/reorder";

// Configuration de l'URL depuis le template
function setTransactionReorderUrl(url) {
    TRANSACTION_REORDER_URL = url;
    console.log("URL configurée:", TRANSACTION_REORDER_URL);
}

// Fonction pour mettre à jour les numéros d'ordre dans le DOM sans recharger
function updateTransactionOrdersInDOM() {
    const tbody = document.getElementById("transactions-tbody");
    if (!tbody) return;

    const transactionRows = tbody.querySelectorAll(".transaction-row");
    let currentOrder = 1;

    transactionRows.forEach((row) => {
        // Mettre à jour l'attribut data-order
        row.setAttribute("data-order", currentOrder);

        // Mettre à jour le numéro d'ordre affiché dans la cellule correspondante
        const orderCell = row.querySelector("td:first-child"); // Première colonne = N° d'ordre
        if (orderCell) {
            orderCell.textContent = currentOrder;
        }

        currentOrder++;
    });

    console.log(
        `✅ ${transactionRows.length} numéros d'ordre mis à jour visuellement`
    );
}

// Fonction d'initialisation principale
function initTransactionDragDrop() {
    const tbody = document.getElementById("transactions-tbody");

    if (!tbody) {
        return;
    }

    const transactionRows = tbody.querySelectorAll(".transaction-row");

    if (transactionRows.length === 0) {
        return;
    }

    transactionRows.forEach((row, index) => {
        const dragHandle = row.querySelector(".drag-handle");

        if (!dragHandle) {
            return; // Pas de drag pour les exercices clôturés
        }

        row.draggable = true;
        row.setAttribute("draggable", "true");

        // Drag & drop configuré

        // Event listeners
        addDragEventListeners(row, tbody);
    });
}

function addDragEventListeners(row, tbody) {
    row.addEventListener("dragstart", function (e) {
        this.classList.add("dragging");
        e.dataTransfer.setData("text/plain", this.dataset.id);
        e.dataTransfer.effectAllowed = "move";
    });

    row.addEventListener("dragend", function (e) {
        this.classList.remove("dragging");
        // Supprimer tous les indicateurs drag-over
        const allRows = tbody.querySelectorAll(".transaction-row");
        allRows.forEach((r) => r.classList.remove("drag-over"));
    });

    row.addEventListener("dragover", function (e) {
        e.preventDefault();
        const draggingRow = tbody.querySelector(".transaction-row.dragging");
        if (draggingRow && draggingRow !== this) {
            this.classList.add("drag-over");
        }
    });

    row.addEventListener("dragleave", function (e) {
        this.classList.remove("drag-over");
    });

    row.addEventListener("drop", function (e) {
        console.log("=== DROP EVENT ===");
        e.preventDefault();
        this.classList.remove("drag-over");

        const draggingRow = tbody.querySelector(".transaction-row.dragging");
        console.log("Dragging row:", draggingRow);
        console.log("Target row:", this);

        if (draggingRow && draggingRow !== this) {
            console.log(
                "Dragging exercice ID:",
                draggingRow.dataset.exerciceId
            );
            console.log("Target exercice ID:", this.dataset.exerciceId);

            console.log("=== EXECUTING DROP ===");
            console.log(
                "Drop de transaction:",
                draggingRow.dataset.id,
                "sur",
                this.dataset.id
            );

            const oldExerciceId = draggingRow.dataset.exerciceId;
            const newExerciceId = this.dataset.exerciceId;

            // Déterminer la position d'insertion
            const rect = this.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;

            if (e.clientY < midpoint) {
                // Insérer avant
                console.log("Insertion avant");
                tbody.insertBefore(draggingRow, this);
            } else {
                // Insérer après
                console.log("Insertion après");
                tbody.insertBefore(draggingRow, this.nextSibling);
            }

            // Si changement d'exercice, mettre à jour l'attribut data-exercice-id
            if (oldExerciceId !== newExerciceId) {
                console.log(
                    "Changement d'exercice de",
                    oldExerciceId,
                    "vers",
                    newExerciceId
                );
                draggingRow.dataset.exerciceId = newExerciceId;
            }

            // Sauvegarder toutes les transactions affectées
            saveAllTransactionChanges();
        } else {
            console.log("Pas de dragging row ou même élément");
        }
    });
}

function updateTransactionExerciseVisually(transactionRow, newExerciceId) {
    // Cette fonction pourrait être étendue pour mettre à jour visuellement
    // d'autres éléments liés à l'exercice dans la ligne de transaction
    console.log(
        `Transaction ${transactionRow.dataset.id} déplacée vers exercice ${newExerciceId}`
    );
}

function saveAllTransactionChanges() {
    const tbody = document.getElementById("transactions-tbody");
    if (!tbody) {
        console.error("Tbody des transactions non trouvé pour la sauvegarde");
        return;
    }

    const allTransactionRows = tbody.querySelectorAll(".transaction-row");
    const transactionsData = [];

    // Regrouper les transactions par exercice pour calculer les ordres corrects
    const transactionsByExercice = {};

    allTransactionRows.forEach((row) => {
        const exerciceId = row.dataset.exerciceId;
        if (!transactionsByExercice[exerciceId]) {
            transactionsByExercice[exerciceId] = [];
        }
        transactionsByExercice[exerciceId].push(row);
    });

    // Pour chaque exercice, recalculer les ordres
    Object.keys(transactionsByExercice).forEach((exerciceId) => {
        const exerciceRows = transactionsByExercice[exerciceId];
        exerciceRows.forEach((row, index) => {
            const transactionId = parseInt(row.dataset.id);
            const newOrder = index + 1;
            const currentExerciceId = parseInt(row.dataset.exerciceId);

            transactionsData.push({
                id: transactionId,
                order: newOrder,
                exercice_id: currentExerciceId,
            });

            // Mettre à jour visuellement le numéro d'ordre
            const orderCell = row.querySelector('[data-field="numero_ordre"]');
            if (orderCell) {
                const newContent = orderCell.innerHTML.replace(
                    /\d+$/,
                    newOrder
                );
                orderCell.innerHTML = newContent;
            }
        });
    });

    if (transactionsData.length === 0) {
        return;
    }

    // ===== SAUVEGARDE DIRECTE SUR SERVEUR =====
    // Utilisation de l'API bulk-update-order qui devrait fonctionner

    console.log("💾 Sauvegarde des changements sur le serveur...");
    showToast("💾 Sauvegarde en cours...", "success");

    console.log("📤 Données envoyées:", { transactions: transactionsData });

    fetch(TRANSACTION_REORDER_URL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
            transactions: transactionsData,
        }),
    })
        .then((response) => {
            console.log(
                "📥 Réponse reçue:",
                response.status,
                response.statusText
            );

            // Vérifier le statut avant de parser le JSON
            if (!response.ok) {
                return response.text().then((text) => {
                    console.error(
                        "❌ Réponse non-JSON (erreur " + response.status + "):",
                        text
                    );
                    throw new Error(
                        `Erreur serveur ${response.status}: ${text.substring(
                            0,
                            200
                        )}`
                    );
                });
            }

            return response.text().then((text) => {
                console.log("📄 Réponse brute:", text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("❌ Erreur parsing JSON:", e);
                    console.error("📄 Texte reçu:", text.substring(0, 500));
                    throw new Error(
                        "Réponse serveur invalide: " + text.substring(0, 100)
                    );
                }
            });
        })
        .then((data) => {
            console.log("✅ Données parsées:", data);

            if (data.success) {
                showToast(
                    "🎉 Changements sauvegardés avec succès !",
                    "success"
                );
                console.log("✅ Sauvegarde réussie:", data);

                // Mettre à jour les numéros d'ordre visuellement au lieu de recharger
                updateTransactionOrdersInDOM();

                showToast("✅ Ordre des transactions mis à jour", "success");
            } else {
                console.error("❌ Erreur serveur:", data);
                showToast(
                    "❌ Erreur: " +
                        (data.error ||
                            data.message ||
                            "Échec de la sauvegarde"),
                    "error"
                );

                // En cas d'échec, activer le mode offline comme fallback
                activateOfflineMode(transactionsData);
            }
        })
        .catch((error) => {
            console.error("❌ Erreur de connexion:", error);
            showToast("❌ " + error.message, "error");

            // En cas d'erreur de connexion, activer le mode offline
            activateOfflineMode(transactionsData);
        });
}

function showToast(message, type) {
    // Créer un toast amélioré
    const toast = document.createElement("div");
    const alertType = type === "success" ? "success" : "danger";
    toast.className =
        "alert alert-" +
        alertType +
        " position-fixed alert-dismissible fade show";
    toast.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px; font-size: 16px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);";

    const iconClass =
        type === "success" ? "check-circle" : "exclamation-triangle";
    toast.innerHTML =
        '<i class="fas fa-' +
        iconClass +
        ' me-2"></i>' +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

    document.body.appendChild(toast);

    // Supprimer après 5 secondes (plus long pour laisser le temps de lire)
    setTimeout(
        () => {
            if (toast.parentNode) {
                toast.remove();
            }
        },
        type === "success" ? 4000 : 8000
    ); // Erreurs restent plus longtemps
}

// Version de compatibilité pour la fonction existante
function saveTransactionOrder(exerciceId) {
    // Version simplifiée qui appelle la fonction complète
    saveAllTransactionChanges();
}

// ===== FONCTIONS MODE OFFLINE (FALLBACK) =====

function activateOfflineMode(transactionsData) {
    console.log("🔄 Activation du mode offline (fallback)...");

    // Clé de stockage unique pour cette page
    const storageKey = "drag_drop_changes_" + window.location.pathname;

    // Récupérer les changements existants
    let storedChanges = JSON.parse(localStorage.getItem(storageKey) || "{}");

    // Enregistrer tous les nouveaux changements
    transactionsData.forEach((transactionData) => {
        storedChanges[transactionData.id] = {
            order: transactionData.order,
            exercice_id: transactionData.exercice_id,
            timestamp: Date.now(),
        };
    });

    // Sauvegarder dans localStorage
    localStorage.setItem(storageKey, JSON.stringify(storedChanges));

    const totalStoredChanges = Object.keys(storedChanges).length;

    showToast(
        `💽 ${transactionsData.length} changements sauvegardés localement (total: ${totalStoredChanges} en attente)`,
        "success"
    );
    console.log("💽 Changements stockés localement:", storedChanges);

    // Créer un indicateur persistant de mode offline
    createOfflineIndicator(totalStoredChanges);
}

function createOfflineIndicator(changesCount) {
    // Ne créer qu'un seul indicateur
    let indicator = document.getElementById("offline-indicator");
    if (!indicator) {
        indicator = document.createElement("div");
        indicator.id = "offline-indicator";
        document.body.appendChild(indicator);
    }

    indicator.innerHTML = `
        💽 Mode Offline - ${changesCount} changement(s) en attente
        <button onclick="clearOfflineChanges()" style="margin-left: 10px; background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 2px 8px; border-radius: 3px; cursor: pointer;">
            Effacer
        </button>
        <button onclick="attemptServerSync('${
            localStorage.key(0) ||
            "drag_drop_changes_" + window.location.pathname
        }')" style="margin-left: 5px; background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 2px 8px; border-radius: 3px; cursor: pointer;">
            Synchroniser
        </button>
    `;
    indicator.style.cssText = `
        position: fixed; 
        top: 10px; 
        left: 50%; 
        transform: translateX(-50%); 
        background: #17a2b8; 
        color: white; 
        padding: 12px 20px; 
        border-radius: 6px; 
        z-index: 10000; 
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-size: 14px;
    `;
}

function clearOfflineChanges() {
    const storageKey = "drag_drop_changes_" + window.location.pathname;
    localStorage.removeItem(storageKey);

    const indicator = document.getElementById("offline-indicator");
    if (indicator) {
        indicator.remove();
    }

    showToast("🗑️ Changements locaux effacés", "success");

    // Mode offline activé - pas besoin de recharger
    console.log(
        "🔄 Mode offline activé - les changements seront synchronisés plus tard"
    );
}

async function attemptServerSync(storageKey) {
    const storedChanges = JSON.parse(localStorage.getItem(storageKey) || "{}");
    const changeIds = Object.keys(storedChanges);

    if (changeIds.length === 0) {
        showToast("Aucun changement à synchroniser", "success");
        return;
    }

    showToast(
        `🔄 Tentative de synchronisation de ${changeIds.length} changements...`,
        "success"
    );

    let syncCount = 0;
    const syncErrors = [];

    for (const transactionId of changeIds) {
        const change = storedChanges[transactionId];

        try {
            // Tenter la sauvegarde du numéro d'ordre
            const orderResponse = await fetch(
                `/transaction/${transactionId}/update-field`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `field=numero_ordre&value=${change.order}`,
                }
            );

            if (orderResponse.ok) {
                syncCount++;

                // Si changement d'exercice, le synchroniser aussi
                if (change.exercice_id) {
                    await fetch(`/transaction/${transactionId}/update-field`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: `field=exercice&value=${change.exercice_id}`,
                    });
                }

                // Supprimer le changement synchronisé
                delete storedChanges[transactionId];
            } else {
                syncErrors.push(
                    `Transaction ${transactionId}: ${orderResponse.status}`
                );
            }
        } catch (error) {
            syncErrors.push(`Transaction ${transactionId}: ${error.message}`);
        }
    }

    // Mettre à jour le stockage
    localStorage.setItem(storageKey, JSON.stringify(storedChanges));

    // Afficher le résultat
    const remainingChanges = Object.keys(storedChanges).length;

    if (syncCount > 0) {
        showToast(
            `✅ ${syncCount} changements synchronisés avec succès !`,
            "success"
        );

        if (remainingChanges === 0) {
            // Tout synchronisé - supprimer l'indicateur
            const indicator = document.getElementById("offline-indicator");
            if (indicator) {
                indicator.remove();
            }

            // Synchronisation réussie - mettre à jour visuellement
            updateTransactionOrdersInDOM();
            showToast("✅ Synchronisation terminée", "success");
        } else {
            // Mettre à jour l'indicateur
            createOfflineIndicator(remainingChanges);
        }
    } else {
        showToast(
            `❌ Synchronisation échouée - serveur toujours indisponible`,
            "error"
        );
    }

    if (syncErrors.length > 0) {
        console.warn("Erreurs de synchronisation:", syncErrors);
    }
}

function applyStoredChanges() {
    const storageKey = "drag_drop_changes_" + window.location.pathname;
    const storedChanges = JSON.parse(localStorage.getItem(storageKey) || "{}");
    const changeIds = Object.keys(storedChanges);

    if (changeIds.length === 0) {
        return; // Aucun changement stocké
    }

    console.log(`🔄 Application de ${changeIds.length} changements stockés...`);

    // Appliquer chaque changement stocké à l'interface
    changeIds.forEach((transactionId) => {
        const change = storedChanges[transactionId];
        const row = document.querySelector(`[data-id="${transactionId}"]`);

        if (row) {
            // Mettre à jour le numéro d'ordre visuellement
            const orderCell = row.querySelector('[data-field="numero_ordre"]');
            if (orderCell) {
                const newContent = orderCell.innerHTML.replace(
                    /\d+$/,
                    change.order
                );
                orderCell.innerHTML = newContent;
            }

            // Mettre à jour l'exercice si nécessaire
            if (
                change.exercice_id &&
                row.dataset.exerciceId !== change.exercice_id.toString()
            ) {
                row.dataset.exerciceId = change.exercice_id;
                // Ici, on pourrait aussi déplacer visuellement la ligne si nécessaire
            }
        }
    });

    // Afficher l'indicateur
    createOfflineIndicator(changeIds.length);

    showToast(`🔄 ${changeIds.length} changements locaux appliqués`, "success");
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
    // Attendre un peu que tout soit rendu
    setTimeout(() => {
        // Appliquer les changements stockés AVANT d'initialiser le drag & drop
        applyStoredChanges();

        // Puis initialiser le drag & drop
        initTransactionDragDrop();
    }, 200);
});
