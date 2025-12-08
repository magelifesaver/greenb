<p><?= esc_html( sprintf( __( 'Hi, %s', 'mb-user-profile' ), $data->user->display_name ) ) ?></p>

<p><?= esc_html( sprintf( __( 'Someone has requested a new password for your account on %s site.', 'mb-user-profile' ), get_bloginfo( 'name' ) ) ) ?></p>

<p><a href="<?= esc_url( $data->url ) ?>"><?php esc_html_e( 'Click here to reset your password', 'mb-user-profile' ) ?></a></p>
