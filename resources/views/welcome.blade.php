<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>WeDumbell Login</title>

        <style>
            :root {
                --bg: #f4f1ec;
                --panel: #ffffff;
                --ink: #111111;
                --muted: #606060;
                --line: #a9a9a9;
                --shadow: 0 10px 24px rgba(17, 17, 17, 0.14);
                --radius-xl: 26px;
                --radius-lg: 14px;
                --radius-pill: 999px;
                --text-xs: 0.75rem;
                --text-sm: 0.875rem;
                --text-md: 1rem;
                --text-lg: 1.125rem;
                --text-xl: 1.375rem;
                --text-2xl: clamp(1.6rem, 2.2vw, 2rem);
                --text-3xl: clamp(1.9rem, 2.8vw, 2.5rem);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: Georgia, "Times New Roman", serif;
                font-size: 16px;
                line-height: 1.45;
                background:
                    radial-gradient(circle at top left, rgba(255, 255, 255, 0.95), transparent 38%),
                    linear-gradient(135deg, #f6f4f0 0%, #ece7df 100%);
                color: var(--ink);
            }

            a {
                color: inherit;
            }

            .page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px 16px;
            }

            .auth-shell {
                width: min(1120px, 100%);
                position: relative;
                background: rgba(255, 255, 255, 0.34);
                border-radius: 30px;
                overflow: hidden;
                box-shadow: 0 22px 60px rgba(26, 26, 26, 0.08);
                backdrop-filter: blur(4px);
                min-height: 640px;
            }

            .screens-track {
                display: flex;
                width: 200%;
                min-height: 640px;
                transition: transform 0.52s cubic-bezier(0.22, 1, 0.36, 1);
                will-change: transform;
            }

            .auth-shell[data-screen="signup"] .screens-track {
                transform: translateX(-50%);
            }

            .form-side,
            .brand-side {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 640px;
            }

            .form-side {
                padding: 48px 32px;
            }

            .login-card {
                width: min(100%, 380px);
                background: var(--panel);
                border: 1.5px solid var(--line);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow);
                padding: 34px 22px 30px;
            }

            .signup-card {
                width: min(100%, 420px);
                background: var(--panel);
                border: 1.5px solid var(--line);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow);
                padding: 28px 20px 16px;
            }

            .login-card h1 {
                margin: 0 0 34px;
                text-align: center;
                font-size: var(--text-2xl);
                font-weight: 700;
                line-height: 1.2;
            }

            .signup-card h1 {
                margin: 0;
                text-align: center;
                font-size: var(--text-2xl);
                font-weight: 700;
                line-height: 1.15;
            }

            .signup-subtitle {
                margin: 4px auto 16px;
                max-width: 320px;
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-sm);
                font-weight: 700;
                line-height: 1.1;
            }

            .field-group {
                display: flex;
                flex-direction: column;
                gap: 18px;
            }

            .signup-group {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .split-fields {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }

            .field {
                position: relative;
            }

            .simple-field {
                position: relative;
                display: block;
            }

            .field input,
            .simple-field input {
                width: 100%;
                height: 48px;
                border: 1.5px solid var(--line);
                border-radius: var(--radius-lg);
                background: #fff;
                padding: 0 16px 0 44px;
                font-size: var(--text-md);
                color: var(--ink);
                outline: none;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .field.password-field input,
            .simple-field.password-field input {
                padding-right: 44px;
            }

            .field input.input-invalid,
            .simple-field input.input-invalid {
                border-color: #c63a3a;
                box-shadow: 0 0 0 3px rgba(198, 58, 58, 0.14);
                background: #fff7f7;
            }

            .simple-field input {
                padding: 0 12px;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-sm);
            }

            .field input::placeholder,
            .simple-field input::placeholder {
                color: #4e4e4e;
            }

            .field input:focus,
            .simple-field input:focus {
                border-color: #000;
                box-shadow: 0 0 0 3px rgba(17, 17, 17, 0.08);
            }

            .field svg {
                position: absolute;
                left: 14px;
                top: 50%;
                transform: translateY(-50%);
                width: 18px;
                height: 18px;
                color: #111;
            }

            .password-toggle {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                width: 28px;
                height: 28px;
                border: 0;
                background: transparent;
                color: #444;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                padding: 0;
                z-index: 2;
            }

            .password-toggle svg {
                position: static;
                transform: none;
                width: 18px;
                height: 18px;
            }

            .primary-btn,
            .secondary-btn {
                width: 100%;
                height: 40px;
                border-radius: var(--radius-pill);
                font-size: var(--text-sm);
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            }

            .primary-btn:hover,
            .secondary-btn:hover {
                transform: translateY(-1px);
            }

            .primary-btn {
                border: none;
                background: #000;
                color: #fff;
                box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
            }

            .secondary-btn {
                border: 1.5px solid var(--line);
                background: #fff;
                color: #111;
            }

            .helper-links {
                text-align: center;
                margin: 8px 0 12px;
                font-size: var(--text-sm);
            }

            .helper-links a {
                text-decoration: underline;
                text-underline-offset: 3px;
            }

            .divider {
                text-align: center;
                color: #222;
                margin: 8px 0 12px;
                font-size: var(--text-sm);
            }

            .error-message {
                margin: 0 0 14px;
                padding: 10px 12px;
                border-radius: 10px;
                border: 1px solid #d58a8a;
                background: #ffe6e6;
                color: #7f1414;
                font-size: var(--text-sm);
            }

            .success-message {
                margin: 0 0 14px;
                padding: 10px 12px;
                border-radius: 10px;
                border: 1px solid #8fc59b;
                background: #e9f8ed;
                color: #1d6a32;
                font-size: var(--text-sm);
            }

            .signup-note {
                margin: 6px 0 0;
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-xs);
                line-height: 1.25;
            }

            .field-error {
                margin: 2px 2px 0;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-xs);
                color: #a12b2b;
            }

            .signup-footer {
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-sm);
                margin-top: 8px;
            }

            .signup-footer a {
                text-decoration: underline;
                text-underline-offset: 2px;
            }

            .forgot-modal {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.48);
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
                z-index: 40;
            }

            .forgot-modal.show {
                display: flex;
            }

            .forgot-card {
                width: min(100%, 380px);
                background: #fff;
                border: 1.5px solid var(--line);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow);
                padding: 24px 20px;
            }

            .forgot-card h2 {
                margin: 0 0 8px;
                text-align: center;
                font-size: var(--text-2xl);
            }

            .forgot-card p {
                margin: 0 0 16px;
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-size: var(--text-sm);
                color: var(--muted);
            }

            .action-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .brand-side {
                background: #050505;
                color: #fff;
                padding: 42px 34px;
            }

            .brand-panel {
                text-align: center;
                max-width: 360px;
            }

            .brand-panel--signup h2 {
                font-size: var(--text-3xl);
                line-height: 1.1;
            }

            .brand-panel--signup p {
                max-width: 320px;
                font-size: var(--text-lg);
                line-height: 1.08;
            }

            .screen {
                display: grid;
                grid-template-columns: minmax(320px, 1fr) minmax(320px, 0.96fr);
                width: 50%;
                min-width: 50%;
            }

            .brand-mark {
                width: 140px;
                height: auto;
                margin: 0 auto 20px;
            }

            .brand-panel h2 {
                margin: 0 0 18px;
                font-size: var(--text-3xl);
                font-weight: 700;
                line-height: 1;
            }

            .brand-panel p {
                margin: 0 auto;
                max-width: 300px;
                font-size: var(--text-lg);
                line-height: 1.15;
                font-weight: 700;
            }

            @media (max-width: 860px) {
                .auth-shell {
                    min-height: 960px;
                }

                .screen {
                    grid-template-columns: 1fr;
                }

                .brand-side {
                    order: -1;
                    min-height: 320px;
                    border-bottom-left-radius: 0;
                    border-bottom-right-radius: 0;
                }

                .form-side {
                    padding-top: 28px;
                    padding-bottom: 36px;
                }
            }

            @media (max-width: 480px) {
                .page {
                    padding: 18px 12px;
                }

                .form-side,
                .brand-side {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .login-card {
                    padding: 28px 18px 24px;
                }

                .login-card h1 {
                    font-size: 1.45rem;
                    margin-bottom: 26px;
                }

                .signup-card h1 {
                    font-size: 1.45rem;
                }

                .split-fields {
                    grid-template-columns: 1fr;
                    gap: 10px;
                }

                .action-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body data-forgot-open="{{ $errors->forgot->any() ? 'true' : 'false' }}">
        <main class="page">
            <section class="auth-shell" data-screen="{{ session('auth_screen', $errors->signup->any() ? 'signup' : 'login') }}">
                <div class="screens-track">
                <div class="screen screen-login">
                    <div class="form-side">
                        <div class="login-card">
                            <h1>Login into WeDumbell</h1>

                            @if ($errors->has('identity'))
                                <div class="error-message">{{ $errors->first('identity') }}</div>
                            @endif

                            @if (session('signup_success'))
                                <div class="success-message">{{ session('signup_success') }}</div>
                            @endif

                            @if (session('forgot_success'))
                                <div class="success-message">{{ session('forgot_success') }}</div>
                            @endif

                            <form class="field-group" method="POST" action="{{ url('/login') }}">
                                @csrf

                                <label class="field" for="identity">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" fill="currentColor"/>
                                        <path d="M4 19.5A5.5 5.5 0 0 1 9.5 14h5A5.5 5.5 0 0 1 20 19.5V21H4v-1.5Z" fill="currentColor"/>
                                    </svg>
                                    <input
                                        id="identity"
                                        name="identity"
                                        type="text"
                                        class="{{ $errors->has('identity') ? 'input-invalid' : '' }}"
                                        value="{{ old('identity') }}"
                                        placeholder="Email or mobile number"
                                    >
                                </label>

                                <label class="field password-field" for="password">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7.5 10V7a4.5 4.5 0 1 1 9 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="15" r="1.4" fill="currentColor"/>
                                    </svg>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        class="{{ $errors->has('identity') ? 'input-invalid' : '' }}"
                                        placeholder="Password"
                                    >
                                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                    </button>
                                </label>

                                <button class="primary-btn" type="submit">Login</button>
                            </form>

                            <div class="helper-links">
                                <a href="#" id="forgotPasswordLink">Forgot password?</a>
                            </div>

                            <div class="divider">or</div>

                            <button class="secondary-btn" type="button" data-switch="signup">Create account</button>
                        </div>
                    </div>

                    <div class="brand-side">
                        <div class="brand-panel">
                            <img class="brand-mark" src="{{ asset('images/logo.png') }}" alt="GymSystem logo">

                            <h2>WeDumbell</h2>
                            <p>
                                Welcome to WeDumbell,
                                where your fitness journey
                                begins. Stay consistent
                                and get stronger every day
                            </p>
                        </div>
                    </div>
                </div>

                <div class="screen screen-signup">
                    <div class="brand-side">
                        <div class="brand-panel brand-panel--signup">
                            <img class="brand-mark" src="{{ asset('images/logo.png') }}" alt="GymSystem logo">

                            <h2>Join us in WeDumbell</h2>
                            <p>
                                Create your account and
                                unlock access to classes,
                                member perks, and your
                                personal fitness
                                dashboard.
                            </p>
                        </div>
                    </div>

                    <div class="form-side">
                        <div class="signup-card">
                            <h1>Create account</h1>
                            <p class="signup-subtitle">Join WeDumbell and start your fitness journey today.</p>

                            @if ($errors->signup->any())
                                <div class="error-message">{{ $errors->signup->first() }}</div>
                            @endif

                            <form class="signup-group" method="POST" action="{{ url('/register') }}">
                                @csrf

                                <div class="split-fields">
                                    <label class="simple-field" for="first_name">
                                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" placeholder="First Name">
                                    </label>
                                    <label class="simple-field" for="last_name">
                                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" placeholder="Last Name">
                                    </label>
                                </div>

                                <label class="simple-field" for="signup_email">
                                    <input id="signup_email" name="email" type="email" value="{{ old('email') }}" placeholder="Email Address">
                                </label>

                                <label class="simple-field" for="signup_mobile">
                                    <input id="signup_mobile" name="mobile" type="text" value="{{ old('mobile') }}" placeholder="Mobile Number">
                                </label>

                                <label class="simple-field password-field" for="create_password">
                                    <input id="create_password" name="create_password" type="password" class="{{ $errors->signup->has('create_password') || $errors->signup->has('confirm_password') ? 'input-invalid' : '' }}" placeholder="Create Password">
                                    <button type="button" class="password-toggle" data-password-toggle="create_password" aria-label="Show password">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                    </button>
                                </label>

                                <label class="simple-field password-field" for="confirm_password">
                                    <input id="confirm_password" name="confirm_password" type="password" class="{{ $errors->signup->has('create_password') || $errors->signup->has('confirm_password') ? 'input-invalid' : '' }}" placeholder="Confirm Password">
                                    <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                    </button>
                                </label>

                                <div class="field-error" id="signupPasswordError" @if (!($errors->signup->has('create_password') || $errors->signup->has('confirm_password'))) style="display: none;" @endif>
                                    {{ $errors->signup->first('create_password') ?: $errors->signup->first('confirm_password') }}
                                </div>

                                <p class="signup-note">By signing up you agree to our Terms and Privacy Policy.</p>

                                <button class="primary-btn" type="submit">Create account</button>
                            </form>

                            <div class="divider">Or</div>

                            <div class="signup-footer">
                                Already have an account?
                                <a href="#" data-switch="login">Login here</a>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </section>
        </main>

        <div class="forgot-modal @if ($errors->forgot->any()) show @endif" id="forgotPasswordModal">
            <div class="forgot-card">
                <h2>Reset password</h2>
                <p>Enter your email and choose a new password for your account.</p>

                @if ($errors->forgot->any())
                    <div class="error-message">{{ $errors->forgot->first() }}</div>
                @endif

                <form class="signup-group" method="POST" action="{{ url('/forgot-password') }}">
                    @csrf
                    <label class="simple-field" for="forgot_email">
                        <input id="forgot_email" name="email" type="email" value="{{ old('email') }}" placeholder="Email Address">
                    </label>

                    <label class="simple-field password-field" for="new_password">
                        <input id="new_password" name="new_password" type="password" placeholder="New Password">
                        <button type="button" class="password-toggle" data-password-toggle="new_password" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                        </button>
                    </label>

                    <label class="simple-field password-field" for="new_password_confirmation">
                        <input id="new_password_confirmation" name="new_password_confirmation" type="password" placeholder="Confirm New Password">
                        <button type="button" class="password-toggle" data-password-toggle="new_password_confirmation" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                        </button>
                    </label>

                    <div class="field-error" id="forgotPasswordError" @if (!($errors->forgot->has('new_password') || $errors->forgot->has('new_password_confirmation'))) style="display: none;" @endif>
                        {{ $errors->forgot->first('new_password') ?: $errors->forgot->first('new_password_confirmation') }}
                    </div>

                    <div class="action-row">
                        <button class="primary-btn" type="submit">Update password</button>
                        <button class="secondary-btn" type="button" id="forgotModalClose">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            const authShell = document.querySelector('.auth-shell');
            const signupPassword = document.getElementById('create_password');
            const signupPasswordConfirm = document.getElementById('confirm_password');
            const signupPasswordError = document.getElementById('signupPasswordError');
            const forgotModal = document.getElementById('forgotPasswordModal');
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            const forgotClose = document.getElementById('forgotModalClose');
            const forgotPassword = document.getElementById('new_password');
            const forgotPasswordConfirm = document.getElementById('new_password_confirmation');
            const forgotPasswordError = document.getElementById('forgotPasswordError');
            const shouldOpenForgotModal = document.body.dataset.forgotOpen === 'true';

            const switchScreen = (nextScreen) => {
                if (!authShell || !['login', 'signup'].includes(nextScreen)) {
                    return;
                }
                authShell.dataset.screen = nextScreen;
            };

            const setPasswordErrorState = (message = '') => {
                if (!signupPassword || !signupPasswordConfirm || !signupPasswordError) {
                    return;
                }

                const hasError = message !== '';
                signupPassword.classList.toggle('input-invalid', hasError);
                signupPasswordConfirm.classList.toggle('input-invalid', hasError);
                signupPasswordError.style.display = hasError ? 'block' : 'none';
                signupPasswordError.textContent = message;
            };

            const validateSignupPasswords = () => {
                if (!signupPassword || !signupPasswordConfirm) {
                    return true;
                }

                const password = signupPassword.value;
                const confirmPassword = signupPasswordConfirm.value;

                if (!password && !confirmPassword) {
                    setPasswordErrorState('');
                    return true;
                }

                if (password.length > 0 && password.length < 8) {
                    setPasswordErrorState('Password must be at least 8 characters long.');
                    return false;
                }

                if (confirmPassword && password !== confirmPassword) {
                    setPasswordErrorState('Passwords do not match.');
                    return false;
                }

                setPasswordErrorState('');
                return true;
            };

            const setForgotPasswordErrorState = (message = '') => {
                if (!forgotPassword || !forgotPasswordConfirm || !forgotPasswordError) {
                    return;
                }

                const hasError = message !== '';
                forgotPassword.classList.toggle('input-invalid', hasError);
                forgotPasswordConfirm.classList.toggle('input-invalid', hasError);
                forgotPasswordError.style.display = hasError ? 'block' : 'none';
                forgotPasswordError.textContent = message;
            };

            const validateForgotPasswords = () => {
                if (!forgotPassword || !forgotPasswordConfirm) {
                    return true;
                }

                const password = forgotPassword.value;
                const confirmPassword = forgotPasswordConfirm.value;

                if (!password && !confirmPassword) {
                    setForgotPasswordErrorState('');
                    return true;
                }

                if (password.length > 0 && password.length < 8) {
                    setForgotPasswordErrorState('Password must be at least 8 characters long.');
                    return false;
                }

                if (confirmPassword && password !== confirmPassword) {
                    setForgotPasswordErrorState('Passwords do not match.');
                    return false;
                }

                setForgotPasswordErrorState('');
                return true;
            };

            document.querySelectorAll('[data-switch]').forEach((control) => {
                control.addEventListener('click', (event) => {
                    event.preventDefault();
                    switchScreen(control.dataset.switch);
                });
            });

            document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const input = document.getElementById(toggle.dataset.passwordToggle);
                    if (!input) return;
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            });

            signupPassword?.addEventListener('input', validateSignupPasswords);
            signupPasswordConfirm?.addEventListener('input', validateSignupPasswords);
            signupPassword?.form?.addEventListener('submit', (event) => {
                if (!validateSignupPasswords()) {
                    event.preventDefault();
                }
            });

            forgotPassword?.addEventListener('input', validateForgotPasswords);
            forgotPasswordConfirm?.addEventListener('input', validateForgotPasswords);
            forgotPassword?.form?.addEventListener('submit', (event) => {
                if (!validateForgotPasswords()) {
                    event.preventDefault();
                }
            });

            forgotPasswordLink?.addEventListener('click', (event) => {
                event.preventDefault();
                forgotModal?.classList.add('show');
            });

            forgotClose?.addEventListener('click', () => {
                forgotModal?.classList.remove('show');
            });

            forgotModal?.addEventListener('click', (event) => {
                if (event.target === forgotModal) {
                    forgotModal.classList.remove('show');
                }
            });

            if (shouldOpenForgotModal) {
                forgotModal?.classList.add('show');
            }
        </script>
    </body>
</html>
