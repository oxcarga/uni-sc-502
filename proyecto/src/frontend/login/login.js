const form = document.getElementById('login-form');

if (form) {
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const data = new FormData(form);
    console.log('Login attempt', Object.fromEntries(data.entries()));
  });
}
