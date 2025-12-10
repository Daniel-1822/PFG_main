<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Ubuntu', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #1a1a1a;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 600px;
            background: #2c2c2c;
            border-radius: 26px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .left-panel {
            flex: 0 0 60%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .left-panel h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #FFC107;
        }

        .left-panel p {
            font-size: 1.2rem;
            color: #b0b0b0;
            line-height: 1.5;
        }

        .right-panel {
            flex: 0 0 40%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #3a3a3a;
            border-top-right-radius: 26px;
            border-bottom-right-radius: 26px;
            transition: all 0.3s ease;
        }

        .right-panel.expanded {
            flex: 0 0 100%;
            border-top-left-radius: 26px;
            border-bottom-left-radius: 26px;
        }

        .form-container {
            display: none;
            width: 100%;
            max-width: 400px;
        }

        .form-container.active {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .page-title {
            margin-bottom: 1.5rem;
            color: #e0e0e0;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .input-group {
            text-align: left;
            width: 100%;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #b0b0b0;
            font-size: 0.9rem;
            font-weight: 400;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #444;
            border-radius: 26px;
            font-size: 1rem;
            color: #e0e0e0;
            background: #2c2c2c;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%23b0b0b0' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            cursor: pointer;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: #FFC107;
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
        }

        .input-group input::placeholder {
            color: #777;
        }

        .btn {
            padding: 0.8rem;
            border: none;
            border-radius: 26px;
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .login-btn {
            background: #FFC107;
            color: #1a1a1a;
        }

        .login-btn:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-btn {
            background: #555;
            color: #e0e0e0;
        }

        .register-btn:hover {
            background: #666;
            transform: translateY(-2px);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .toggle-btn {
            background: none;
            border: 1px solid #FFC107;
            color: #FFC107;
            padding: 0.5rem 1rem;
            border-radius: 26px;
            cursor: pointer;
            font-size: 0.9rem;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            background: #FFC107;
            color: #1a1a1a;
            transform: translateY(-2px);
        }

        .toggle-btn:active {
            transform: translateY(0);
        }

        .forgot-password {
            display: block;
            color: #FFC107;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 400;
            margin-top: 1rem;
            text-align: center;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            text-decoration: underline;
            color: #e0a800;
        }

        .success-message, .error-message {
            padding: 1rem;
            border-radius: 26px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
            text-align: center;
        }

        .success-message {
            background: #28a745;
            color: white;
        }

        .error-message {
            background: #dc3545;
            color: white;
        }

        .success-message.active, .error-message.active {
            display: block;
        }

        /* Validación de formularios */
        .input-group input:invalid:not(:focus):not(:placeholder-shown) {
            border-color: #dc3545;
        }

        .input-group input:valid:not(:focus):not(:placeholder-shown) {
            border-color: #28a745;
        }

        /* Animaciones suaves */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-container.active {
            animation: slideIn 0.3s ease-out;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                height: auto;
                max-width: 400px;
                margin: 1rem;
            }

            .left-panel {
                flex: 0 0 auto;
                padding: 1.5rem;
                text-align: center;
                align-items: center;
            }

            .left-panel h1 {
                font-size: 2rem;
            }

            .left-panel p {
                font-size: 1rem;
            }

            .right-panel {
                flex: 0 0 auto;
                padding: 1.5rem;
                border-top-right-radius: 0;
                border-bottom-left-radius: 26px;
                border-bottom-right-radius: 26px;
            }

            .right-panel.expanded {
                border-top-left-radius: 0;
                border-bottom-left-radius: 26px;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                margin: 0.5rem;
            }

            .left-panel, .right-panel {
                padding: 1rem;
            }

            .left-panel h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="left-panel" id="left-panel">
            <h1>NoteEd</h1>
            <p>Graba tus clases y genera resúmenes automáticos.</p>
        </div>
        <div class="right-panel" id="right-panel">
            <h2 class="page-title" id="page-title">Iniciar Sesión</h2>
            
            <div id="success-message" class="success-message">
                ¡Registro exitoso! Ahora puedes iniciar sesión.
            </div>
            
            <div id="error-message" class="error-message">
                <!-- Aquí se mostrarán los errores -->
            </div>
            
            <!-- Formulario de Login -->
            <div id="login-form" class="form-container active">
                <form action="login.php" method="POST" novalidate>
                    <div class="input-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" placeholder="Ingresa tu usuario" required minlength="3" maxlength="20">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required minlength="6">
                    </div>
                    <br>
                    <button type="submit" class="btn login-btn">Iniciar Sesión</button>
                </form>
                <button id="to-register" class="toggle-btn">¿No tienes cuenta? Registrarse</button>
            </div>

            <!-- Formulario de Registro -->
            <div id="register-form" class="form-container">
                <form action="register.php" method="POST" novalidate>
                    <div class="input-group">
                        <label for="reg-username">Usuario</label>
                        <input type="text" id="reg-username" name="username" placeholder="Ingresa tu usuario" required minlength="3" maxlength="20">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-first-name">Nombre</label>
                        <input type="text" id="reg-first-name" name="first_name" placeholder="Ingresa tu nombre" required maxlength="50">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-last-name">Apellidos</label>
                        <input type="text" id="reg-last-name" name="last_name" placeholder="Ingresa tus apellidos" required maxlength="50">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-email">Email</label>
                        <input type="email" id="reg-email" name="email" placeholder="Ingresa tu email" required maxlength="100">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-role">Rol</label>
                        <select id="reg-role" name="role" required>
                            <option value="" disabled selected>Selecciona tu rol</option>
                            <option value="docente">Docente</option>
                            <option value="alumno">Alumno</option>
                        </select>
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-password">Contraseña</label>
                        <input type="password" id="reg-password" name="password" placeholder="Ingresa tu contraseña" required minlength="6">
                    </div>
                    <br>
                    <div class="input-group">
                        <label for="reg-confirm-password">Confirmar Contraseña</label>
                        <input type="password" id="reg-confirm-password" name="confirm_password" placeholder="Confirma tu contraseña" required minlength="6">
                    </div>
                    <br>
                    <button type="submit" class="btn login-btn">Registrarse</button>
                </form>
                <button id="to-login" class="toggle-btn">¿Ya tienes cuenta? Iniciar Sesión</button>
            </div>
        </div>
    </div>

    <script>
        const toRegisterBtn = document.getElementById('to-register');
        const toLoginBtn = document.getElementById('to-login');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const pageTitle = document.getElementById('page-title');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const leftPanel = document.getElementById('left-panel');
        const rightPanel = document.getElementById('right-panel');

        // Función para cambiar al formulario de registro
        function showRegisterForm() {
            loginForm.classList.remove('active');
            registerForm.classList.add('active');
            pageTitle.textContent = 'Registrarse';
            hideMessages();
            
            // Ocultar panel izquierdo en pantallas grandes para dar más espacio
            if (window.innerWidth > 768) {
                leftPanel.style.display = 'none';
                rightPanel.classList.add('expanded');
            }
        }

        // Función para cambiar al formulario de login
        function showLoginForm() {
            registerForm.classList.remove('active');
            loginForm.classList.add('active');
            pageTitle.textContent = 'Iniciar Sesión';
            hideMessages();
            
            // Mostrar panel izquierdo nuevamente
            if (window.innerWidth > 768) {
                leftPanel.style.display = 'flex';
                rightPanel.classList.remove('expanded');
            }
        }

        // Función para ocultar mensajes
        function hideMessages() {
            successMessage.classList.remove('active');
            errorMessage.classList.remove('active');
        }

        // Función para mostrar mensaje de error
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.add('active');
            successMessage.classList.remove('active');
        }

        // Event listeners para cambio de formularios
        toRegisterBtn.addEventListener('click', showRegisterForm);
        toLoginBtn.addEventListener('click', showLoginForm);

        // Validación de contraseñas coincidentes
        const regPassword = document.getElementById('reg-password');
        const regConfirmPassword = document.getElementById('reg-confirm-password');

        function validatePasswordMatch() {
            if (regPassword.value !== regConfirmPassword.value) {
                regConfirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                regConfirmPassword.setCustomValidity('');
            }
        }

        regPassword.addEventListener('input', validatePasswordMatch);
        regConfirmPassword.addEventListener('input', validatePasswordMatch);

        // Validación en tiempo real
        const inputs = document.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.checkValidity()) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#28a745';
                }
            });

            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#28a745';
                } else if (this.value.length > 0) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#444';
                }
            });
        });

        // Manejo de envío de formularios con validación
        document.querySelector('#login-form form').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                showError('Por favor, completa todos los campos correctamente.');
            }
        });

        document.querySelector('#register-form form').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                showError('Por favor, completa todos los campos correctamente.');
                return;
            }

            // Validación adicional de contraseñas
            if (regPassword.value !== regConfirmPassword.value) {
                e.preventDefault();
                showError('Las contraseñas no coinciden.');
                return;
            }

            if (regPassword.value.length < 6) {
                e.preventDefault();
                showError('La contraseña debe tener al menos 6 caracteres.');
                return;
            }
        });

        // Mostrar mensaje de éxito si viene de registro exitoso
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            successMessage.classList.add('active');
            showLoginForm();
        }

        // Mostrar mensaje de error si viene con parámetro de error
        if (urlParams.get('error')) {
            const errorType = urlParams.get('error');
            let errorText = 'Ha ocurrido un error.';
            
            switch(errorType) {
                case 'invalid_credentials':
                    errorText = 'Usuario o contraseña incorrectos.';
                    break;
                case 'user_exists':
                    errorText = 'El usuario ya existe.';
                    showRegisterForm();
                    break;
                case 'email_exists':
                    errorText = 'El email ya está registrado.';
                    showRegisterForm();
                    break;
                case 'password_mismatch':
                    errorText = 'Las contraseñas no coinciden.';
                    showRegisterForm();
                    break;
                default:
                    errorText = 'Ha ocurrido un error inesperado.';
            }
            
            showError(errorText);
        }

        // Ajustar layout en cambio de tamaño de ventana
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                leftPanel.style.display = 'flex';
                rightPanel.classList.remove('expanded');
            } else {
                if (registerForm.classList.contains('active')) {
                    leftPanel.style.display = 'none';
                    rightPanel.classList.add('expanded');
                }
            }
        });
    </script>
</body>
</html>