/* Clinic Management System - Frontend (vanilla JS SPA)
 * Served via backend/public/index.php and talks to backend/public/api.php.
 */

const API = {
  async request(route, { method = 'GET', body, query } = {}) {
    const url = new URL('/api.php', window.location.origin);
    url.searchParams.set('route', route);
    if (query && typeof query === 'object') {
      Object.entries(query).forEach(([k, v]) => {
        if (v === undefined || v === null || v === '') return;
        url.searchParams.set(k, String(v));
      });
    }

    const headers = { 'Content-Type': 'application/json' };
    const csrf = state.csrfToken;
    if (csrf && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      headers['X-CSRF-Token'] = csrf;
    }

    const res = await fetch(url.toString(), {
      method,
      credentials: 'include',
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error(`Unexpected server response (${res.status})`);
    }
    if (!data?.ok) {
      const msg = data?.error?.message || 'Request failed';
      const err = new Error(msg);
      err.status = res.status;
      err.details = data?.error?.details;
      throw err;
    }
    return data;
  },

  get(route, query) {
    return API.request(route, { method: 'GET', query });
  },
  post(route, body) {
    return API.request(route, { method: 'POST', body });
  },
  patch(route, body) {
    return API.request(route, { method: 'PATCH', body });
  },
  put(route, body) {
    return API.request(route, { method: 'PUT', body });
  },
  del(route, body) {
    return API.request(route, { method: 'DELETE', body });
  },
};

const state = {
  user: null,
  csrfToken: null,
  route: 'dashboard',
};

function el(tag, attrs = {}, children = []) {
  const n = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => {
    if (k === 'class') n.className = v;
    else if (k === 'html') n.innerHTML = v;
    else if (k.startsWith('on') && typeof v === 'function') n.addEventListener(k.slice(2), v);
    else if (v !== undefined && v !== null) n.setAttribute(k, String(v));
  });
  (Array.isArray(children) ? children : [children]).forEach((c) => {
    if (c === null || c === undefined) return;
    n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
  });
  return n;
}

function toast(type, title, message) {
  const root = document.getElementById('toast-root');
  const t = el('div', { class: `toast ${type}` }, [
    el('div', { class: 't' }, title),
    message ? el('div', { class: 'm' }, message) : null,
  ]);
  root.appendChild(t);
  setTimeout(() => t.remove(), 3800);
}

function badgeStatus(s) {
  const map = {
    active: 'ok',
    inactive: 'bad',
    scheduled: 'warn',
    completed: 'ok',
    cancelled: 'bad',
    no_show: 'bad',
  };
  const cls = map[s] || '';
  return `<span class="tag ${cls}">${escapeHtml(String(s))}</span>`;
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}

function setHash(route) {
  window.location.hash = `#/${route}`;
}

function parseHash() {
  const h = window.location.hash || '#/dashboard';
  const m = h.match(/^#\/([^?]+)(\?.*)?$/);
  return m ? m[1] : 'dashboard';
}

async function boot() {
  try {
    const me = await API.get('/auth/me');
    state.user = me.data.user;
    const csrf = await API.get('/auth/csrf');
    state.csrfToken = csrf.data.csrf_token;
  } catch {
    state.user = null;
    state.csrfToken = null;
  }

  state.route = parseHash();
  render();
}

function navItemsForRole(role) {
  const common = [
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'appointments', label: 'Appointments' },
    { id: 'departments', label: 'Departments' },
    { id: 'medicines', label: 'Medicines' },
    { id: 'billing', label: 'Billing / Payment' },
    { id: 'reports', label: 'Reports' },
    { id: 'settings', label: 'Settings' },
    { id: 'profile', label: 'Profile' },
    { id: 'logout', label: 'Logout' },
  ];

  if (role === 'admin') {
    return [
      ...common.slice(0, 1),
      { id: 'doctors', label: 'Doctor Management' },
      { id: 'staff', label: 'Staff Management' },
      { id: 'patients', label: 'Patient Management' },
      ...common.slice(1),
    ];
  }
  if (role === 'staff') {
    return [
      ...common.slice(0, 1),
      { id: 'doctors', label: 'Doctor Directory' },
      { id: 'patients', label: 'Patient Management' },
      ...common.slice(1),
    ];
  }
  if (role === 'doctor') {
    return [
      { id: 'dashboard', label: 'Dashboard' },
      { id: 'appointments', label: 'Appointments' },
      { id: 'patients', label: 'Patients' },
      { id: 'departments', label: 'Departments' },
      { id: 'medicines', label: 'Medicines' },
      { id: 'reports', label: 'Reports' },
      { id: 'profile', label: 'Profile' },
      { id: 'logout', label: 'Logout' },
    ];
  }
  // patient
  return [
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'appointments', label: 'Appointments' },
    { id: 'billing', label: 'Billing / Payment' },
    { id: 'reports', label: 'Reports' },
    { id: 'feedback', label: 'Feedback' },
    { id: 'settings', label: 'Settings' },
    { id: 'profile', label: 'Profile' },
    { id: 'logout', label: 'Logout' },
  ];
}

function renderAuth() {
  const root = document.getElementById('app');
  root.innerHTML = '';
  const card = el('div', { class: 'auth' }, [
    el('div', { class: 'auth-card' }, [
      el('div', { class: 'auth-left' }, [
        el('div', { class: 'row' }, [
          el('img', { src: '/assets/logo.svg', alt: 'Clinic', width: '46', height: '46' }),
          el('div', {}, [
            el('div', { style: 'font-weight:750; font-size:14px' }, 'Clinic Management System'),
            el('div', { class: 'muted', style: 'font-size:12px; margin-top:2px' }, 'Secure • Fast • Responsive'),
          ]),
        ]),
        el('h1', { style: 'margin-top:14px' }, 'Welcome back'),
        el('p', {}, 'Log in to manage appointments, billing, departments, staff/doctors, and patient records. The interface adapts by role (admin/doctor/staff/patient).'),
        el('div', { class: 'divider' }),
        el('div', { class: 'muted', style: 'font-size:13px; line-height:1.7' }, [
          el('div', {}, 'Demo accounts (from seed.sql):'),
          el('div', {}, 'admin@clinic.test / Admin@123'),
          el('div', {}, 'doctor@clinic.test / Doctor@123'),
          el('div', {}, 'staff@clinic.test / Staff@123'),
          el('div', {}, 'patient@clinic.test / Patient@123'),
        ]),
      ]),
      el('div', { class: 'auth-right' }, [
        el('div', { style: 'font-weight:750; font-size:18px' }, 'Login'),
        el('div', { class: 'label' }, 'Email'),
        el('input', { class: 'input', id: 'login-email', type: 'email', placeholder: 'you@clinic.test', autocomplete: 'username' }),
        el('div', { class: 'label' }, 'Password'),
        el('input', { class: 'input', id: 'login-password', type: 'password', placeholder: '••••••••', autocomplete: 'current-password' }),
        el('div', { style: 'height:12px' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;
            try {
              const res = await API.post('/auth/login', { email, password });
              state.user = res.data.user;
              state.csrfToken = res.data.csrf_token;
              toast('ok', 'Logged in', `Role: ${state.user.role}`);
              setHash('dashboard');
              render();
            } catch (e) {
              toast('bad', 'Login failed', e.message);
            }
          },
        }, 'Sign in'),
      ]),
    ]),
  ]);
  root.appendChild(card);
}

