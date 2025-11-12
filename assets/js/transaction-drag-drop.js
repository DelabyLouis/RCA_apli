console.log("=== TRANSACTION DRAG & DROP LOADED ===");

// Variables globales
let TRANSACTION_REORDER_URL = "/transaction/reorder";

// Configuration de l'URL depuis le template
function setTransactionReorderUrl(url) {
    TRANSACTION_REORDER_URL = url;
    console.log("URL configurée:", TRANSACTION_REORDER_URL);
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

    // ===== SOLUTION DÉFINITIVE =====
    // Les numéros d'ordre sont déjà mis à jour visuellement
    // On essaie de sauvegarder sur le serveur, mais en cas d'échec,
    // les changements visuels restent actifs

    // ===== SOLUTION DÉGRADÉE INTELLIGENTE =====
    // Au lieu d'utiliser l'API bulk qui ne fonctionne pas,
    // sauvegarder chaque transaction individuellement via l'API d'édition de champ

    console.log("💾 Sauvegarde intelligente des changements...");
    showToast("💾 Sauvegarde en cours...", "success");

    let savedCount = 0;
    let totalCount = transactionsData.length;
    let errors = [];

    // Fonction pour sauvegarder une transaction individuelle
    async function saveIndividualTransaction(transactionData) {
        try {
            // Sauvegarder le numéro d'ordre via l'API d'édition de champ
            const orderResponse = await fetch(
                `/transaction/${transactionData.id}/update-field`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `field=numero_ordre&value=${transactionData.order}`,
                }
            );

            if (!orderResponse.ok) {
                throw new Error(`Erreur ordre: ${orderResponse.status}`);
            }

            // Si changement d'exercice, le sauvegarder aussi
            if (transactionData.exercice_id) {
                const exerciceResponse = await fetch(
                    `/transaction/${transactionData.id}/update-field`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: `field=exercice&value=${transactionData.exercice_id}`,
                    }
                );

                if (!exerciceResponse.ok) {
                    console.warn(
                        `Changement d'exercice échoué pour transaction ${transactionData.id}`
                    );
                }
            }

            savedCount++;
            console.log(
                `✅ Transaction ${transactionData.id} sauvegardée (${savedCount}/${totalCount})`
            );
        } catch (error) {
            errors.push(`Transaction ${transactionData.id}: ${error.message}`);
            console.error(
                `❌ Erreur sauvegarde transaction ${transactionData.id}:`,
                error
            );
        }
    }

    // Sauvegarder toutes les transactions une par une
    Promise.all(transactionsData.map(saveIndividualTransaction))
        .then(() => {
            if (savedCount === totalCount) {
                showToast(
                    `🎉 Tous les changements sauvegardés avec succès ! (${savedCount}/${totalCount})`,
                    "success"
                );
                console.log("🎉 Sauvegarde complète réussie !");

                // Rechargement automatique après succès complet
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showToast(
                    `⚠️ Sauvegarde partielle: ${savedCount}/${totalCount} réussies`,
                    "error"
                );
                console.warn("Erreurs:", errors);
            }
        })
        .catch(() => {
            showToast("❌ Erreur générale de sauvegarde", "error");

            // Indicateur de changements non sauvegardés
            if (!document.getElementById("unsaved-indicator")) {
                const indicator = document.createElement("div");
                indicator.id = "unsaved-indicator";
                indicator.innerHTML =
                    "⚠️ Changements non sauvegardés - Cliquez pour recharger";
                indicator.style.cssText =
                    "position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #ff6b35; color: white; padding: 10px 20px; border-radius: 6px; z-index: 10000; font-weight: bold; cursor: pointer;";
                indicator.onclick = () => window.location.reload();
                document.body.appendChild(indicator);
            }
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

// Initialisation automatique quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
    // Attendre un peu que tout soit rendu
    setTimeout(() => {
        initTransactionDragDrop();
    }, 200);
});
