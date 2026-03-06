// ===== TRANSACTION PAGE SCRIPTS - STYLE EXERCICES =====
// Inspiré de la structure propre des exercices

console.log("Transaction page scripts loaded");

// Fonction principale d'initialisation (comme pour les exercices)
function initTransactionPage() {
    console.log("Initialisation page transactions (style exercices)");

    // Initialiser toutes les fonctionnalités
    initInlineEditing();
    initDeleteButtons();
    initExerciceCollapse();
    autoCollapseClosedExercices();
    initFilters();
}

// Fonction pour afficher une notification
function showNotification(message, type) {
    if (typeof type === "undefined") type = "danger";
    const notificationsContainer = document.getElementById("notifications");

    if (!notificationsContainer) return;

    const notification = document.createElement("div");
    notification.className =
        "alert alert-" + type + " alert-dismissible fade show";
    const statusText = type === "success" ? "✅ Succès:" : "❌ Erreur:";
    notification.innerHTML =
        "<strong>" +
        statusText +
        "</strong> " +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

    notificationsContainer.appendChild(notification);

    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Gestion de l'édition inline
function initInlineEditing() {
    console.log(
        "Initialisation édition inline pour transactions (page principale)"
    );

    // Récupérer les données depuis la variable globale définie dans le template
    const selectData = window.transactionSelectData || {
        exercices: [],
        types: [],
        modes: [],
    };
    const exercices = selectData.exercices;
    const typesTransaction = selectData.types;
    const modesPaiement = selectData.modes;

    // Gestion de l'édition inline simple
    document.querySelectorAll(".editable-field").forEach(function (element) {
        element.addEventListener("click", function () {
            if (this.querySelector("input") || this.querySelector("select")) {
                return;
            }

            const field = this.dataset.field;
            const currentValue = this.textContent.trim();
            const transactionId = this.closest("tr").dataset.id;

            console.log(
                "Édition de:",
                field,
                "pour transaction:",
                transactionId
            );

            if (field === "date_transaction") {
                let isoValue = "";
                if (currentValue && currentValue !== "") {
                    const parts = currentValue.split("/");
                    if (parts.length === 3) {
                        isoValue =
                            parts[2] +
                            "-" +
                            parts[1].padStart(2, "0") +
                            "-" +
                            parts[0].padStart(2, "0");
                    }
                }

                this.innerHTML =
                    '<input type="date" class="form-control form-control-sm" value="' +
                    isoValue +
                    '" style="min-width: 150px;">';
            } else if (field === "exercice") {
                let selectHtml =
                    '<select class="form-control form-control-sm" style="min-width: 150px;">';
                selectHtml += '<option value="">Aucun exercice</option>';
                exercices.forEach((exercice) => {
                    selectHtml +=
                        '<option value="' +
                        exercice.id +
                        '">' +
                        exercice.libelle +
                        "</option>";
                });
                selectHtml += "</select>";
                this.innerHTML = selectHtml;

                // Sélectionner la valeur actuelle
                const select = this.querySelector("select");
                const currentExerciceText = currentValue;
                for (let option of select.options) {
                    if (option.text === currentExerciceText) {
                        option.selected = true;
                        break;
                    }
                }
            } else if (field === "type_transaction") {
                let selectHtml =
                    '<select class="form-control form-control-sm" style="min-width: 150px;">';
                selectHtml += '<option value="">Aucun type</option>';
                typesTransaction.forEach((type) => {
                    selectHtml +=
                        '<option value="' +
                        type.id +
                        '">' +
                        type.libelle +
                        "</option>";
                });
                selectHtml += "</select>";
                this.innerHTML = selectHtml;

                // Sélectionner la valeur actuelle
                const select2 = this.querySelector("select");
                const currentTypeText = currentValue;
                for (let option of select2.options) {
                    if (option.text === currentTypeText) {
                        option.selected = true;
                        break;
                    }
                }
            } else if (field === "mode_de_paiement") {
                let selectHtml =
                    '<select class="form-control form-control-sm" style="min-width: 150px;">';
                selectHtml +=
                    '<option value="">Aucun mode de paiement</option>';
                modesPaiement.forEach((mode) => {
                    selectHtml +=
                        '<option value="' +
                        mode.id +
                        '">' +
                        mode.libelle +
                        "</option>";
                });
                selectHtml += "</select>";
                this.innerHTML = selectHtml;

                // Sélectionner la valeur actuelle
                const select3 = this.querySelector("select");
                const currentModeText = currentValue;
                for (let option of select3.options) {
                    if (option.text === currentModeText) {
                        option.selected = true;
                        break;
                    }
                }
            } else {
                this.innerHTML =
                    '<input type="text" class="form-control form-control-sm" value="' +
                    currentValue +
                    '" style="min-width: 150px;">';
            }

            const input = this.querySelector("input, select");
            if (input) {
                input.focus();
                if (input.type !== "date" && input.tagName === "INPUT") {
                    input.select();
                }

                // Sauvegarder au blur ou Entrée
                function saveChanges() {
                    const newValue = input.value;
                    if (newValue !== currentValue) {
                        console.log("Sauvegarde:", field, "=", newValue);

                        // Préparer les données
                        const formData = new FormData();
                        formData.append("field", field);
                        formData.append("value", newValue);

                        // URL sans exercice_id pour la page principale
                        const url =
                            "/transaction/" + transactionId + "/update-field";

                        fetch(url, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    element.textContent =
                                        data.display_value || newValue;
                                    element.classList.add("text-success");
                                    setTimeout(
                                        () =>
                                            element.classList.remove(
                                                "text-success"
                                            ),
                                        1000
                                    );
                                } else {
                                    alert(
                                        "Erreur: " +
                                            (data.error ||
                                                "Impossible de sauvegarder")
                                    );
                                    element.textContent = currentValue;
                                }
                            })
                            .catch((error) => {
                                console.error("Erreur:", error);
                                element.textContent = currentValue;
                            });
                    } else {
                        element.textContent = currentValue;
                    }
                }

                input.addEventListener("blur", saveChanges);
                input.addEventListener("keydown", function (e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        saveChanges();
                    } else if (e.key === "Escape") {
                        element.textContent = currentValue;
                    }
                });
            }
        });
    });
}