function renderShell(inner) {
  const root = document.getElementById('app');
  root.innerHTML = '';

  const role = state.user?.role || 'guest';
  const nav = navItemsForRole(role);

  const sidebar = el('aside', { class: 'sidebar' }, [
    el('div', { class: 'brand' }, [
      el('img', { src: '/assets/logo.svg', alt: 'Clinic logo' }),
      el('div', {}, [
        el('div', { class: 'title' }, 'Clinic CMS'),
        el('div', { class: 'subtitle' }, role.toUpperCase()),
      ]),
    ]),
    el('nav', { class: 'nav' }, nav.map((it) =>
      el('a', {
        href: `#/${it.id}`,
        class: state.route === it.id ? 'active' : '',
        onclick: (e) => {
          if (it.id === 'logout') return;
          e.preventDefault();
          setHash(it.id);
        },
      }, [
        el('span', {}, it.label),
        it.id === 'logout' ? el('span', { class: 'pill' }, 'Exit') : null,
      ])
    )),
  ]);

  const content = el('main', { class: 'content' }, [
    el('div', { class: 'topbar' }, [
      el('div', {}, [
        el('div', { style: 'font-weight:750; font-size:16px' }, titleForRoute(state.route)),
        el('div', { class: 'who' }, [
          'Signed in as ',
          el('b', {}, state.user.user_name),
          ` (${state.user.role})`,
        ]),
      ]),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn small', onclick: async () => { await refreshMe(); } }, 'Refresh'),
      ]),
    ]),
    inner,
  ]);

  root.appendChild(el('div', { class: 'shell' }, [sidebar, content]));
}

function titleForRoute(r) {
  const map = {
    dashboard: 'Dashboard',
    doctors: 'Doctor Management',
    staff: 'Staff Management',
    patients: 'Patient Management',
    appointments: 'Appointment Management',
    departments: 'Department Management',
    medicines: 'Medicines',
    billing: 'Billing / Payment',
    reports: 'Reports',
    settings: 'Settings',
    profile: 'Profile',
    feedback: 'Feedback',
    logout: 'Logout',
  };
  return map[r] || 'Clinic CMS';
}

async function refreshMe() {
  try {
    const me = await API.get('/auth/me');
    state.user = me.data.user;
    const csrf = await API.get('/auth/csrf');
    state.csrfToken = csrf.data.csrf_token;
    toast('ok', 'Refreshed', 'Session updated');
    render();
  } catch (e) {
    toast('warn', 'Session expired', 'Please login again');
    state.user = null;
    state.csrfToken = null;
    render();
  }
}

function render() {
  state.route = parseHash();

  if (!state.user) {
    renderAuth();
    return;
  }

  if (state.route === 'logout') {
    (async () => {
      try { await API.post('/auth/logout', {}); } catch {}
      state.user = null;
      state.csrfToken = null;
      toast('ok', 'Logged out', 'See you next time');
      setHash('dashboard');
      render();
    })();
    renderShell(el('div', { class: 'page' }, el('div', { class: 'card full' }, 'Logging out...')));
    return;
  }

  const page = el('div', { class: 'page' }, []);
  renderShell(page);

  const r = state.route;
  const role = state.user.role;

  // Basic pages (more CRUD views are added next as we continue)
  if (r === 'dashboard') return void renderDashboard(page);
  if (r === 'departments') return void renderDepartments(page);
  if (r === 'medicines') return void renderMedicines(page);
  if (r === 'appointments') return void renderAppointments(page);
  if (r === 'billing') return void renderBilling(page);
  if (r === 'reports') return void renderReports(page);
  if (r === 'settings') return void renderSettings(page);
  if (r === 'feedback') return void renderFeedback(page);
  if (r === 'profile') return void renderProfile(page);

  if (r === 'doctors') return void renderDoctors(page);
  if (r === 'staff') return void renderStaff(page);
  if (r === 'patients') return void renderPatients(page);

  page.appendChild(el('div', { class: 'card full' }, 'Page not found'));
}

async function renderDashboard(page) {
  page.innerHTML = '';
  const card = el('div', { class: 'card full' }, [
    el('h3', {}, 'Overview'),
    el('div', { class: 'muted' }, 'Key stats for your role.'),
    el('div', { style: 'height:12px' }),
    el('div', { class: 'kpis', id: 'kpi-root' }, []),
  ]);
  page.appendChild(card);

  try {
    const res = await API.get('/reports/dashboard');
    const data = res.data.dashboard || {};
    const k = document.getElementById('kpi-root');
    k.innerHTML = '';
    Object.entries(data).forEach(([label, value]) => {
      k.appendChild(el('div', { class: 'kpi' }, [
        el('div', { class: 'label' }, label.replaceAll('_', ' ')),
        el('div', { class: 'value' }, String(value)),
      ]));
    });
  } catch (e) {
    toast('bad', 'Dashboard failed', e.message);
  }
}

function tableView({ columns, rows, emptyText }) {
  if (!rows.length) {
    return el('div', { class: 'muted' }, emptyText || 'No data');
  }
  const thead = el('thead', {}, el('tr', {}, columns.map((c) => el('th', {}, c.label))));
  const tbody = el('tbody', {}, rows.map((r) => el('tr', {}, columns.map((c) => el('td', { class: c.class || '' , html: c.render ? c.render(r) : escapeHtml(String(r[c.key] ?? '')) })) )));
  const t = el('table', { class: 'table' }, [thead, tbody]);
  return t;
}

