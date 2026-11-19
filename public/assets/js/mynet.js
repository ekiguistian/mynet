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
});