// Gestion de la suppression
function initDeleteButtons() {
    document.querySelectorAll(".delete-btn").forEach(function (button) {
        button.addEventListener("click", function (e) {
            e.preventDefault();

            if (
                !confirm(
                    "Êtes-vous sûr de vouloir supprimer cette transaction ?"
                )
            ) {
                return;
            }

            const transactionId = this.dataset.id;
            const row = this.closest("tr");

            const url = "/transaction/" + transactionId + "/delete-ajax";

            fetch(url, {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showNotification(
                            "Transaction supprimée avec succès !",
                            "success"
                        );
                        row.remove();
                        // Réafficher "Aucune transaction" si plus de lignes
                        const tbody = document.querySelector("tbody");
                        if (tbody.children.length === 0) {
                            location.reload(); // Recharger pour afficher le message "aucune transaction"
                        }
                    } else {
                        showNotification(
                            data.error ||
                                "Impossible de supprimer la transaction",
                            "danger"
                        );
                    }
                })
                .catch((error) => {
                    console.error("Erreur:", error);
                    showNotification(
                        "Erreur de connexion ou serveur indisponible",
                        "danger"
                    );
                });
        });
    });
}

// Gestion du collapse/expand des exercices
function initExerciceCollapse() {
    document
        .querySelectorAll(".exercice-separator")
        .forEach(function (separator) {
            separator.addEventListener("click", function () {
                const exerciceId = this.dataset.exerciceId;
                const transactionRows = document.querySelectorAll(
                    `tr.transaction-row[data-exercice-id="${exerciceId}"]`
                );
                const montantCollapsed = this.querySelector(
                    ".exercice-montant-collapsed"
                );
                const chevron = this.querySelector(".collapse-indicator i");
                const isCollapsed = this.classList.contains("collapsed");

                if (isCollapsed) {
                    // Dérouler - afficher toutes les transactions de cet exercice
                    transactionRows.forEach(function (row) {
                        row.style.display = "";
                    });
                    this.classList.remove("collapsed");
                    // Masquer le montant final
                    if (montantCollapsed) {
                        montantCollapsed.style.display = "none";
                    }
                    // Changer l'icône du chevron
                    if (chevron) {
                        chevron.className = "fas fa-chevron-up";
                    }
                } else {
                    // Enrouler - masquer toutes les transactions de cet exercice
                    transactionRows.forEach(function (row) {
                        row.style.display = "none";
                    });
                    this.classList.add("collapsed");
                    // Afficher le montant final
                    if (montantCollapsed) {
                        montantCollapsed.style.display = "block";
                    }
                    // Changer l'icône du chevron
                    if (chevron) {
                        chevron.className = "fas fa-chevron-down";
                    }
                }

                console.log(
                    "Exercice " +
                        exerciceId +
                        " " +
                        (isCollapsed ? "déroulé" : "enroulé")
                );
            });
        });
}

