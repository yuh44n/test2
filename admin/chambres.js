 // Fonctions pour gérer les modales
      function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
      }
      
      function openEditModal(id, idType, numero, tarif, statut) {
        document.getElementById('edit_ID_CHAMBRE').value = id;
        document.getElementById('edit_ID_TYPE_CHAMBRE').value = idType;
        document.getElementById('edit_NUMERO_CHAMBRE').value = numero;
        document.getElementById('edit_TARIF').value = tarif;
        document.getElementById('edit_STATUT_CHAMBRE').value = statut;
        document.getElementById('editModal').style.display = 'block';
      }
      
      function openDeleteModal(id) {
        document.getElementById('delete_ID_CHAMBRE').value = id;
        document.getElementById('deleteModal').style.display = 'block';
      }
      
      function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
      }
      
      // Fermer la modale si l'utilisateur clique en dehors
      window.onclick = function(event) {
        if (event.target.className === 'modal') {
          event.target.style.display = 'none';
        }
      }
      
      // Fonction pour filtrer le tableau
      function filterTable() {
        var input, filter, table, tr, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        var statusFilter = document.getElementById("statusFilter").value;
        table = document.getElementById("chambresTable");
        tr = table.getElementsByTagName("tr");
        
        for (i = 1; i < tr.length; i++) {
          var rowVisible = false;
          var td = tr[i].getElementsByTagName("td");
          
          if (td.length > 0) {
            // Vérifier toutes les colonnes sauf les actions et la case à cocher
            for (var j = 2; j < td.length; j++) {
              if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                  rowVisible = true;
                  break;
                }
              }
            }
            
            // Appliquer le filtre de statut
            if (rowVisible && statusFilter !== "") {
              var statusCell = td[6]; // Index de la colonne STATUT_CHAMBRE
              var status = statusCell.textContent || statusCell.innerText;
              if (status.trim() !== statusFilter) {
                rowVisible = false;
              }
            }
            
            tr[i].style.display = rowVisible ? "" : "none";
          }
        }
      }
      
      // Fonction pour trier le tableau
      function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("chambresTable");
        switching = true;
        // Définir la direction de tri (ascendant)
        dir = "asc";
        
        while (switching) {
          switching = false;
          rows = table.rows;
          
          for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n + 2]; // +2 pour ignorer checkbox et actions
            y = rows[i + 1].getElementsByTagName("TD")[n + 2];
            
            if (x && y) {
              // Convertir les valeurs pour le tri
              var xValue, yValue;
              
              // Vérifier si la valeur est un nombre
              if (!isNaN(parseFloat(x.innerHTML))) {
                xValue = parseFloat(x.innerHTML);
                yValue = parseFloat(y.innerHTML);
              } else {
                xValue = x.innerHTML.toLowerCase();
                yValue = y.innerHTML.toLowerCase();
              }
              
              if (dir == "asc") {
                if (xValue > yValue) {
                  shouldSwitch = true;
                  break;
                }
              } else if (dir == "desc") {
                if (xValue < yValue) {
                  shouldSwitch = true;
                  break;
                }
              }
            }
          }
          
          if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
          } else {
            if (switchcount == 0 && dir == "asc") {
              dir = "desc";
              switching = true;
            }
          }
        }
      }
      
      // Sélection de toutes les cases à cocher
      document.addEventListener('DOMContentLoaded', function() {
        // Ajouter une case à cocher dans l'en-tête si nécessaire
        const headerCheckbox = document.querySelector('thead th:first-child');
        if (headerCheckbox && !headerCheckbox.querySelector('input[type="checkbox"]')) {
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.id = 'selectAll';
          checkbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.row-checkbox').forEach(cb => {
              cb.checked = isChecked;
            });
          });
          headerCheckbox.appendChild(checkbox);
        }
      });