document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('#sidebar');
  const scrim = document.querySelector('.mobile-scrim');
  const closeMenu = () => { sidebar?.classList.remove('menu-open'); scrim?.classList.remove('visible'); };
  document.querySelectorAll('[data-menu-open]').forEach(button => button.addEventListener('click', () => { sidebar?.classList.add('menu-open'); scrim?.classList.add('visible'); }));
  document.querySelectorAll('[data-menu-close]').forEach(button => button.addEventListener('click', closeMenu));
  document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => button.closest('.flash')?.remove()));
  document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (input) input.type = input.type === 'password' ? 'text' : 'password';
  }));
  const search = document.querySelector('[data-table-search]');
  search?.addEventListener('input', () => {
    const needle = search.value.trim().toLocaleLowerCase();
    document.querySelectorAll('.workspace-table tbody tr').forEach(row => {
      row.hidden = needle !== '' && !row.textContent.toLocaleLowerCase().includes(needle);
    });
  });
  const groupSelect = document.querySelector('[data-target-group]');
  const customerTarget = document.querySelector('[data-customer-target]');
  const syncCustomerTarget = () => {
    if (!customerTarget || !groupSelect) return;
    customerTarget.required = groupSelect.value === 'pelanggan';
    customerTarget.disabled = groupSelect.value !== 'pelanggan';
  };
  groupSelect?.addEventListener('change', syncCustomerTarget);
  syncCustomerTarget();
  const templateSelect = document.querySelector('[data-message-template]');
  const messageBody = document.querySelector('[data-message-body]');
  templateSelect?.addEventListener('change', () => {
    const body = templateSelect.selectedOptions[0]?.dataset.body;
    if (body && messageBody) messageBody.value = body;
  });

  document.querySelectorAll('[data-generate-customer-id]').forEach(button => button.addEventListener('click', async () => {
    const input = document.querySelector('#field-customer-code');
    const previousLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Memuat…';
    try {
      const response = await fetch(button.dataset.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = await response.json();
      if (!response.ok || !/^\d{10}$/.test(data.customer_id || '')) throw new Error(data.error || 'ID pelanggan gagal dibuat.');
      if (input) input.value = data.customer_id;
    } catch (error) {
      window.alert(error.message || 'ID pelanggan gagal dibuat. Muat ulang halaman.');
    } finally {
      button.disabled = false;
      button.textContent = previousLabel;
    }
  }));

  const voucherForm = document.querySelector('[data-voucher-form]');
  if (voucherForm) {
    const branchSelect = voucherForm.querySelector('[data-voucher-branch]');
    const routerSelect = voucherForm.querySelector('[data-voucher-router]');
    const profileSelect = voucherForm.querySelector('[data-voucher-profile]');
    const priceInput = voucherForm.querySelector('[data-voucher-price]');
    const syncRouters = () => {
      const branchId = branchSelect?.value;
      [...routerSelect.options].forEach((option, index) => {
        if (index === 0) return;
        option.hidden = Boolean(branchId && option.dataset.branch !== branchId);
      });
      routerSelect.value = '';
      profileSelect.innerHTML = '<option value="">Pilih router terlebih dahulu</option>';
      profileSelect.disabled = true;
      priceInput.value = '';
    };
    branchSelect?.addEventListener('change', syncRouters);
    routerSelect?.addEventListener('change', async () => {
      const routerId = routerSelect.value;
      profileSelect.innerHTML = '<option value="">Memuat profil router…</option>';
      profileSelect.disabled = true;
      priceInput.value = '';
      if (!routerId) return;
      try {
        const response = await fetch(`${voucherForm.dataset.profileUrl}/${encodeURIComponent(routerId)}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Profil router gagal dimuat.');
        profileSelect.innerHTML = '<option value="">Pilih profil hotspot</option>';
        data.profiles.forEach(profile => {
          const option = document.createElement('option');
          option.value = profile.name;
          option.textContent = profile.price === null ? profile.name : `${profile.name} · Rp ${Number(profile.price).toLocaleString('id-ID')}`;
          option.dataset.price = profile.price === null ? '' : String(profile.price);
          profileSelect.append(option);
        });
        profileSelect.disabled = false;
        if (!data.profiles.length) profileSelect.innerHTML = '<option value="">Tidak ada profil hotspot di router</option>';
      } catch (error) {
        profileSelect.innerHTML = `<option value="">${error.message}</option>`;
      }
    });
    profileSelect?.addEventListener('change', () => {
      const price = profileSelect.selectedOptions[0]?.dataset.price;
      priceInput.value = price ? `Rp ${Number(price).toLocaleString('id-ID')}` : 'Comment bukan angka bulat';
    });
  }

  const customerForm = document.querySelector('form[action$="/area/customers/simpan"]');
  if (customerForm) {
    const branch = customerForm.querySelector('#field-branch_id');
    const service = customerForm.querySelector('#field-service_type');
    const mode = customerForm.querySelector('#field-connection_mode');
    const staticQueueHelp = customerForm.querySelector('[data-static-queue-help]');
    const branchFields = [...customerForm.querySelectorAll('[data-branch-filter]')];
    const connectionFields = [...customerForm.querySelectorAll('[data-connection-mode]')];
    const applyFilters = () => {
      branchFields.forEach(select => {
        [...select.options].forEach((option, index) => {
          if (!index) return;
          const branchMismatch = option.dataset.branch && branch?.value && option.dataset.branch !== branch.value;
          const serviceMismatch = select.hasAttribute('data-service-filter') && option.dataset.service && service?.value && option.dataset.service !== service.value;
          option.hidden = Boolean(branchMismatch || serviceMismatch);
          if (option.hidden && option.selected) select.value = '';
        });
      });
      connectionFields.forEach(wrapper => {
        const visible = wrapper.dataset.connectionMode === service?.value;
        wrapper.hidden = !visible;
        const input = wrapper.querySelector('input, select');
        if (input) input.required = visible && (input.id === 'field-mikrotik_username' || input.id === 'field-mikrotik_password' || input.id === 'field-static_ip');
      });
      if (mode) {
        const createOption = [...mode.options].find(option => option.value === 'create');
        const staticOnly = service?.value === 'static_ip';
        if (createOption) createOption.hidden = staticOnly;
        if (staticOnly) {
          mode.value = 'sync';
          mode.disabled = true;
          if (!customerForm.querySelector('input[name="connection_mode"][type="hidden"]')) {
            const fixedMode = document.createElement('input');
            fixedMode.type = 'hidden';
            fixedMode.name = 'connection_mode';
            fixedMode.value = 'sync';
            customerForm.append(fixedMode);
          }
        } else {
          mode.disabled = false;
          customerForm.querySelector('input[name="connection_mode"][type="hidden"]')?.remove();
        }
        if (staticQueueHelp) staticQueueHelp.hidden = !staticOnly;
      }
    };
    branch?.addEventListener('change', applyFilters);
    service?.addEventListener('change', applyFilters);
    applyFilters();
  }

  const routerMonitor = document.querySelector('[data-router-monitor]');
  if (routerMonitor) {
    const lastCheck = routerMonitor.querySelector('[data-router-last-check]');
    const formatMemory = bytes => `${(Number(bytes || 0) / (1024 * 1024)).toFixed(0)} MB`;
    const refreshRouterStatus = async () => {
      try {
        const response = await fetch(routerMonitor.dataset.statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Status router gagal dimuat.');
        data.routers.forEach(router => {
          const row = document.querySelector(`[data-router-row="${CSS.escape(String(router.id))}"]`);
          if (!row) return;
          row.querySelector('[data-router-identity]').textContent = router.identity || '—';
          row.querySelector('[data-router-uptime]').textContent = router.uptime || '—';
          row.querySelector('[data-router-cpu]').textContent = router.cpu === null ? '—' : `${router.cpu}%`;
          const total = Number(router.memory_total || 0);
          const used = Number(router.memory_used || 0);
          row.querySelector('[data-router-memory]').textContent = total ? `${formatMemory(used)} / ${formatMemory(total)} (${Math.round(used * 100 / total)}%)` : '—';
        });
        if (lastCheck) lastCheck.textContent = `Terakhir diperbarui ${new Date(data.checked_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;
      } catch (error) {
        if (lastCheck) lastCheck.textContent = error.message || 'Gagal memuat status';
      }
    };
    refreshRouterStatus();
    window.setInterval(refreshRouterStatus, 60_000);
  }

  document.querySelectorAll('[data-stock-search]').forEach(search => {
    const select = document.getElementById(search.dataset.stockSearch);
    if (!select) return;
    search.addEventListener('input', () => {
      const term = search.value.trim().toLocaleLowerCase();
      Array.from(select.options).forEach((option, index) => {
        if (index === 0) return;
        const isOnuInForm = select.hasAttribute('data-stock-in-select') && option.dataset.category === 'ont_onu';
        option.hidden = isOnuInForm || (term !== '' && !option.textContent.toLocaleLowerCase().includes(term));
      });
    });
  });

  document.querySelectorAll('[data-stock-form]').forEach(form => {
    const product = form.querySelector('[data-stock-product-select]');
    const serialField = form.querySelector('[data-stock-serial-field]');
    const serialSelect = form.querySelector('[data-stock-serial-select]');
    const quantity = form.querySelector('[data-stock-quantity]');
    if (!product || !serialField || !serialSelect || !quantity) return;
    const updateSerialOptions = () => {
      const isOnu = product.selectedOptions[0]?.dataset.category === 'ont_onu';
      serialField.hidden = !isOnu;
      serialSelect.disabled = !isOnu;
      serialSelect.required = isOnu;
      quantity.step = isOnu ? '1' : '0.01';
      if (!isOnu) {
        Array.from(serialSelect.options).forEach(option => { option.selected = false; });
        quantity.removeAttribute('max');
        return;
      }
      Array.from(serialSelect.options).forEach(option => {
        option.hidden = option.value !== '' && option.dataset.product !== product.value;
        if (option.hidden) option.selected = false;
      });
      const available = Array.from(serialSelect.options).filter(option => option.value && !option.hidden).length;
      quantity.max = String(available);
    };
    product.addEventListener('change', updateSerialOptions);
    serialSelect.addEventListener('change', () => { if (serialSelect.selectedOptions.length) quantity.value = String(serialSelect.selectedOptions.length); });
    updateSerialOptions();
  });
});
