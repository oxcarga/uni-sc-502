import { usersApi } from '../js/api.js';

const form = document.getElementById('registro-form');

if (form) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    console.log('Registration attempt', data);

    try {
      await usersApi.create({
        firstName: data.firstName,
        lastName: data.lastName,
        email: data.email,
        password: data.password,
      });
    } catch (error) {
      console.error('Registration failed', error);
    }
  });
}