async function renderDepartments(page) {
  page.innerHTML = '';
  const controls = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:260px; flex:1' }, [
        el('div', { class: 'label' }, 'Search'),
        el('input', { class: 'input', id: 'dept-q', placeholder: 'e.g. Cardiology' }),
      ]),
      el('div', { class: 'spacer' }),
      state.user.role === 'admin'
        ? el('button', { class: 'btn primary', onclick: () => openDepartmentModal() }, 'Add Department')
        : null,
      el('button', { class: 'btn', onclick: () => loadDepartments() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'dept-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(controls);

  async function loadDepartments() {
    const q = document.getElementById('dept-q').value.trim();
    try {
      const res = await API.get('/departments', { q, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      document.getElementById('dept-table').innerHTML = '';
      document.getElementById('dept-table').appendChild(tableView({
        columns: [
          { key: 'department_id', label: 'ID' },
          { key: 'department_name', label: 'Name' },
          { key: 'description', label: 'Description' },
          { key: 'updated_at', label: 'Updated' },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            if (state.user.role !== 'admin') return '';
            return `
              <button class="btn small" data-act="edit" data-id="${r.department_id}">Edit</button>
              <button class="btn small danger" data-act="del" data-id="${r.department_id}">Delete</button>
            `;
          }},
        ],
        rows,
        emptyText: 'No departments found.',
      }));
      document.getElementById('dept-table').querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'edit') return openDepartmentModal(id);
          if (act === 'del') return deleteDepartment(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function deleteDepartment(id) {
    if (!confirm('Delete this department?')) return;
    try {
      await API.del(`/departments/${id}`, {});
      toast('ok', 'Deleted', 'Department removed');
      loadDepartments();
    } catch (e) {
      toast('bad', 'Delete failed', e.message);
    }
  }

  async function openDepartmentModal(id) {
    const isEdit = !!id;
    let current = { department_name: '', description: '' };
    if (isEdit) {
      const res = await API.get(`/departments/${id}`);
      current = res.data.department;
    }
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Department' : 'Add Department'),
      el('div', { class: 'label' }, 'Name'),
      el('input', { class: 'input', id: 'dept-name', value: current.department_name || '' }),
      el('div', { class: 'label' }, 'Description'),
      el('textarea', { id: 'dept-desc' }, current.description || ''),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const name = document.getElementById('dept-name').value.trim();
            const description = document.getElementById('dept-desc').value.trim() || null;
            try {
              if (isEdit) await API.put(`/departments/${id}`, { department_name: name, description });
              else await API.post('/departments', { department_name: name, description });
              toast('ok', 'Saved', 'Department updated');
              backdrop.remove();
              loadDepartments();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadDepartments();
}

async function renderMedicines(page) {
  page.innerHTML = '';
  const canWrite = ['admin', 'staff'].includes(state.user.role);
  const controls = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:260px; flex:1' }, [
        el('div', { class: 'label' }, 'Search'),
        el('input', { class: 'input', id: 'med-q', placeholder: 'name or company' }),
      ]),
      el('div', { class: 'spacer' }),
      canWrite ? el('button', { class: 'btn primary', onclick: () => openMedicineModal() }, 'Add Medicine') : null,
      el('button', { class: 'btn', onclick: () => loadMedicines() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'med-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(controls);

  async function loadMedicines() {
    const q = document.getElementById('med-q').value.trim();
    try {
      const res = await API.get('/medicines', { q, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('med-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'medicine_id', label: 'ID' },
          { key: 'medicine_name', label: 'Name' },
          { key: 'company', label: 'Company' },
          { key: 'price', label: 'Price' },
          { key: 'stock', label: 'Stock' },
          { key: 'expiry_date', label: 'Expiry' },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            if (!canWrite) return '';
            return `
              <button class="btn small" data-act="edit" data-id="${r.medicine_id}">Edit</button>
              <button class="btn small danger" data-act="del" data-id="${r.medicine_id}">Delete</button>
            `;
          }},
        ],
        rows,
        emptyText: 'No medicines found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'edit') return openMedicineModal(id);
          if (act === 'del') return deleteMedicine(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function deleteMedicine(id) {
    if (!confirm('Delete this medicine?')) return;
    try {
      await API.del(`/medicines/${id}`, {});
      toast('ok', 'Deleted', 'Medicine removed');
      loadMedicines();
    } catch (e) {
      toast('bad', 'Delete failed', e.message);
    }
  }

  async function openMedicineModal(id) {
    const isEdit = !!id;
    let current = { medicine_name: '', company: '', price: 0, stock: 0, expiry_date: '' };
    if (isEdit) {
      const res = await API.get(`/medicines/${id}`);
      current = res.data.medicine;
    }
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Medicine' : 'Add Medicine'),
      el('div', { class: 'grid' }, [
        el('div', { class: 'card full', style: 'box-shadow:none; background:transparent; border:none; padding:0' }, [
          el('div', { class: 'label' }, 'Name'),
          el('input', { class: 'input', id: 'm-name', value: current.medicine_name || '' }),
          el('div', { class: 'label' }, 'Company'),
          el('input', { class: 'input', id: 'm-company', value: current.company || '' }),
          el('div', { class: 'row' }, [
            el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Price'), el('input', { class: 'input', id: 'm-price', type: 'number', step: '0.01', value: current.price ?? 0 })]),
            el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Stock'), el('input', { class: 'input', id: 'm-stock', type: 'number', step: '1', value: current.stock ?? 0 })]),
            el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Expiry'), el('input', { class: 'input', id: 'm-exp', type: 'date', value: current.expiry_date || '' })]),
          ]),
        ]),
      ]),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payload = {
              medicine_name: document.getElementById('m-name').value.trim(),
              company: document.getElementById('m-company').value.trim() || null,
              price: Number(document.getElementById('m-price').value || 0),
              stock: Number(document.getElementById('m-stock').value || 0),
              expiry_date: document.getElementById('m-exp').value || null,
            };
            try {
              if (isEdit) await API.put(`/medicines/${id}`, payload);
              else await API.post('/medicines', payload);
              toast('ok', 'Saved', 'Medicine updated');
              backdrop.remove();
              loadMedicines();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadMedicines();
}

async function renderAppointments(page) {
  page.innerHTML = '';
  const canCreate = ['admin', 'staff', 'patient'].includes(state.user.role);
  const card = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'From'), el('input', { class: 'input', type: 'date', id: 'a-from', value: new Date().toISOString().slice(0,10) })]),
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'To'), el('input', { class: 'input', type: 'date', id: 'a-to', value: new Date(Date.now() + 7*86400000).toISOString().slice(0,10) })]),
      el('div', { class: 'spacer' }),
      canCreate ? el('button', { class: 'btn primary', onclick: () => openAppointmentModal() }, 'Book Appointment') : null,
      el('button', { class: 'btn', onclick: () => loadAppointments() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'a-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  async function loadAppointments() {
    const from = document.getElementById('a-from').value;
    const to = document.getElementById('a-to').value;
    try {
      const res = await API.get('/appointments', { from, to, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('a-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'appointment_id', label: 'ID' },
          { key: 'appointment_date', label: 'Date' },
          { key: 'appointment_time', label: 'Time' },
          { key: 'doctor_name', label: 'Doctor' },
          { key: 'patient_name', label: 'Patient' },
          { key: 'status', label: 'Status', render: (r) => badgeStatus(r.status) },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            const role = state.user.role;
            if (role === 'patient' && r.status === 'scheduled') return `<button class="btn small danger" data-act="cancel" data-id="${r.appointment_id}">Cancel</button>`;
            if (role === 'doctor') return `<button class="btn small" data-act="done" data-id="${r.appointment_id}">Mark Completed</button>`;
            if (role === 'admin' || role === 'staff') return `<button class="btn small" data-act="edit" data-id="${r.appointment_id}">Edit</button>`;
            return '';
          }},
        ],
        rows,
        emptyText: 'No appointments found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'cancel') return cancelAppointment(id);
          if (act === 'done') return markCompleted(id);
          if (act === 'edit') return openAppointmentModal(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function cancelAppointment(id) {
    if (!confirm('Cancel this appointment?')) return;
    try {
      await API.patch(`/appointments/${id}`, { status: 'cancelled' });
      toast('ok', 'Cancelled', 'Appointment cancelled');
      loadAppointments();
    } catch (e) {
      toast('bad', 'Cancel failed', e.message);
    }
  }

  async function markCompleted(id) {
    try {
      await API.patch(`/appointments/${id}`, { status: 'completed' });
      toast('ok', 'Updated', 'Marked as completed');
      loadAppointments();
    } catch (e) {
      toast('bad', 'Update failed', e.message);
    }
  }

  async function openAppointmentModal(id) {
    const isEdit = !!id;
    let current = { patient_id: '', doctor_id: '', appointment_date: new Date().toISOString().slice(0,10), appointment_time: '10:00' };
    if (isEdit) {
      const res = await API.get(`/appointments/${id}`);
      current = res.data.appointment;
    }

    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Appointment' : 'Book Appointment'),
      state.user.role === 'patient'
        ? el('div', { class: 'muted' }, 'Booking for your account.')
        : el('div', { class: 'label' }, 'Patient ID'),
      state.user.role === 'patient'
        ? null
        : el('input', { class: 'input', id: 'ap-patient', type: 'number', value: current.patient_id || '' }),
      el('div', { class: 'label' }, 'Doctor ID'),
      el('input', { class: 'input', id: 'ap-doctor', type: 'number', value: current.doctor_id || '' }),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Date'), el('input', { class: 'input', id: 'ap-date', type: 'date', value: current.appointment_date || '' })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Time'), el('input', { class: 'input', id: 'ap-time', type: 'time', value: (current.appointment_time || '10:00:00').slice(0,5) })]),
      ]),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payload = {
              patient_id: state.user.role === 'patient' ? undefined : Number(document.getElementById('ap-patient').value || 0),
              doctor_id: Number(document.getElementById('ap-doctor').value || 0),
              appointment_date: document.getElementById('ap-date').value,
              appointment_time: document.getElementById('ap-time').value,
            };
            try {
              if (isEdit) await API.put(`/appointments/${id}`, payload);
              else await API.post('/appointments', payload);
              toast('ok', 'Saved', 'Appointment updated');
              backdrop.remove();
              loadAppointments();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadAppointments();
}

async function renderBilling(page) {
  page.innerHTML = '';
  const canWrite = ['admin', 'staff'].includes(state.user.role);
  const card = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'From'), el('input', { class: 'input', type: 'date', id: 'b-from', value: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10) })]),
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'To'), el('input', { class: 'input', type: 'date', id: 'b-to', value: new Date().toISOString().slice(0,10) })]),
      el('div', { class: 'spacer' }),
      canWrite ? el('button', { class: 'btn primary', onclick: () => openBillModal() }, 'Create Bill') : null,
      el('button', { class: 'btn', onclick: () => loadBills() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'b-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  async function loadBills() {
    const from = document.getElementById('b-from').value;
    const to = document.getElementById('b-to').value;
    try {
      const res = await API.get('/billing', { from, to, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('b-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'bill_id', label: 'Bill ID' },
          { key: 'patient_name', label: 'Patient' },
          { key: 'bill_date', label: 'Bill Date' },
          { key: 'total_amount', label: 'Total' },
          { key: 'paid_amount', label: 'Paid' },
          { key: 'due_amount', label: 'Due' },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            const view = `<button class="btn small" data-act="view" data-id="${r.bill_id}">View</button>`;
            if (!canWrite) return view;
            return `${view} <button class="btn small" data-act="pay" data-id="${r.bill_id}">Add Payment</button>`;
          }},
        ],
        rows,
        emptyText: 'No bills found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'view') return openBillView(id);
          if (act === 'pay') return openPaymentModal(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function openBillView(id) {
    try {
      const res = await API.get(`/billing/${id}`);
      const { bill, items, payments, summary } = res.data;
      const backdrop = el('div', { class: 'modal-backdrop' }, []);
      const modal = el('div', { class: 'modal' }, [
        el('h3', {}, `Bill #${bill.bill_id}`),
        el('div', { class: 'muted' }, `Bill date: ${bill.bill_date} • Total: ${bill.total_amount} • Paid: ${summary.paid_amount} • Due: ${summary.due_amount}`),
        el('div', { style: 'height:10px' }),
        el('div', { class: 'label' }, 'Items'),
        tableView({
          columns: [
            { key: 'description', label: 'Description' },
            { key: 'quantity', label: 'Qty' },
            { key: 'price', label: 'Price' },
            { key: 'total', label: 'Total' },
          ],
          rows: items,
          emptyText: 'No items.',
        }),
        el('div', { style: 'height:10px' }),
        el('div', { class: 'label' }, 'Payments'),
        tableView({
          columns: [
            { key: 'payment_date', label: 'Date' },
            { key: 'payment_mode', label: 'Mode' },
            { key: 'amount', label: 'Amount' },
          ],
          rows: payments,
          emptyText: 'No payments.',
        }),
        el('div', { style: 'height:12px' }),
        el('div', { class: 'row' }, [
          el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Close'),
        ]),
      ]);
      backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
      backdrop.appendChild(modal);
      document.body.appendChild(backdrop);
    } catch (e) {
      toast('bad', 'Load bill failed', e.message);
    }
  }

  function openBillModal() {
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, 'Create Bill'),
      el('div', { class: 'label' }, 'Patient ID'),
      el('input', { class: 'input', id: 'bill-patient', type: 'number', placeholder: 'e.g. 1' }),
      el('div', { class: 'label' }, 'Bill Date'),
      el('input', { class: 'input', id: 'bill-date', type: 'date', value: new Date().toISOString().slice(0,10) }),
      el('div', { class: 'label' }, 'Items (one per line: description | qty | price)'),
      el('textarea', { id: 'bill-items', class: 'input' }, 'Consultation Fee | 1 | 500\nMedicine | 1 | 100'),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const patient_id = Number(document.getElementById('bill-patient').value || 0);
            const bill_date = document.getElementById('bill-date').value;
            const lines = document.getElementById('bill-items').value.split('\n').map((l) => l.trim()).filter(Boolean);
            const items = lines.map((l) => {
              const parts = l.split('|').map((p) => p.trim());
              return { description: parts[0] || '', quantity: Number(parts[1] || 1), price: Number(parts[2] || 0) };
            });
            try {
              await API.post('/billing', { patient_id, bill_date, items });
              toast('ok', 'Created', 'Bill created');
              backdrop.remove();
              loadBills();
            } catch (e) {
              toast('bad', 'Create failed', e.message);
            }
          },
        }, 'Create'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  function openPaymentModal(billId) {
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, `Add Payment (Bill #${billId})`),
      el('div', { class: 'label' }, 'Mode'),
      el('input', { class: 'input', id: 'pay-mode', value: 'cash' }),
      el('div', { class: 'label' }, 'Amount'),
      el('input', { class: 'input', id: 'pay-amount', type: 'number', step: '0.01', value: '0' }),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payment_mode = document.getElementById('pay-mode').value.trim();
            const amount = Number(document.getElementById('pay-amount').value || 0);
            try {
              await API.post(`/billing/${billId}/payments`, { payment_mode, amount, payment_date: new Date().toISOString().slice(0,19).replace('T',' ') });
              toast('ok', 'Payment added', 'Payment recorded');
              backdrop.remove();
              loadBills();
            } catch (e) {
              toast('bad', 'Payment failed', e.message);
            }
          },
        }, 'Add'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadBills();
}

