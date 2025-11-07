document.addEventListener("DOMContentLoaded", () => {
  
  const modal = document.getElementById("userModal");
  const cancelBtn = document.getElementById("userCancelBtn");
  const form = document.getElementById("userForm");
  const modalTitle = document.getElementById("userModalTitle");
  const tableBody = document.querySelector("#userTable tbody");
  const searchInput = document.getElementById("userSearch");
  
  let editingUserId = null; 
  let users = []; 
  let currentPage = 1;
  let itemsPerPage = 15;

  loadUsers();

  cancelBtn.addEventListener("click", closeModal);
  
  window.addEventListener("click", e => { 
    if (e.target === modal) closeModal(); 
  });

  form.addEventListener("submit", handleFormSubmit);

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      currentPage = 1; 
      renderTable();
    });
  }

  const filterButtons = document.querySelectorAll(".filter-btn");
  filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      handleFilter(btn, filterButtons);
      currentPage = 1;
      renderTable();
    });
  });

  document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderTable();
    }
  });

  document.getElementById("nextPage").addEventListener("click", () => {
    const filteredData = getFilteredUsers();
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (currentPage < totalPages) {
      currentPage++;
      renderTable();
    }
  });

  document.getElementById("itemsPerPage").addEventListener("change", (e) => {
    itemsPerPage = e.target.value === 'all' ? Infinity : parseInt(e.target.value);
    currentPage = 1; 
    renderTable();
  });

  // Load users from server
  function loadUsers() {
    fetch('api_users.php?action=getUsers')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          users = data.data;
          updateSummaryCards();
          renderTable();
        } else {
          console.error('Failed to load users:', data.message);
        }
      })
      .catch(error => console.error('Error loading users:', error));
  }

  function openModal(title, userData = null) {
    modalTitle.textContent = title;
    modal.classList.add("show");

    if (userData) {
      document.getElementById("userId").value = userData.user_id || '';
      document.getElementById("userName").value = userData.name || '';
      document.getElementById("userEmail").value = userData.email || '';
      document.getElementById("userContact").value = userData.contact || '';
      document.getElementById("userStatus").value = userData.status || 'active';
      document.getElementById("userCreatedAt").value = userData.created_at || '';
      editingUserId = userData.user_id;
    } else {
      form.reset();
      editingUserId = null;
    }
  }

  function closeModal() {
    modal.classList.remove("show");
    form.reset();
    editingUserId = null; 
  }

  function handleFormSubmit(e) {
    e.preventDefault();

    if (!editingUserId) {
      alert('No user selected');
      return;
    }

    const status = document.getElementById("userStatus").value.trim();

    if (!status) {
      alert("Please select a status");
      return;
    }

    const formData = new FormData();
    formData.append('action', 'updateUser');
    formData.append('user_id', editingUserId);
    formData.append('status', status);

    fetch('api_users.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        closeModal();
        loadUsers();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error:', error));
  }

  function renderTable() {
    tableBody.innerHTML = '';

    let data = getFilteredUsers();

    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    const pageData = data.slice(startIndex, endIndex);

    pageData.forEach((user) => {
      const row = createTableRow(user);
      tableBody.appendChild(row);
    });

    updatePaginationUI(totalItems, startIndex, endIndex, totalPages);

    if (totalItems === 0) {
      const emptyRow = document.createElement('tr');
      emptyRow.innerHTML = '<td colspan="6" style="text-align: center; padding: 30px; color: #999;">No users found</td>';
      tableBody.appendChild(emptyRow);
    }
  }

  function getFilteredUsers() {
    let filtered = [...users];

    const searchTerm = searchInput.value.toLowerCase();
    if (searchTerm) {
      filtered = filtered.filter(user => {
        return (
          user.name.toLowerCase().includes(searchTerm) ||
          user.email.toLowerCase().includes(searchTerm) ||
          user.contact.toLowerCase().includes(searchTerm)
        );
      });
    }

    const activeFilter = document.querySelector(".filter-btn.active");
    const statusFilter = activeFilter?.dataset.status?.toLowerCase() || 'all';
    if (statusFilter !== 'all') {
      filtered = filtered.filter(user => user.status.toLowerCase() === statusFilter);
    }

    return filtered;
  }

  function createTableRow(user) {
    const row = document.createElement("tr");
    
    row.innerHTML = `
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>${user.contact}</td>
      <td>${new Date(user.created_at).toLocaleDateString()}</td>
      <td>
        <span style="padding: 5px 10px; border-radius: 5px; ${user.status === 'active' ? 'background-color: #4caf50; color: white;' : 'background-color: #f44336; color: white;'}">
          ${user.status}
        </span>
      </td>
      <td>
        <button class="btn edit-btn" data-user-id="${user.user_id}">Edit</button>
      </td>
    `;

    const editBtn = row.querySelector(".edit-btn");

    editBtn.addEventListener("click", () => {
      openModal("Edit User", user);
    });

    return row;
  }

  function updatePaginationUI(totalItems, startIndex, endIndex, totalPages) {
    document.getElementById("showingStart").textContent = totalItems > 0 ? startIndex + 1 : 0;
    document.getElementById("showingEnd").textContent = endIndex;
    document.getElementById("totalRecords").textContent = totalItems;

    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = currentPage === totalPages || totalPages === 0;

    renderPageNumbers(totalPages);
  }

  function renderPageNumbers(totalPages) {
    const pageNumbersDiv = document.getElementById("pageNumbers");
    pageNumbersDiv.innerHTML = '';

    if (totalPages <= 1) return;

    const maxVisible = 7;
    let pages = [];

    if (totalPages <= maxVisible) {
      pages = Array.from({ length: totalPages }, (_, i) => i + 1);
    } else {
      if (currentPage <= 3) {
        pages = [1, 2, 3, 4, '...', totalPages];
      } else if (currentPage >= totalPages - 2) {
        pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      } else {
        pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
      }
    }

    pages.forEach(page => {
      const pageBtn = document.createElement('div');
      if (page === '...') {
        pageBtn.className = 'page-number ellipsis';
        pageBtn.textContent = '...';
      } else {
        pageBtn.className = 'page-number';
        if (page === currentPage) {
          pageBtn.classList.add('active');
        }
        pageBtn.textContent = page;
        pageBtn.addEventListener('click', () => {
          currentPage = page;
          renderTable();
        });
      }
      pageNumbersDiv.appendChild(pageBtn);
    });
  }

  function updateSummaryCards() {
    let total = 0;
    let active = 0;
    let inactive = 0;

    users.forEach(user => {
      total++;
      if (user.status === 'active') {
        active++;
      } else if (user.status === 'inactive') {
        inactive++;
      }
    });

    document.getElementById("totalUsers").textContent = total;
    document.getElementById("activeUsers").textContent = active;
    document.getElementById("inactiveUsers").textContent = inactive;
  }

  function handleFilter(btn, filterButtons) {
    filterButtons.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
  }

  // --- Sidebar toggle and nav wiring ---
  const leftPanel = document.getElementById('leftPanel');
  const panelToggle = document.getElementById('panelToggle');

  if (leftPanel && panelToggle) {
    // initialize collapsed state
    leftPanel.classList.add('closed');
    leftPanel.setAttribute('aria-hidden', 'true');
    panelToggle.setAttribute('aria-expanded', 'false');
    panelToggle.innerHTML = '▶';

    panelToggle.addEventListener('click', () => {
      const isClosed = leftPanel.classList.contains('closed');
      if (isClosed) {
        // expand
        leftPanel.classList.remove('closed');
        leftPanel.setAttribute('aria-hidden', 'false');
        panelToggle.setAttribute('aria-expanded', 'true');
        panelToggle.innerHTML = '◀';
      } else {
        // collapse
        leftPanel.classList.add('closed');
        leftPanel.setAttribute('aria-hidden', 'true');
        panelToggle.setAttribute('aria-expanded', 'false');
        panelToggle.innerHTML = '▶';
      }
    });

    // basic stub handlers for sidebar buttons
    document.querySelectorAll('.side-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.classList.contains('logout-btn')) {
          if (confirm('Are you sure you want to log out?')) {
            window.location.href = '../../auth/logout.php';
          }
          return;
        }

        const target = btn.dataset.target;
        if (target === 'dashboard') {
          window.location.href = 'dashboard.php';
        } else if (target === 'users') {
          window.location.href = 'users.php';
        } else if (target === 'doctors') {
          window.location.href = 'doctors.php';
        }
      });
    });
  }
});

