<div class="mto-dashboard">
    <form class="mto-credentials js-mto-credentials">
        <label for="login"><?php _e('Maatoo login', 'mto'); ?>
            <input type="text" id="login" name="login"/>
        </label>

        <label for="password"><?php _e('Maatoo password', 'mto'); ?>
            <input type="password" name="password" id="password">
        </label>

        <label for="login"><?php _e('Access Token', 'mto'); ?>
            <input type="text" id="token" name="token"/>
        </label>

        <input type="submit" name="connect" value="<?php
        _e('Connect', 'mto'); ?>">
    </form>
</div>
<?php