async function renderReports(page) {
  page.innerHTML = '';
  const role = state.user.role;
  const card = el('div', { class: 'card full' }, [
    el('h3', {}, 'Reports'),
    el('div', { class: 'muted' }, 'Appointments per doctor, revenue, and patient history.'),
    el('div', { style: 'height:12px' }),
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'From'), el('input', { class: 'input', type: 'date', id: 'r-from', value: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10) })]),
      el('div', { style: 'min-width:220px' }, [el('div', { class: 'label' }, 'To'), el('input', { class: 'input', type: 'date', id: 'r-to', value: new Date().toISOString().slice(0,10) })]),
      el('div', { class: 'spacer' }),
      el('button', { class: 'btn', onclick: () => loadReports() }, 'Generate'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'r-out' }, el('div', { class: 'muted' }, 'Select a range and click Generate.')),
  ]);
  page.appendChild(card);

  async function loadReports() {
    const from = document.getElementById('r-from').value;
    const to = document.getElementById('r-to').value;
    const out = document.getElementById('r-out');
    out.innerHTML = '';

    try {
      if (role === 'admin' || role === 'staff') {
        const [ap, rev] = await Promise.all([
          API.get('/reports/appointments-per-doctor', { from, to }),
          API.get('/reports/revenue', { from, to }),
        ]);
        out.appendChild(el('div', { class: 'card full' }, [
          el('h3', {}, 'Revenue'),
          el('div', { class: 'muted' }, `Total revenue: ${rev.data.revenue}`),
        ]));
        out.appendChild(el('div', { class: 'card full' }, [
          el('h3', {}, 'Appointments per doctor'),
          tableView({
            columns: [
              { key: 'doctor_id', label: 'Doctor ID' },
              { key: 'doctor_name', label: 'Doctor' },
              { key: 'appointment_count', label: 'Appointments' },
            ],
            rows: ap.data.items || [],
            emptyText: 'No results.',
          }),
        ]));
      } else {
        out.appendChild(el('div', { class: 'card full' }, [
          el('h3', {}, 'Patient History'),
          el('div', { class: 'muted' }, 'Your visit history is available from the server-side report endpoint.'),
        ]));
        const ph = await API.get('/reports/patient-history', {});
        out.appendChild(el('div', { class: 'card full' }, [
          tableView({
            columns: [
              { key: 'visit_date', label: 'Visit' },
              { key: 'doctor_name', label: 'Doctor' },
              { key: 'diagnosis', label: 'Diagnosis' },
              { key: 'notes', label: 'Notes' },
            ],
            rows: ph.data.items || [],
            emptyText: 'No history records.',
          }),
        ]));
      }
    } catch (e) {
      toast('bad', 'Report failed', e.message);
    }
  }
}