// Auto-collapse des exercices clôturés
function autoCollapseClosedExercices() {
    document
        .querySelectorAll(".exercice-separator")
        .forEach(function (separator) {
            const exerciceId = separator.dataset.exerciceId;
            // Vérifier si l'exercice est clôturé (présence du badge "CLÔTURÉ")
            const isClosedExercice =
                separator.querySelector(".badge.bg-danger");

            if (isClosedExercice) {
                // Enrouler cet exercice clôturé
                const transactionRows = document.querySelectorAll(
                    `tr.transaction-row[data-exercice-id="${exerciceId}"]`
                );
                const montantCollapsed = separator.querySelector(
                    ".exercice-montant-collapsed"
                );
                const chevron = separator.querySelector(
                    ".collapse-indicator i"
                );

                transactionRows.forEach(function (row) {
                    row.style.display = "none";
                });
                separator.classList.add("collapsed");

                if (montantCollapsed) {
                    montantCollapsed.style.display = "block";
                }

                // Changer l'icône du chevron pour indiquer que c'est enroulé
                if (chevron) {
                    chevron.className = "fas fa-chevron-down";
                }
            }
        });
}

// Gestion des filtres
function initFilters() {
    console.log("Initialisation des filtres");

    // Bouton appliquer les filtres
    const applyFiltersBtn = document.getElementById("apply-filters");
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener("click", applyFilters);
    }

    // Bouton effacer les filtres
    const clearFiltersBtn = document.getElementById("clear-filters");
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener("click", clearFilters);
    }

    // Gestion du dropdown tiers
    initTiersDropdown();

    // Mettre à jour le texte du dropdown tiers au chargement
    updateTiersDropdownText();
}

function initTiersDropdown() {
    // Appliquer le filtre tiers
    const applyTiersBtn = document.getElementById("apply-tiers-filter");
    if (applyTiersBtn) {
        applyTiersBtn.addEventListener("click", function () {
            // Fermer le dropdown
            const dropdown = bootstrap.Dropdown.getInstance(
                document.getElementById("filter-tiers-dropdown")
            );
            if (dropdown) {
                dropdown.hide();
            }
            updateTiersDropdownText();
        });
    }

    // Tout décocher
    const clearTiersBtn = document.getElementById("clear-tiers-selection");
    if (clearTiersBtn) {
        clearTiersBtn.addEventListener("click", function () {
            document
                .querySelectorAll(".tiers-checkbox")
                .forEach(function (checkbox) {
                    checkbox.checked = false;
                });
            updateTiersDropdownText();
        });
    }

    // Mettre à jour le texte quand on coche/décoche
    document.querySelectorAll(".tiers-checkbox").forEach(function (checkbox) {
        checkbox.addEventListener("change", updateTiersDropdownText);
    });
}

