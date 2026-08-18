const { centersApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('banks-status');
const table = document.getElementById('admin-banks-table');
const tableBody = table?.querySelector('tbody');

const kpiTotal = document.getElementById('kpi-total');
const kpiActive = document.getElementById('kpi-active');
const kpiInactive = document.getElementById('kpi-inactive');
const banksCount = document.getElementById('banks-count');

const filterSearch = document.getElementById('filterSearch');
const filterStatus = document.getElementById('filterStatus');
const filterRegion = document.getElementById('filterRegion');
const filterClear = document.getElementById('filterClear');

const newBankModalEl = document.getElementById('newBankModal');
const newBankForm = document.getElementById('newBankForm');
const newBankError = document.getElementById('new-bank-error');
const saveBankButton = document.getElementById('saveBankButton');

const editBankModalEl = document.getElementById('editBankModal');
const editBankForm = document.getElementById('editBankForm');
const editBankError = document.getElementById('edit-bank-error');
const updateBankButton = document.getElementById('updateBankButton');

const editBankId = document.getElementById('editBankId');
const editBankName = document.getElementById('editBankName');
const editBankCode = document.getElementById('editBankCode');
const editBankAddress = document.getElementById('editBankAddress');
const editBankProvince = document.getElementById('editBankProvince');
const editBankCanton = document.getElementById('editBankCanton');
const editBankRegion = document.getElementById('editBankRegion');
const editBankContactName = document.getElementById('editBankContactName');
const editBankContactPhone = document.getElementById('editBankContactPhone');
const editBankContactEmail = document.getElementById('editBankContactEmail');
const editBankStatus = document.getElementById('editBankStatus');
const editBankDescription = document.getElementById('editBankDescription');

let centers = [];

function showStatus(message, type = 'info') {
  if (!statusEl) return;

  statusEl.textContent = message;

  statusEl.classList.remove(
    'd-none',
    'alert-success',
    'alert-danger',
    'alert-info'
  );

  statusEl.classList.add(`alert-${type}`);
}

function hideNewBankError() {
  if (!newBankError) return;

  newBankError.textContent = '';
  newBankError.classList.add('d-none');
}

function showNewBankError(message) {
  if (!newBankError) return;

  newBankError.textContent = message;
  newBankError.classList.remove('d-none');
}

function hideEditBankError() {
  if (!editBankError) return;

  editBankError.textContent = '';
  editBankError.classList.add('d-none');
}

function showEditBankError(message) {
  if (!editBankError) return;

  editBankError.textContent = message;
  editBankError.classList.remove('d-none');
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function setRowActive(row, active) {
  row.dataset.active = active ? '1' : '0';

  const badge = row.querySelector('.bank-status');

  if (badge) {
    badge.className =
      `badge-soft ${
        active
          ? 'badge-soft--green'
          : 'badge-soft--slate'
      } bank-status`;

    badge.innerHTML =
      `<span class="dot"></span>${active ? 'Activo' : 'Inactivo'}`;
  }

  const toggle = row.querySelector('.bank-active-toggle');

  if (toggle) {
    toggle.checked = active;
  }
}

function refreshKpis() {
  const rows = [
    ...(table?.querySelectorAll('tbody tr[data-bank-id]') ?? []),
  ];

  const visible = rows.filter(
    (row) => row.style.display !== 'none'
  );

  const active = visible.filter(
    (row) => row.dataset.active === '1'
  ).length;

  const inactive = visible.length - active;

  if (kpiTotal) {
    kpiTotal.textContent = String(visible.length);
  }

  if (kpiActive) {
    kpiActive.textContent = String(active);
  }

  if (kpiInactive) {
    kpiInactive.textContent = String(inactive);
  }

  if (banksCount) {
    banksCount.textContent =
      visible.length === 1
        ? '1 banco'
        : `${visible.length} bancos`;
  }
}

function applyFilters() {
  const q = (filterSearch?.value ?? '')
    .trim()
    .toLowerCase();

  const status = filterStatus?.value ?? '';
  const region = filterRegion?.value ?? '';

  table
    ?.querySelectorAll('tbody tr[data-bank-id]')
    .forEach((row) => {
      const name = row.dataset.name ?? '';
      const rowRegion = row.dataset.region ?? '';
      const active = row.dataset.active === '1';

      const matchesSearch =
        !q
        || name.includes(q)
        || rowRegion.toLowerCase().includes(q);

      const matchesStatus =
        !status
        || (status === 'active' && active)
        || (status === 'inactive' && !active);

      const matchesRegion =
        !region || rowRegion === region;

      row.style.display =
        matchesSearch
        && matchesStatus
        && matchesRegion
          ? ''
          : 'none';
    });

  refreshKpis();
}

function populateRegionFilter(centerList) {
  if (!filterRegion) return;

  const regions = [
    ...new Set(
      centerList
        .map(
          (center) =>
            center.region || center.province
        )
        .filter(Boolean)
    ),
  ].sort();

  const current = filterRegion.value;

  filterRegion.innerHTML =
    '<option value="">Todas</option>'
    + regions
      .map(
        (region) =>
          `<option value="${escapeHtml(region)}">${escapeHtml(region)}</option>`
      )
      .join('');

  if (regions.includes(current)) {
    filterRegion.value = current;
  }
}

function renderCenters(centerList) {
  if (!tableBody) return;

  if (centerList.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-4">
          No hay bancos registrados.
        </td>
      </tr>
    `;

    if (kpiTotal) {
      kpiTotal.textContent = '0';
    }

    if (kpiActive) {
      kpiActive.textContent = '0';
    }

    if (kpiInactive) {
      kpiInactive.textContent = '0';
    }

    if (banksCount) {
      banksCount.textContent = '0 bancos';
    }

    populateRegionFilter(centerList);

    return;
  }

  tableBody.innerHTML = centerList
    .map((center) => {
      const active = Boolean(center.active);

      const region =
        center.region
        || center.province
        || '';

      const code =
        center.code
        || `ID ${center.id}`;

      return `
        <tr
          data-bank-id="${center.id}"
          data-active="${active ? '1' : '0'}"
          data-region="${escapeHtml(region)}"
          data-name="${escapeHtml(
            String(center.name ?? '').toLowerCase()
          )}"
        >
          <td>
            <div class="fw-semibold">
              ${escapeHtml(center.name)}
            </div>

            <div
              class="text-muted"
              style="font-size: 0.75rem"
            >
              ${escapeHtml(code)}
              ·
              ${escapeHtml(center.address || '')}
            </div>
          </td>

          <td
            class="fw-semibold"
            style="font-size: 0.875rem"
          >
            ${escapeHtml(region || '—')}
          </td>

          <td
            class="text-muted"
            style="font-size: 0.875rem"
          >
            ${escapeHtml(
              center.contact_phone || '—'
            )}
          </td>

          <td>
            <span
              class="badge-soft ${
                active
                  ? 'badge-soft--green'
                  : 'badge-soft--slate'
              } bank-status"
            >
              <span class="dot"></span>
              ${active ? 'Activo' : 'Inactivo'}
            </span>
          </td>

          <td class="text-end">
            <div
              class="d-flex justify-content-end align-items-center gap-2"
            >
              <div class="form-check form-switch m-0">
                <input
                  class="form-check-input bank-active-toggle"
                  type="checkbox"
                  role="switch"
                  aria-label="Activar ${escapeHtml(center.name)}"
                  ${active ? 'checked' : ''}
                />
              </div>

              <button
                type="button"
                class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold bank-edit-button"
                data-bank-id="${center.id}"
              >
                Editar
              </button>
            </div>
          </td>
        </tr>
      `;
    })
    .join('');

  populateRegionFilter(centerList);
  applyFilters();
}

async function loadCenters(showMessage = true) {
  const payload = await centersApi.list({
    all: true,
  });

  centers =
    Array.isArray(payload?.data)
      ? payload.data
      : [];

  renderCenters(centers);

  if (showMessage) {
    showStatus(
      centers.length === 1
        ? '1 banco cargado correctamente.'
        : `${centers.length} bancos cargados correctamente.`,
      'info'
    );
  }
}

function openEditModal(center) {
  hideEditBankError();

  if (editBankId) {
    editBankId.value = String(center.id ?? '');
  }

  if (editBankName) {
    editBankName.value = center.name ?? '';
  }

  if (editBankCode) {
    editBankCode.value = center.code ?? '';
  }

  if (editBankAddress) {
    editBankAddress.value = center.address ?? '';
  }

  if (editBankProvince) {
    editBankProvince.value = center.province ?? '';
  }

  if (editBankCanton) {
    editBankCanton.value = center.canton ?? '';
  }

  if (editBankRegion) {
    editBankRegion.value = center.region ?? '';
  }

  if (editBankContactName) {
    editBankContactName.value = center.contact_name ?? '';
  }

  if (editBankContactPhone) {
    editBankContactPhone.value = center.contact_phone ?? '';
  }

  if (editBankContactEmail) {
    editBankContactEmail.value = center.contact_email ?? '';
  }

  if (editBankStatus) {
    editBankStatus.value =
      center.active ? 'true' : 'false';
  }

  if (editBankDescription) {
    editBankDescription.value = center.description ?? '';
  }

  if (
    editBankModalEl
    && window.bootstrap?.Modal
  ) {
    const modal =
      window.bootstrap.Modal.getOrCreateInstance(
        editBankModalEl
      );

    modal.show();
  }
}

table?.addEventListener(
  'click',
  (event) => {
    const editButton = event.target.closest(
      '.bank-edit-button'
    );

    if (!editButton) return;

    const id = editButton.dataset.bankId;

    const center = centers.find(
      (item) => String(item.id) === String(id)
    );

    if (!center) {
      showStatus(
        'No se pudo cargar la información del banco.',
        'danger'
      );

      return;
    }

    openEditModal(center);
  }
);

table?.addEventListener(
  'change',
  async (event) => {
    const toggle = event.target.closest(
      '.bank-active-toggle'
    );

    if (!toggle) return;

    const row = toggle.closest('tr');

    if (!row) return;

    const id = row.dataset.bankId;

    const name =
      row
        .querySelector('td .fw-semibold')
        ?.textContent
        ?.trim()
      ?? 'Banco';

    const newActive = toggle.checked;
    const previousActive =
      row.dataset.active === '1';

    toggle.disabled = true;

    try {
      await centersApi.updateActive(
        id,
        newActive
      );

      setRowActive(row, newActive);

      const storedCenter = centers.find(
        (center) =>
          String(center.id) === String(id)
      );

      if (storedCenter) {
        storedCenter.active = newActive;
      }

      refreshKpis();

      showStatus(
        `${name} fue ${
          newActive
            ? 'activado'
            : 'desactivado'
        } correctamente.`,
        'success'
      );
    } catch (error) {
      setRowActive(
        row,
        previousActive
      );

      refreshKpis();

      showStatus(
        error?.message
          || `No se pudo actualizar el estado de ${name}.`,
        'danger'
      );
    } finally {
      toggle.disabled = false;
    }
  }
);

newBankForm?.addEventListener(
  'submit',
  async (event) => {
    event.preventDefault();

    hideNewBankError();

    const formData =
      new FormData(newBankForm);

    const name = String(
      formData.get('name') ?? ''
    ).trim();

    const address = String(
      formData.get('address') ?? ''
    ).trim();

    if (!name) {
      showNewBankError(
        'Debes ingresar el nombre del banco.'
      );

      return;
    }

    if (!address) {
      showNewBankError(
        'Debes ingresar la dirección del banco.'
      );

      return;
    }

    const data = {
      name,
      address,

      code: String(
        formData.get('code') ?? ''
      ).trim(),

      province: String(
        formData.get('province') ?? ''
      ).trim(),

      canton: String(
        formData.get('canton') ?? ''
      ).trim(),

      region: String(
        formData.get('region') ?? ''
      ).trim(),

      contact_name: String(
        formData.get('contact_name') ?? ''
      ).trim(),

      contact_phone: String(
        formData.get('contact_phone') ?? ''
      ).trim(),

      contact_email: String(
        formData.get('contact_email') ?? ''
      ).trim(),

      description: String(
        formData.get('description') ?? ''
      ).trim(),

      active:
        String(
          formData.get('active') ?? 'true'
        ) === 'true',
    };

    if (saveBankButton) {
      saveBankButton.disabled = true;
      saveBankButton.textContent = 'Guardando…';
    }

    try {
      await centersApi.create(data);

      newBankForm.reset();

      const statusSelect =
        document.getElementById('bankStatus');

      if (statusSelect) {
        statusSelect.value = 'true';
      }

      if (
        newBankModalEl
        && window.bootstrap?.Modal
      ) {
        const modal =
          window.bootstrap.Modal.getOrCreateInstance(
            newBankModalEl
          );

        modal.hide();
      }

      await loadCenters(false);

      showStatus(
        `${name} fue registrado correctamente.`,
        'success'
      );
    } catch (error) {
      showNewBankError(
        error?.message
          || 'No se pudo registrar el banco.'
      );
    } finally {
      if (saveBankButton) {
        saveBankButton.disabled = false;
        saveBankButton.textContent = 'Guardar banco';
      }
    }
  }
);

editBankForm?.addEventListener(
  'submit',
  async (event) => {
    event.preventDefault();

    hideEditBankError();

    const id = editBankId?.value ?? '';

    if (!id) {
      showEditBankError(
        'No se pudo identificar el banco.'
      );

      return;
    }

    const formData =
      new FormData(editBankForm);

    const name = String(
      formData.get('name') ?? ''
    ).trim();

    const address = String(
      formData.get('address') ?? ''
    ).trim();

    if (!name) {
      showEditBankError(
        'Debes ingresar el nombre del banco.'
      );

      return;
    }

    if (!address) {
      showEditBankError(
        'Debes ingresar la dirección del banco.'
      );

      return;
    }

    const data = {
      name,
      address,

      code: String(
        formData.get('code') ?? ''
      ).trim(),

      province: String(
        formData.get('province') ?? ''
      ).trim(),

      canton: String(
        formData.get('canton') ?? ''
      ).trim(),

      region: String(
        formData.get('region') ?? ''
      ).trim(),

      contact_name: String(
        formData.get('contact_name') ?? ''
      ).trim(),

      contact_phone: String(
        formData.get('contact_phone') ?? ''
      ).trim(),

      contact_email: String(
        formData.get('contact_email') ?? ''
      ).trim(),

      description: String(
        formData.get('description') ?? ''
      ).trim(),

      active:
        String(
          formData.get('active') ?? 'true'
        ) === 'true',
    };

    if (updateBankButton) {
      updateBankButton.disabled = true;
      updateBankButton.textContent = 'Guardando…';
    }

    try {
      await centersApi.update(
        id,
        data
      );

      if (
        editBankModalEl
        && window.bootstrap?.Modal
      ) {
        const modal =
          window.bootstrap.Modal.getOrCreateInstance(
            editBankModalEl
          );

        modal.hide();
      }

      await loadCenters(false);

      showStatus(
        `${name} fue actualizado correctamente.`,
        'success'
      );
    } catch (error) {
      showEditBankError(
        error?.message
          || 'No se pudo actualizar el banco.'
      );
    } finally {
      if (updateBankButton) {
        updateBankButton.disabled = false;
        updateBankButton.textContent = 'Guardar cambios';
      }
    }
  }
);

newBankModalEl?.addEventListener(
  'hidden.bs.modal',
  () => {
    hideNewBankError();

    newBankForm?.reset();

    const statusSelect =
      document.getElementById('bankStatus');

    if (statusSelect) {
      statusSelect.value = 'true';
    }
  }
);

editBankModalEl?.addEventListener(
  'hidden.bs.modal',
  () => {
    hideEditBankError();
    editBankForm?.reset();

    if (editBankId) {
      editBankId.value = '';
    }
  }
);

filterSearch?.addEventListener(
  'input',
  applyFilters
);

filterStatus?.addEventListener(
  'change',
  applyFilters
);

filterRegion?.addEventListener(
  'change',
  applyFilters
);

filterClear?.addEventListener(
  'click',
  () => {
    if (filterSearch) {
      filterSearch.value = '';
    }

    if (filterStatus) {
      filterStatus.value = '';
    }

    if (filterRegion) {
      filterRegion.value = '';
    }

    applyFilters();
  }
);

try {
  await loadCenters();
} catch (error) {
  showStatus(
    error?.message
      || 'No se pudieron cargar los bancos.',
    'danger'
  );
}