async function renderSettings(page) {
  page.innerHTML = '';
  const canEdit = state.user.role === 'admin';
  const card = el('div', { class: 'card full' }, [
    el('h3', {}, 'Clinic Settings'),
    el('div', { class: 'muted' }, canEdit ? 'Update clinic name, address, and contact information.' : 'View clinic information.'),
    el('div', { style: 'height:12px' }),
    el('div', { id: 's-form' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  try {
    const res = await API.get('/settings/clinic');
    const c = res.data.clinic || { clinic_name: '', address: '', contact: '', email: '' };
    const form = el('div', {}, [
      el('div', { class: 'label' }, 'Clinic Name'),
      el('input', { class: 'input', id: 's-name', value: c.clinic_name || '', disabled: !canEdit }),
      el('div', { class: 'label' }, 'Address'),
      el('input', { class: 'input', id: 's-addr', value: c.address || '', disabled: !canEdit }),
      el('div', { class: 'label' }, 'Contact'),
      el('input', { class: 'input', id: 's-contact', value: c.contact || '', disabled: !canEdit }),
      el('div', { class: 'label' }, 'Email'),
      el('input', { class: 'input', id: 's-email', value: c.email || '', disabled: !canEdit }),
      el('div', { style: 'height:12px' }),
      canEdit ? el('button', {
        class: 'btn primary',
        onclick: async () => {
          try {
            await API.put('/settings/clinic', {
              clinic_name: document.getElementById('s-name').value.trim(),
              address: document.getElementById('s-addr').value.trim(),
              contact: document.getElementById('s-contact').value.trim(),
              email: document.getElementById('s-email').value.trim(),
            });
            toast('ok', 'Saved', 'Settings updated');
          } catch (e) {
            toast('bad', 'Save failed', e.message);
          }
        },
      }, 'Save') : null,
    ]);
    document.getElementById('s-form').innerHTML = '';
    document.getElementById('s-form').appendChild(form);
  } catch (e) {
    toast('bad', 'Load failed', e.message);
  }
}

async function renderFeedback(page) {
  page.innerHTML = '';
  if (state.user.role !== 'patient') {
    const card = el('div', { class: 'card full' }, [
      el('h3', {}, 'Feedback'),
      el('div', { class: 'muted' }, 'Patients can submit feedback. Admin/Staff can review from the backend endpoint.'),
    ]);
    page.appendChild(card);
    return;
  }

  const card = el('div', { class: 'card full' }, [
    el('h3', {}, 'Submit Feedback'),
    el('div', { class: 'label' }, 'Rating (1-5)'),
    el('input', { class: 'input', id: 'fb-rating', type: 'number', min: '1', max: '5', value: '5' }),
    el('div', { class: 'label' }, 'Comments'),
    el('textarea', { class: 'input', id: 'fb-comments' }, ''),
    el('div', { style: 'height:12px' }),
    el('button', { class: 'btn primary', onclick: submit }, 'Send'),
  ]);
  page.appendChild(card);

  async function submit() {
    try {
      await API.post('/feedback', {
        rating: Number(document.getElementById('fb-rating').value || 5),
        comments: document.getElementById('fb-comments').value.trim(),
      });
      toast('ok', 'Thank you', 'Feedback submitted');
      document.getElementById('fb-comments').value = '';
    } catch (e) {
      toast('bad', 'Submit failed', e.message);
    }
  }
}

async function renderProfile(page) {
  page.innerHTML = '';
  const card = el('div', { class: 'card full' }, [
    el('h3', {}, 'Profile'),
    el('div', { class: 'muted' }, 'Your account information.'),
    el('div', { style: 'height:10px' }),
    el('div', { class: 'grid' }, [
      el('div', { class: 'card half' }, [
        el('h3', {}, 'Account'),
        el('div', { class: 'muted' }, `Name: ${state.user.user_name}`),
        el('div', { class: 'muted' }, `Email: ${state.user.login_email}`),
        el('div', { class: 'muted' }, `Role: ${state.user.role}`),
        el('div', { class: 'muted' }, `Status: ${state.user.status}`),
      ]),
      el('div', { class: 'card half' }, [
        el('h3', {}, 'Role profile'),
        el('div', { id: 'profile-role', class: 'muted' }, 'Loading...'),
      ]),
    ]),
  ]);
  page.appendChild(card);

  try {
    const role = state.user.role;
    const root = document.getElementById('profile-role');
    if (role === 'doctor') {
      const res = await API.get('/doctors/me');
      root.textContent = `Doctor ID: ${res.data.doctor.doctor_id} • Specialization: ${res.data.doctor.specialization || '-'}`;
    } else if (role === 'patient') {
      const res = await API.get('/patients/me');
      root.textContent = `Patient ID: ${res.data.patient.patient_id} • Blood group: ${res.data.patient.blood_group || '-'}`;
    } else if (role === 'staff') {
      root.textContent = 'Staff profile is managed by admin.';
    } else {
      root.textContent = 'Admin account.';
    }
  } catch (e) {
    toast('bad', 'Profile failed', e.message);
  }
}

async function renderDoctors(page) {
  page.innerHTML = '';
  const role = state.user.role;
  const canWrite = role === 'admin';

  const card = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:260px; flex:1' }, [
        el('div', { class: 'label' }, 'Search'),
        el('input', { class: 'input', id: 'doc-q', placeholder: 'name, email, specialization' }),
      ]),
      el('div', { class: 'spacer' }),
      canWrite ? el('button', { class: 'btn primary', onclick: () => openDoctorModal() }, 'Add Doctor') : null,
      el('button', { class: 'btn', onclick: () => loadDoctors() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'doc-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  async function loadDoctors() {
    const q = document.getElementById('doc-q').value.trim();
    try {
      const res = await API.get('/doctors', { q, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('doc-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'doctor_id', label: 'ID' },
          { key: 'name', label: 'Name' },
          { key: 'specialization', label: 'Specialization' },
          { key: 'department_name', label: 'Department' },
          { key: 'consultation_fee', label: 'Fee' },
          { key: 'status', label: 'Status', render: (r) => badgeStatus(r.status) },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            if (!canWrite) return '';
            return `
              <button class="btn small" data-act="edit" data-id="${r.doctor_id}">Edit</button>
              <button class="btn small danger" data-act="del" data-id="${r.doctor_id}">Delete</button>
            `;
          }},
        ],
        rows,
        emptyText: 'No doctors found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'edit') return openDoctorModal(id);
          if (act === 'del') return deleteDoctor(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function deleteDoctor(id) {
    if (!confirm('Delete this doctor (and login account)?')) return;
    try {
      await API.del(`/doctors/${id}`, {});
      toast('ok', 'Deleted', 'Doctor removed');
      loadDoctors();
    } catch (e) {
      toast('bad', 'Delete failed', e.message);
    }
  }

  async function openDoctorModal(id) {
    const isEdit = !!id;
    let current = {
      user_name: '',
      login_email: '',
      password: '',
      user_status: 'active',
      name: '',
      specialization: '',
      qualification: '',
      mobile: '',
      experience: '',
      consultation_fee: '',
      status: 'active',
      department_id: '',
    };
    if (isEdit) {
      const res = await API.get(`/doctors/${id}`);
      const d = res.data.doctor;
      current = {
        ...current,
        name: d.name || '',
        specialization: d.specialization || '',
        qualification: d.qualification || '',
        mobile: d.mobile || '',
        experience: d.experience ?? '',
        consultation_fee: d.consultation_fee ?? '',
        status: d.status || 'active',
        department_id: d.department_id ?? '',
      };
    }

    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Doctor' : 'Add Doctor'),
      !isEdit ? el('div', { class: 'muted' }, 'Creates a linked login account (role: doctor).') : null,
      !isEdit ? el('div', { class: 'label' }, 'Login name') : null,
      !isEdit ? el('input', { class: 'input', id: 'd-user', value: current.user_name }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Login email') : null,
      !isEdit ? el('input', { class: 'input', id: 'd-email', type: 'email', value: current.login_email }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Password') : null,
      !isEdit ? el('input', { class: 'input', id: 'd-pass', type: 'password', value: current.password }) : null,
      el('div', { class: 'label' }, 'Doctor name'),
      el('input', { class: 'input', id: 'd-name', value: current.name }),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Specialization'), el('input', { class: 'input', id: 'd-spec', value: current.specialization })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Qualification'), el('input', { class: 'input', id: 'd-qual', value: current.qualification })]),
      ]),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Mobile'), el('input', { class: 'input', id: 'd-mobile', value: current.mobile })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Experience (years)'), el('input', { class: 'input', id: 'd-exp', type: 'number', value: current.experience })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Fee'), el('input', { class: 'input', id: 'd-fee', type: 'number', step: '0.01', value: current.consultation_fee })]),
      ]),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Department ID'), el('input', { class: 'input', id: 'd-dept', type: 'number', value: current.department_id })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Status'), el('select', { class: 'input', id: 'd-status' }, [
          el('option', { value: 'active', selected: current.status === 'active' }, 'active'),
          el('option', { value: 'inactive', selected: current.status === 'inactive' }, 'inactive'),
        ])]),
      ]),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payload = {
              name: document.getElementById('d-name').value.trim(),
              specialization: document.getElementById('d-spec').value.trim() || null,
              qualification: document.getElementById('d-qual').value.trim() || null,
              mobile: document.getElementById('d-mobile').value.trim() || null,
              experience: Number(document.getElementById('d-exp').value || 0),
              consultation_fee: Number(document.getElementById('d-fee').value || 0),
              department_id: document.getElementById('d-dept').value ? Number(document.getElementById('d-dept').value) : null,
              status: document.getElementById('d-status').value,
            };
            try {
              if (isEdit) {
                await API.put(`/doctors/${id}`, payload);
              } else {
                await API.post('/doctors', {
                  user_name: document.getElementById('d-user').value.trim(),
                  login_email: document.getElementById('d-email').value.trim(),
                  password: document.getElementById('d-pass').value,
                  ...payload,
                });
              }
              toast('ok', 'Saved', 'Doctor updated');
              backdrop.remove();
              loadDoctors();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadDoctors();
}

