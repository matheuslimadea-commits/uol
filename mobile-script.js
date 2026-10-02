document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('toggle-password');

    togglePassword.addEventListener('click', function () {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.textContent = 'ocultar';
        } else {
            passwordInput.type = 'password';
            togglePassword.textContent = 'exibir';
        }
    });

    const loginForm = document.getElementById('login-form');
    loginForm.addEventListener('submit', function (e) {
        // e.preventDefault();
        console.log('Formulário enviado');
    });
});
