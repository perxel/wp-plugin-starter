<?php
/**
 * Settings screen.
 *
 * @package Perxel_PluginName
 *
 * @var array $settings  Settings::all().
 * @var bool  $updated   Whether the form just saved.
 * @var bool  $was_reset Whether settings were just reset.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Perxel_UI escapes structure; dynamic values escaped inline.

if ( $updated ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings saved.', 'perxel-plugin-name' ), array( 'dismissible' => true ) );
} elseif ( $was_reset ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings reset to defaults.', 'perxel-plugin-name' ), array( 'dismissible' => true ) );
}

$reset_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'pxprefix_reset_settings' ), admin_url( 'admin-post.php' ) ),
	'pxprefix_reset_settings'
);
?>
<form id="pxprefix-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxprefix_save_settings" />
	<?php wp_nonce_field( 'pxprefix_save_settings' ); ?>

	<?php
	echo \Perxel_UI::rows(
		array(
			array(
				'title' => __( 'General', 'perxel-plugin-name' ),
				'rows'  => array(
					array(
						'label'   => __( 'Enabled', 'perxel-plugin-name' ),
						'sub'     => esc_html__( 'Turn the plugin on or off without deactivating it.', 'perxel-plugin-name' ),
						'content' => \Perxel_UI::toggle(
							array(
								'name'    => 'enabled',
								'checked' => (bool) $settings['enabled'],
								'label'   => __( 'Enabled', 'perxel-plugin-name' ),
							)
						),
					),
					array(
						'label'   => __( 'API key', 'perxel-plugin-name' ),
						'sub'     => esc_html__( 'Example text field. Replace with the settings your plugin needs.', 'perxel-plugin-name' ),
						'content' => '<input type="password" name="api_key" autocomplete="off" value="' . esc_attr( $settings['api_key'] ) . '" />',
					),
				),
			),
		)
	);
	?>
</form>

<?php
echo \Perxel_UI::rows(
	array(
		array(
			'title'  => __( 'Danger zone', 'perxel-plugin-name' ),
			'danger' => true,
			'rows'   => array(
				array(
					'label'   => __( 'Reset settings', 'perxel-plugin-name' ),
					'sub'     => esc_html__( 'Restore every setting on this screen to its default.', 'perxel-plugin-name' ),
					'content' => '<a class="button" href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Reset', 'perxel-plugin-name' ) . '</a>',
				),
			),
		),
	)
);

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
