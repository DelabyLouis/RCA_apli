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
    console.log("=== DEBUT initTransactionDragDrop ===");
    const tbody = document.getElementById("transactions-tbody");
    console.log("Tbody trouvé:", tbody);

    if (!tbody) {
        console.error("Tbody des transactions non trouvé !");
        console.log(
            "Éléments avec id transactions-tbody:",
            document.querySelectorAll("#transactions-tbody")
        );
        console.log("Tous les tbody:", document.querySelectorAll("tbody"));
        return;
    }

    const transactionRows = tbody.querySelectorAll(".transaction-row");
    console.log(
        "Nombre de lignes de transactions trouvées:",
        transactionRows.length
    );

    if (transactionRows.length === 0) {
        console.log("Aucune ligne .transaction-row trouvée dans le tbody");
        console.log(
            "Toutes les lignes dans tbody:",
            tbody.querySelectorAll("tr")
        );
        return;
    }

    transactionRows.forEach((row, index) => {
        console.log("--- Transaction " + (index + 1) + " ---");
        console.log("Row:", row);
        console.log("Dataset:", row.dataset);

        const dragHandle = row.querySelector(".drag-handle");
        console.log("Drag handle:", dragHandle);

        if (!dragHandle) {
            console.log(
                "Pas de drag-handle pour la transaction:",
                row.dataset.id
            );
            console.log(
                "Contenu de la première cellule:",
                row.querySelector("td")?.innerHTML
            );
            return; // Pas de drag pour les exercices clôturés
        }

        console.log("Initialisation drag pour transaction:", row.dataset.id);
        row.draggable = true;
        row.setAttribute("draggable", "true");
        console.log("Draggable set to:", row.getAttribute("draggable"));

        // Test simple : ajouter une bordure colorée pour voir les éléments draggables
        row.style.border = "2px solid green";

        // Test de click sur le drag handle pour vérifier l'interactivité
        if (dragHandle) {
            dragHandle.addEventListener("click", function (e) {
                console.log("=== CLICK TEST ===");
                console.log(
                    "Click sur drag handle de transaction:",
                    row.dataset.id
                );
                alert("Click détecté ! Drag handle fonctionne.");
                e.preventDefault();
            });

            // Test de survol
            dragHandle.addEventListener("mouseenter", function () {
                console.log("Survol du drag handle:", row.dataset.id);
                dragHandle.style.color = "red";
            });

            dragHandle.addEventListener("mouseleave", function () {
                dragHandle.style.color = "";
            });
        }

        // Event listeners
        addDragEventListeners(row, tbody);
    });
}

function addDragEventListeners(row, tbody) {
    row.addEventListener("dragstart", function (e) {
        console.log("=== DRAG START ===");
        console.log("Transaction ID:", this.dataset.id);
        console.log("Exercice ID:", this.dataset.exerciceId);
        console.log("Element:", this);
        this.classList.add("dragging");
        e.dataTransfer.setData("text/plain", this.dataset.id);
        e.dataTransfer.effectAllowed = "move";
    });

    row.addEventListener("dragend", function (e) {
        console.log("=== DRAG END ===");
        console.log("Transaction ID:", this.dataset.id);
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

function saveAllTransactionChanges() {
    const tbody = document.getElementById("transactions-tbody");
    if (!tbody) {
        console.error("Tbody des transactions non trouvé pour la sauvegarde");
        return;
    }

    const allTransactionRows = tbody.querySelectorAll(".transaction-row");
    const transactionsData = [];

    console.log("Sauvegarde de tous les changements de transactions");
    console.log("Nombre total de transactions:", allTransactionRows.length);

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
        console.log("Aucune transaction trouvée");
        return;
    }

    console.log("Données à envoyer:", transactionsData);

    // Envoyer au serveur
    fetch(TRANSACTION_REORDER_URL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            transactions: transactionsData,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showToast("Ordre des transactions mis à jour", "success");
                // Recharger la page pour voir tous les changements
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                console.error("Erreur:", data.error);
                showToast(
                    "Erreur lors de la mise à jour: " + data.error,
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Erreur:", error);
            showToast("Erreur de communication avec le serveur", "error");
        });
}

function showToast(message, type) {
    // Créer un toast simple
    const toast = document.createElement("div");
    const alertType = type === "success" ? "success" : "danger";
    toast.className = "alert alert-" + alertType + " position-fixed";
    toast.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";

    const iconClass = type === "success" ? "check" : "exclamation-triangle";
    toast.innerHTML = '<i class="fas fa-' + iconClass + ' me-2"></i>' + message;

    document.body.appendChild(toast);

    // Supprimer après 3 secondes
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Version de compatibilité pour la fonction existante
function saveTransactionOrder(exerciceId) {
    // Version simplifiée qui appelle la fonction complète
    saveAllTransactionChanges();
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
    console.log("=== INITIALISATION DRAG & DROP ===");
    console.log("DOM chargé, début initialisation drag & drop...");

    // Attendre un peu que tout soit rendu
    setTimeout(() => {
        console.log("Initialisation du drag & drop des transactions...");
        initTransactionDragDrop();
    }, 200);
});
