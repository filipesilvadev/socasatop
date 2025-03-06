<form id="edit-broker" method="post" class="form">
    <?php if (isset($_GET['id'])) :
        $id = $_GET['id'];
        $user = get_user_by('ID', $id);
    ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-wrapper">
            <label for="name">Nome:</label>
            <input type="text" name="name" id="name" required value="<?php echo $user->display_name; ?>">
        </div>
        <div class="form-wrapper">
            <label for="phone">Telefone:</label>
            <input type="tel" name="phone" id="phone" required value="<?php echo get_user_meta($id, 'phone', true); ?>">
        </div>
        <div class="form-wrapper">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo $user->user_email; ?>">
        </div>
        <div class="form-wrapper">
            <label for="password" class="mb-3 block">Senha:</label>
            <input type="text" name="password" id="password">
        </div>
        <button type="submit" class="btn btn-info">
            Editar Corretor
        </button>
    <?php else : ?>
        <p>Corretor não encontrado.</p>
    <?php endif; ?>
</form>