async function renderStaff(page) {
  page.innerHTML = '';
  if (state.user.role !== 'admin') {
    page.appendChild(el('div', { class: 'card full' }, [
      el('h3', {}, 'Staff Management'),
      el('div', { class: 'muted' }, 'Only admins can manage staff accounts.'),
    ]));
    return;
  }

  const card = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:260px; flex:1' }, [
        el('div', { class: 'label' }, 'Search'),
        el('input', { class: 'input', id: 'st-q', placeholder: 'name, email, role' }),
      ]),
      el('div', { class: 'spacer' }),
      el('button', { class: 'btn primary', onclick: () => openStaffModal() }, 'Add Staff'),
      el('button', { class: 'btn', onclick: () => loadStaff() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'st-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  async function loadStaff() {
    const q = document.getElementById('st-q').value.trim();
    try {
      const res = await API.get('/staff', { q, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('st-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'staff_id', label: 'ID' },
          { key: 'user_name', label: 'Name' },
          { key: 'login_email', label: 'Email' },
          { key: 'role', label: 'Role' },
          { key: 'status', label: 'Status', render: (r) => badgeStatus(r.status) },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => `
              <button class="btn small" data-act="edit" data-id="${r.staff_id}">Edit</button>
              <button class="btn small danger" data-act="del" data-id="${r.staff_id}">Delete</button>
          `},
        ],
        rows,
        emptyText: 'No staff found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'edit') return openStaffModal(id);
          if (act === 'del') return deleteStaff(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function deleteStaff(id) {
    if (!confirm('Delete this staff member (and login account)?')) return;
    try {
      await API.del(`/staff/${id}`, {});
      toast('ok', 'Deleted', 'Staff removed');
      loadStaff();
    } catch (e) {
      toast('bad', 'Delete failed', e.message);
    }
  }

  async function openStaffModal(id) {
    const isEdit = !!id;
    let current = { user_name: '', login_email: '', password: '', role: 'Receptionist', salary: '', joining_date: '', status: 'active', user_status: 'active' };
    if (isEdit) {
      const res = await API.get(`/staff/${id}`);
      const s = res.data.staff;
      current = { ...current, role: s.role || '', salary: s.salary ?? '', joining_date: s.joining_date || '', status: s.status || 'active' };
    }
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Staff' : 'Add Staff'),
      !isEdit ? el('div', { class: 'muted' }, 'Creates a linked login account (role: staff).') : null,
      !isEdit ? el('div', { class: 'label' }, 'Login name') : null,
      !isEdit ? el('input', { class: 'input', id: 's-user', value: current.user_name }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Login email') : null,
      !isEdit ? el('input', { class: 'input', id: 's-email', type: 'email', value: current.login_email }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Password') : null,
      !isEdit ? el('input', { class: 'input', id: 's-pass', type: 'password', value: current.password }) : null,
      el('div', { class: 'label' }, 'Job Role'),
      el('input', { class: 'input', id: 's-role', value: current.role }),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Salary'), el('input', { class: 'input', id: 's-salary', type: 'number', step: '0.01', value: current.salary })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Joining date'), el('input', { class: 'input', id: 's-join', type: 'date', value: current.joining_date })]),
      ]),
      el('div', { class: 'label' }, 'Status'),
      el('select', { class: 'input', id: 's-status' }, [
        el('option', { value: 'active', selected: current.status === 'active' }, 'active'),
        el('option', { value: 'inactive', selected: current.status === 'inactive' }, 'inactive'),
      ]),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payload = {
              role: document.getElementById('s-role').value.trim(),
              salary: document.getElementById('s-salary').value ? Number(document.getElementById('s-salary').value) : null,
              joining_date: document.getElementById('s-join').value || null,
              status: document.getElementById('s-status').value,
            };
            try {
              if (isEdit) await API.put(`/staff/${id}`, payload);
              else await API.post('/staff', {
                user_name: document.getElementById('s-user').value.trim(),
                login_email: document.getElementById('s-email').value.trim(),
                password: document.getElementById('s-pass').value,
                ...payload,
              });
              toast('ok', 'Saved', 'Staff updated');
              backdrop.remove();
              loadStaff();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadStaff();
}

async function renderPatients(page) {
  page.innerHTML = '';
  const role = state.user.role;
  const canWrite = ['admin', 'staff'].includes(role);

  const card = el('div', { class: 'card full' }, [
    el('div', { class: 'row' }, [
      el('div', { style: 'min-width:260px; flex:1' }, [
        el('div', { class: 'label' }, 'Search'),
        el('input', { class: 'input', id: 'pt-q', placeholder: 'name, email, mobile' }),
      ]),
      el('div', { class: 'spacer' }),
      canWrite ? el('button', { class: 'btn primary', onclick: () => openPatientModal() }, 'Add Patient') : null,
      el('button', { class: 'btn', onclick: () => loadPatients() }, 'Load'),
    ]),
    el('div', { style: 'height:12px' }),
    el('div', { id: 'pt-table' }, el('div', { class: 'muted' }, 'Loading...')),
  ]);
  page.appendChild(card);

  async function loadPatients() {
    const q = document.getElementById('pt-q').value.trim();
    try {
      const res = await API.get('/patients', { q, page: 1, page_size: 50 });
      const rows = res.data.items || [];
      const root = document.getElementById('pt-table');
      root.innerHTML = '';
      root.appendChild(tableView({
        columns: [
          { key: 'patient_id', label: 'ID' },
          { key: 'name', label: 'Name' },
          { key: 'gender', label: 'Gender' },
          { key: 'mobile', label: 'Mobile' },
          { key: 'blood_group', label: 'Blood' },
          { key: 'actions', label: 'Actions', class: 'actions', render: (r) => {
            const view = `<button class="btn small" data-act="view" data-id="${r.patient_id}">View</button>`;
            if (!canWrite) return view;
            return `${view} <button class="btn small" data-act="edit" data-id="${r.patient_id}">Edit</button> <button class="btn small danger" data-act="del" data-id="${r.patient_id}">Delete</button>`;
          }},
        ],
        rows,
        emptyText: 'No patients found.',
      }));
      root.querySelectorAll('button[data-act]').forEach((b) => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const act = b.getAttribute('data-act');
          if (act === 'view') return viewPatient(id);
          if (act === 'edit') return openPatientModal(id);
          if (act === 'del') return deletePatient(id);
        });
      });
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function viewPatient(id) {
    try {
      const res = await API.get(`/patients/${id}`);
      const p = res.data.patient;
      const backdrop = el('div', { class: 'modal-backdrop' }, []);
      const modal = el('div', { class: 'modal' }, [
        el('h3', {}, `Patient #${p.patient_id}`),
        el('div', { class: 'muted' }, `${p.name} • ${p.gender || '-'} • ${p.mobile || '-'} • ${p.blood_group || '-'}`),
        el('div', { style: 'height:12px' }),
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Close'),
      ]);
      backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
      backdrop.appendChild(modal);
      document.body.appendChild(backdrop);
    } catch (e) {
      toast('bad', 'Load failed', e.message);
    }
  }

  async function deletePatient(id) {
    if (!confirm('Delete this patient (and login account)?')) return;
    try {
      await API.del(`/patients/${id}`, {});
      toast('ok', 'Deleted', 'Patient removed');
      loadPatients();
    } catch (e) {
      toast('bad', 'Delete failed', e.message);
    }
  }

  async function openPatientModal(id) {
    const isEdit = !!id;
    let current = { user_name: '', login_email: '', password: '', name: '', gender: '', dob: '', mobile: '', address: '', blood_group: '' };
    if (isEdit) {
      const res = await API.get(`/patients/${id}`);
      const p = res.data.patient;
      current = { ...current, name: p.name || '', gender: p.gender || '', dob: p.dob || '', mobile: p.mobile || '', address: p.address || '', blood_group: p.blood_group || '' };
    }
    const backdrop = el('div', { class: 'modal-backdrop' }, []);
    const modal = el('div', { class: 'modal' }, [
      el('h3', {}, isEdit ? 'Edit Patient' : 'Add Patient'),
      !isEdit ? el('div', { class: 'muted' }, 'Creates a linked login account (role: patient).') : null,
      !isEdit ? el('div', { class: 'label' }, 'Login name') : null,
      !isEdit ? el('input', { class: 'input', id: 'p-user', value: current.user_name }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Login email') : null,
      !isEdit ? el('input', { class: 'input', id: 'p-email', type: 'email', value: current.login_email }) : null,
      !isEdit ? el('div', { class: 'label' }, 'Password') : null,
      !isEdit ? el('input', { class: 'input', id: 'p-pass', type: 'password', value: current.password }) : null,
      el('div', { class: 'label' }, 'Name'),
      el('input', { class: 'input', id: 'p-name', value: current.name }),
      el('div', { class: 'row' }, [
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Gender'), el('select', { class: 'input', id: 'p-gender' }, [
          el('option', { value: '', selected: !current.gender }, '(select)'),
          el('option', { value: 'male', selected: current.gender === 'male' }, 'male'),
          el('option', { value: 'female', selected: current.gender === 'female' }, 'female'),
          el('option', { value: 'other', selected: current.gender === 'other' }, 'other'),
        ])]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'DOB'), el('input', { class: 'input', id: 'p-dob', type: 'date', value: current.dob })]),
        el('div', { style: 'flex:1' }, [el('div', { class: 'label' }, 'Blood Group'), el('input', { class: 'input', id: 'p-blood', value: current.blood_group })]),
      ]),
      el('div', { class: 'label' }, 'Mobile'),
      el('input', { class: 'input', id: 'p-mobile', value: current.mobile }),
      el('div', { class: 'label' }, 'Address'),
      el('input', { class: 'input', id: 'p-address', value: current.address }),
      el('div', { style: 'height:12px' }),
      el('div', { class: 'row' }, [
        el('button', { class: 'btn', onclick: () => backdrop.remove() }, 'Cancel'),
        el('div', { class: 'spacer' }),
        el('button', {
          class: 'btn primary',
          onclick: async () => {
            const payload = {
              name: document.getElementById('p-name').value.trim(),
              gender: document.getElementById('p-gender').value || null,
              dob: document.getElementById('p-dob').value || null,
              blood_group: document.getElementById('p-blood').value.trim() || null,
              mobile: document.getElementById('p-mobile').value.trim() || null,
              address: document.getElementById('p-address').value.trim() || null,
            };
            try {
              if (isEdit) await API.put(`/patients/${id}`, payload);
              else await API.post('/patients', {
                user_name: document.getElementById('p-user').value.trim(),
                login_email: document.getElementById('p-email').value.trim(),
                password: document.getElementById('p-pass').value,
                ...payload,
              });
              toast('ok', 'Saved', 'Patient updated');
              backdrop.remove();
              loadPatients();
            } catch (e) {
              toast('bad', 'Save failed', e.message);
            }
          },
        }, 'Save'),
      ]),
    ]);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.remove(); });
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
  }

  loadPatients();
}

window.addEventListener('hashchange', () => render());
boot();

