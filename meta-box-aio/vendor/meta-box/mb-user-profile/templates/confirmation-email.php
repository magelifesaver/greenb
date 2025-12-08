<p><?php esc_html_e( 'Welcome', 'mb-user-profile' ) ?> <?= esc_html( $data->username ) ?></p>
<p><?php esc_html_e( 'Please click the link below to confirm your account:', 'mb-user-profile' ) ?></p>
<p><a href="<?= esc_url( $data->confirm_link ) ?>" target="_blank"><?php esc_html_e( 'Confirm Account', 'mb-user-profile' ) ?></a></p>
<p><?php esc_html_e( 'If that doesn\'t work, copy and paste the following link in your browser:', 'mb-user-profile' ) ?></p>
<p><?= esc_url( $data->confirm_link ) ?></p>
