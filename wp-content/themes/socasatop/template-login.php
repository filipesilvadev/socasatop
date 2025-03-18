<?php
/**
 * Template Name: Login
 */

if (is_user_logged_in()) {
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
    wp_safe_redirect($redirect_to);
    exit;
}

get_header();

$login_error = isset($_GET['login']) ? $_GET['login'] : '';
$registration_success = isset($_GET['registration']) ? $_GET['registration'] : '';
$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <?php if ($login_error === 'failed'): ?>
                        <div class="alert alert-danger">Erro no login. Por favor, verifique suas credenciais.</div>
                    <?php endif; ?>
                    
                    <?php if ($registration_success === 'true'): ?>
                        <div class="alert alert-success">Cadastro realizado com sucesso! Por favor, faça login.</div>
                    <?php endif; ?>

                    <?php
                    wp_login_form(array(
                        'redirect' => $redirect_to,
                        'label_username' => 'E-mail',
                        'label_password' => 'Senha',
                        'label_remember' => 'Lembrar-me',
                        'label_log_in' => 'Entrar',
                        'remember' => true,
                    ));
                    ?>

                    <div class="text-center mt-3">
                        <a href="<?php echo wp_registration_url(); ?>">Criar uma conta</a>
                        <span class="mx-2">|</span>
                        <a href="<?php echo wp_lostpassword_url(); ?>">Esqueceu a senha?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#loginform {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

#loginform p { margin: 0; }

#loginform label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

#loginform input[type="text"],
#loginform input[type="password"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: border-color 0.2s;
}

#loginform input:focus {
    border-color: #0d6efd;
    outline: none;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
}

#loginform .login-remember {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#wp-submit {
    width: 100%;
    padding: 0.75rem;
    background-color: #0d6efd;
    border: none;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    cursor: pointer;
}

#wp-submit:hover {
    background-color: #0b5ed7;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
}

.alert-success {
    background-color: #d1e7dd;
    border: 1px solid #badbcc;
    color: #0f5132;
}
</style>

<?php get_footer(); ?>
