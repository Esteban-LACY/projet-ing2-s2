<td>
                        <div class="d-flex align-items-center">
                            <img src="${utilisateur.photo_profil || '/assets/img/placeholders/profil.jpg'}" alt="${utilisateur.prenom} ${utilisateur.nom}" class="rounded-circle me-2" width="32" height="32">
                            ${utilisateur.prenom} ${utilisateur.nom}
                        </div>
                    </td>
                    <td>${utilisateur.email}</td>
                    <td>${utilisateur.telephone || '-'}</td>
                    <td>
                        <span class="badge ${utilisateur.est_verifie ? 'bg-success' : 'bg-danger'}">
                            ${utilisateur.est_verifie ? 'Oui' : 'Non'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${utilisateur.est_admin ? 'bg-primary' : 'bg-secondary'}">
                            ${utilisateur.est_admin ? 'Oui' : 'Non'}
                        </span>
                    </td>
                    <td>${dateInscription}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary btn-details" data-id="${utilisateur.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        listeUtilisateurs.innerHTML = html;
        
        // Ajouter les événements sur les boutons de détails
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                ouvrirModalUtilisateur(id);
            });
        });
        
        // Construire la pagination
        construirePagination(page, totalPages);
        
        // Afficher le nombre total d'utilisateurs
        const debut = (page - 1) * limite + 1;
        const fin = Math.min(page * limite, total);
        
        document.querySelector('.card-body').insertAdjacentHTML('afterbegin', `
            <p class="text-muted mb-3">Affichage de ${debut} à ${fin} sur ${total} utilisateurs</p>
        `);
    }
    
    /**
     * Construit la pagination
     */
    function construirePagination(pageCourante, totalPages) {
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Bouton précédent
        html += `
            <li class="page-item ${pageCourante === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pageCourante - 1}" aria-label="Précédent">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 ||                       // Première page
                i === totalPages ||              // Dernière page
                (i >= pageCourante - 1 && i <= pageCourante + 1)  // 1 page avant et après la courante
            ) {
                html += `
                    <li class="page-item ${i === pageCourante ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (
                i === 2 && pageCourante > 3 ||   // Ellipsis après la première page
                i === totalPages - 1 && pageCourante < totalPages - 2  // Ellipsis avant la dernière page
            ) {
                html += '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
            }
        }
        
        // Bouton suivant
        html += `
            <li class="page-item ${pageCourante === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pageCourante + 1}" aria-label="Suivant">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        pagination.innerHTML = html;
        
        // Ajouter les événements sur les liens de pagination
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.parentElement.classList.contains('disabled')) {
                    return;
                }
                
                const page = parseInt(this.getAttribute('data-page'));
                
                if (page !== paramsActuels.page) {
                    paramsActuels.page = page;
                    
                    // Mettre à jour l'URL
                    window.history.pushState({}, '', 'utilisateurs.php?' + new URLSearchParams(paramsActuels).toString());
                    
                    // Recharger les utilisateurs
                    chargerUtilisateurs();
                }
            });
        });
    }
    
    /**
     * Ouvre la modal avec les détails d'un utilisateur
     */
    function ouvrirModalUtilisateur(id) {
        // Afficher l'état de chargement
        modalLoading.style.display = 'block';
        modalContent.style.display = 'none';
        
        // Ouvrir la modal
        modalUtilisateur.show();
        
        // Charger les détails de l'utilisateur
        fetch(`../controllers/admin.php?action=recuperer_utilisateur&id_utilisateur=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    remplirModalUtilisateur(data.data);
                } else {
                    alert('Erreur lors du chargement des données de l\'utilisateur');
                    modalUtilisateur.hide();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors du chargement des données de l\'utilisateur');
                modalUtilisateur.hide();
            });
    }
    
    /**
     * Remplit la modal avec les détails d'un utilisateur
     */
    function remplirModalUtilisateur(data) {
        const utilisateur = data.utilisateur;
        
        // Informations de base
        document.getElementById('user-id').value = utilisateur.id;
        document.getElementById('user-name').textContent = `${utilisateur.prenom} ${utilisateur.nom}`;
        document.getElementById('user-email').textContent = utilisateur.email;
        document.getElementById('user-phone').textContent = utilisateur.telephone || 'Aucun numéro de téléphone';
        
        // Statut
        let statut = '';
        
        if (utilisateur.est_admin) {
            statut += '<span class="badge bg-primary me-1">Administrateur</span>';
        }
        
        if (utilisateur.est_verifie) {
            statut += '<span class="badge bg-success me-1">Email vérifié</span>';
        } else {
            statut += '<span class="badge bg-danger me-1">Email non vérifié</span>';
        }
        
        document.getElementById('user-status').innerHTML = statut;
        
        // Photo de profil
        document.getElementById('user-photo').src = utilisateur.photo_profil || '/assets/img/placeholders/profil.jpg';
        
        // Formulaire
        document.getElementById('nom').value = utilisateur.nom;
        document.getElementById('prenom').value = utilisateur.prenom;
        document.getElementById('email').value = utilisateur.email;
        document.getElementById('telephone').value = utilisateur.telephone || '';
        document.getElementById('est_admin').checked = utilisateur.est_admin;
        document.getElementById('est_verifie').checked = utilisateur.est_verifie;
        
        // Statistiques
        document.getElementById('user-logements').textContent = data.logements.length;
        document.getElementById('user-reservations').textContent = data.reservations_locataire.length;
        
        // Bilan financier
        const bilan = data.bilan_financier.solde;
        document.getElementById('user-bilan').textContent = formaterPrix(bilan);
        document.getElementById('user-bilan').classList.remove('text-success', 'text-danger');
        document.getElementById('user-bilan').classList.add(bilan >= 0 ? 'text-success' : 'text-danger');
        
        // Masquer le chargement et afficher le contenu
        modalLoading.style.display = 'none';
        modalContent.style.display = 'block';
    }
    
    /**
     * Enregistre les modifications d'un utilisateur
     */
    function enregistrerUtilisateur() {
        // Récupérer les données du formulaire
        const formData = new FormData(formUtilisateur);
        
        // Ajouter les valeurs des checkboxes
        formData.set('est_admin', document.getElementById('est_admin').checked ? '1' : '0');
        formData.set('est_verifie', document.getElementById('est_verifie').checked ? '1' : '0');
        
        // Désactiver le bouton pendant la requête
        btnEnregistrer.disabled = true;
        btnEnregistrer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=modifier_utilisateur', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Utilisateur modifié avec succès');
                    modalUtilisateur.hide();
                    chargerUtilisateurs();
                } else {
                    alert(data.message || 'Erreur lors de la modification de l\'utilisateur');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la modification de l\'utilisateur');
            })
            .finally(() => {
                // Réactiver le bouton
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
            });
    }
    
    /**
     * Supprime un utilisateur
     */
    function supprimerUtilisateur() {
        const idUtilisateur = document.getElementById('user-id').value;
        
        // Désactiver le bouton pendant la requête
        btnSupprimer.disabled = true;
        btnSupprimer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=supprimer_utilisateur', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id_utilisateur=${idUtilisateur}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Utilisateur supprimé avec succès');
                    modalUtilisateur.hide();
                    chargerUtilisateurs();
                } else {
                    alert(data.message || 'Erreur lors de la suppression de l\'utilisateur');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la suppression de l\'utilisateur');
            })
            .finally(() => {
                // Réactiver le bouton
                btnSupprimer.disabled = false;
                btnSupprimer.innerHTML = 'Supprimer';
            });
    }
    
    /**
     * Exporte la liste des utilisateurs au format CSV
     */
    function exporterCSV() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        
        params.delete('page');
        params.delete('limite');
        params.append('export', 'csv');
        
        window.location.href = '../controllers/admin.php?action=recuperer_utilisateurs&' + params.toString();
    }
    
    /**
     * Formate un prix
     */
    function formaterPrix(prix) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(prix);
    }
});
</script>

<?php
// Inclure le pied de page
include CHEMIN_VUES . '/admin/footer.php';
?>