function updateTiersDropdownText() {
    const selectedTiers = document.querySelectorAll(".tiers-checkbox:checked");
    const textElement = document.getElementById("selected-tiers-text");

    if (!textElement) return;

    if (selectedTiers.length === 0) {
        textElement.textContent = "Sélectionner des tiers...";
    } else if (selectedTiers.length === 1) {
        const label = selectedTiers[0]
            .closest(".form-check")
            .querySelector(".form-check-label");
        textElement.textContent = label
            ? label.textContent.trim()
            : "1 tiers sélectionné";
    } else {
        textElement.textContent = selectedTiers.length + " tiers sélectionnés";
    }
}

function applyFilters() {
    console.log("Application des filtres");

    // Récupérer les valeurs des filtres
    const libelle = document.getElementById("filter-libelle").value.trim();
    const typeMontant = document.getElementById("filter-type-montant").value;
    const montantMin = document
        .getElementById("filter-montant-min")
        .value.trim();
    const montantMax = document
        .getElementById("filter-montant-max")
        .value.trim();
    const dateMin = document.getElementById("filter-date-min").value;
    const dateMax = document.getElementById("filter-date-max").value;

    // Récupérer les tiers sélectionnés
    const selectedTiers = [];
    document
        .querySelectorAll(".tiers-checkbox:checked")
        .forEach(function (checkbox) {
            selectedTiers.push(checkbox.value);
        });

    // Construire l'URL avec les paramètres
    const url = new URL(window.location);

    // Supprimer les anciens paramètres de filtre
    url.searchParams.delete("libelle");
    url.searchParams.delete("tiers");
    url.searchParams.delete("type_montant");
    url.searchParams.delete("montant_min");
    url.searchParams.delete("montant_max");
    url.searchParams.delete("date_min");
    url.searchParams.delete("date_max");

    // Ajouter les nouveaux paramètres si ils ont des valeurs
    if (libelle) {
        url.searchParams.set("libelle", libelle);
    }

    selectedTiers.forEach(function (tier) {
        url.searchParams.append("tiers[]", tier);
    });

    if (typeMontant) {
        url.searchParams.set("type_montant", typeMontant);
    }

    if (montantMin) {
        url.searchParams.set("montant_min", montantMin);
    }

    if (montantMax) {
        url.searchParams.set("montant_max", montantMax);
    }

    if (dateMin) {
        url.searchParams.set("date_min", dateMin);
    }

    if (dateMax) {
        url.searchParams.set("date_max", dateMax);
    }

    // Rediriger vers l'URL filtrée
    window.location.href = url.toString();
}

function clearFilters() {
    console.log("Effacement des filtres");

    // Vider les champs
    document.getElementById("filter-libelle").value = "";
    document.getElementById("filter-type-montant").value = "";
    document.getElementById("filter-montant-min").value = "";
    document.getElementById("filter-montant-max").value = "";
    document.getElementById("filter-date-min").value = "";
    document.getElementById("filter-date-max").value = "";

    // Décocher tous les tiers
    document.querySelectorAll(".tiers-checkbox").forEach(function (checkbox) {
        checkbox.checked = false;
    });
    updateTiersDropdownText();

    // Rediriger vers l'URL sans paramètres de filtre
    const url = new URL(window.location);
    url.searchParams.delete("libelle");
    url.searchParams.delete("tiers");
    url.searchParams.delete("type_montant");
    url.searchParams.delete("montant_min");
    url.searchParams.delete("montant_max");
    url.searchParams.delete("date_min");
    url.searchParams.delete("date_max");

    window.location.href = url.toString();
}

// Initialisation globale
document.addEventListener("DOMContentLoaded", function () {
    initTransactionPage();
});
