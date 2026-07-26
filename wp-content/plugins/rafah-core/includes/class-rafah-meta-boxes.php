<?php
/**
 * Rafah Core — Tabbed meta boxes with repeaters, media, gallery and file fields.
 * No ACF Pro required.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Map post types to their field config callbacks.
	 */
	private static function configs() {
		return array(
			'project'     => array( 'title' => __( 'Project Details', 'rafah' ), 'fields' => rafah_project_fields() ),
			'agent'       => array( 'title' => __( 'Agent Details', 'rafah' ), 'fields' => rafah_agent_fields() ),
			'testimonial' => array( 'title' => __( 'Testimonial Details', 'rafah' ), 'fields' => rafah_testimonial_fields() ),
		);
	}

	public static function register() {
		foreach ( self::configs() as $post_type => $config ) {
			add_meta_box(
				'rafah_details_' . $post_type,
				$config['title'],
				array( __CLASS__, 'render' ),
				$post_type,
				'normal',
				'high',
				array( 'tabs' => $config['fields'] )
			);
		}
	}

	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! array_key_exists( $screen->post_type, self::configs() ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'rafah-admin', RAFAH_CORE_URL . 'assets/css/admin.css', array(), RAFAH_CORE_VERSION );
		wp_enqueue_script( 'rafah-admin', RAFAH_CORE_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), RAFAH_CORE_VERSION, true );
		wp_localize_script(
			'rafah-admin',
			'rafahAdmin',
			array(
				'chooseImage'  => __( 'Choose Image', 'rafah' ),
				'chooseImages' => __( 'Choose Images', 'rafah' ),
				'chooseFile'   => __( 'Choose File', 'rafah' ),
				'remove'       => __( 'Remove', 'rafah' ),
				'confirmRow'   => __( 'Remove this row?', 'rafah' ),
			)
		);
	}

	/**
	 * Render the tabbed meta box.
	 */
	public static function render( $post, $box ) {
		$tabs = $box['args']['tabs'];
		wp_nonce_field( 'rafah_save_meta', 'rafah_meta_nonce' );
		?>
		<div class="rafah-metabox">
			<nav class="rafah-tabs" role="tablist">
				<?php $first = true; foreach ( $tabs as $tab_id => $tab ) : ?>
					<button type="button" class="rafah-tab<?php echo $first ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>">
						<span class="dashicons <?php echo esc_attr( $tab['icon'] ?? 'dashicons-admin-generic' ); ?>"></span>
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				<?php $first = false; endforeach; ?>
			</nav>
			<?php $first = true; foreach ( $tabs as $tab_id => $tab ) : ?>
				<div class="rafah-panel<?php echo $first ? ' is-active' : ''; ?>" data-panel="<?php echo esc_attr( $tab_id ); ?>">
					<?php foreach ( $tab['fields'] as $field ) {
						self::render_field( $field, $post->ID );
					} ?>
				</div>
			<?php $first = false; endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render one field row.
	 */
	private static function render_field( $field, $post_id ) {
		$key   = $field['key'];
		$name  = 'rafah[' . $key . ']';
		$value = get_post_meta( $post_id, '_rafah_' . $key, true );

		if ( '' === $value && isset( $field['default'] ) ) {
			$value = $field['default'];
		}
		?>
		<div class="rafah-field rafah-field--<?php echo esc_attr( $field['type'] ); ?>">
			<label class="rafah-field__label" for="rafah-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			<div class="rafah-field__control">
				<?php self::render_control( $field, $name, $value, 'rafah-' . $key ); ?>
				<?php if ( ! empty( $field['desc'] ) ) : ?>
					<p class="rafah-field__desc"><?php echo esc_html( $field['desc'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the input control for a field.
	 */
	private static function render_control( $field, $name, $value, $id = '' ) {
		$id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea%s name="%s" rows="4" class="widefat">%s</textarea>', $id_attr, esc_attr( $name ), esc_textarea( $value ) );
				break;

			case 'number':
				printf(
					'<input%s type="number" name="%s" value="%s" step="any"%s%s class="rafah-input rafah-input--number">',
					$id_attr,
					esc_attr( $name ),
					esc_attr( $value ),
					isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '',
					isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : ''
				);
				break;

			case 'select':
				printf( '<select%s name="%s" class="rafah-input">', $id_attr, esc_attr( $name ) );
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $opt_value ), selected( $value, $opt_value, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<label class="rafah-switch"><input%s type="checkbox" name="%s" value="1"%s><span class="rafah-switch__track"></span></label>',
					$id_attr,
					esc_attr( $name ),
					checked( $value, '1', false )
				);
				break;

			case 'post_select':
				$posts = get_posts(
					array(
						'post_type'      => $field['post_type'],
						'posts_per_page' => 200,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);
				printf( '<select%s name="%s" class="rafah-input">', $id_attr, esc_attr( $name ) );
				printf( '<option value="">%s</option>', esc_html__( '— None —', 'rafah' ) );
				foreach ( $posts as $p ) {
					printf( '<option value="%d"%s>%s</option>', $p->ID, selected( (int) $value, $p->ID, false ), esc_html( $p->post_title ) );
				}
				echo '</select>';
				break;

			case 'media':
				$img = $value ? wp_get_attachment_image( (int) $value, 'thumbnail' ) : '';
				printf(
					'<div class="rafah-media" data-type="image"><input type="hidden" name="%s" value="%s"><div class="rafah-media__preview">%s</div><button type="button" class="button rafah-media__pick">%s</button> <button type="button" class="button-link-delete rafah-media__clear%s">%s</button></div>',
					esc_attr( $name ),
					esc_attr( $value ),
					$img, // phpcs:ignore WordPress.Security.EscapeOutput
					esc_html__( 'Choose Image', 'rafah' ),
					$value ? '' : ' hidden',
					esc_html__( 'Remove', 'rafah' )
				);
				break;

			case 'file':
				$filename = $value ? basename( (string) get_attached_file( (int) $value ) ) : '';
				printf(
					'<div class="rafah-media" data-type="file"><input type="hidden" name="%s" value="%s"><span class="rafah-media__filename">%s</span> <button type="button" class="button rafah-media__pick">%s</button> <button type="button" class="button-link-delete rafah-media__clear%s">%s</button></div>',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_html( $filename ),
					esc_html__( 'Choose File', 'rafah' ),
					$value ? '' : ' hidden',
					esc_html__( 'Remove', 'rafah' )
				);
				break;

			case 'gallery':
				$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
				echo '<div class="rafah-gallery">';
				printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( implode( ',', $ids ) ) );
				echo '<ul class="rafah-gallery__list">';
				foreach ( $ids as $img_id ) {
					printf(
						'<li class="rafah-gallery__item" data-id="%d">%s<button type="button" class="rafah-gallery__remove" aria-label="%s">&times;</button></li>',
						$img_id,
						wp_get_attachment_image( $img_id, 'thumbnail' ), // phpcs:ignore WordPress.Security.EscapeOutput
						esc_attr__( 'Remove', 'rafah' )
					);
				}
				echo '</ul>';
				printf( '<button type="button" class="button rafah-gallery__add">%s</button>', esc_html__( 'Add Images', 'rafah' ) );
				echo '</div>';
				break;

			case 'repeater':
				self::render_repeater( $field, $name, $value );
				break;

			default: // text, url, tel, email, date.
				$type = in_array( $field['type'], array( 'url', 'tel', 'email', 'date' ), true ) ? $field['type'] : 'text';
				printf( '<input%s type="%s" name="%s" value="%s" class="widefat rafah-input">', $id_attr, esc_attr( $type ), esc_attr( $name ), esc_attr( $value ) );
		}
	}

	/**
	 * Render a repeater field.
	 */
	private static function render_repeater( $field, $name, $value ) {
		$rows = is_array( $value ) ? $value : array();
		?>
		<div class="rafah-repeater" data-key="<?php echo esc_attr( $field['key'] ); ?>">
			<div class="rafah-repeater__rows">
				<?php foreach ( $rows as $i => $row ) : ?>
					<?php self::render_repeater_row( $field, $name, (int) $i, $row ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-secondary rafah-repeater__add">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php echo esc_html( $field['button'] ?? __( 'Add Row', 'rafah' ) ); ?>
			</button>
			<script type="text/html" class="rafah-repeater__template">
				<?php self::render_repeater_row( $field, $name, '__i__', array() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render one repeater row.
	 */
	private static function render_repeater_row( $field, $name, $index, $row ) {
		?>
		<div class="rafah-repeater__row">
			<div class="rafah-repeater__handle" title="<?php esc_attr_e( 'Drag to reorder', 'rafah' ); ?>"><span class="dashicons dashicons-menu"></span></div>
			<div class="rafah-repeater__fields">
				<?php foreach ( $field['sub_fields'] as $sub ) : ?>
					<div class="rafah-subfield rafah-subfield--<?php echo esc_attr( $sub['type'] ); ?>">
						<label class="rafah-subfield__label"><?php echo esc_html( $sub['label'] ); ?></label>
						<?php
						$sub_name  = $name . '[' . $index . '][' . $sub['key'] . ']';
						$sub_value = $row[ $sub['key'] ] ?? '';
						self::render_control( $sub, $sub_name, $sub_value );
						?>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="rafah-repeater__remove" aria-label="<?php esc_attr_e( 'Remove row', 'rafah' ); ?>"><span class="dashicons dashicons-trash"></span></button>
		</div>
		<?php
	}

	/**
	 * Save handler.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['rafah_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rafah_meta_nonce'] ), 'rafah_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$configs = self::configs();
		if ( ! isset( $configs[ $post->post_type ] ) ) {
			return;
		}

		$input = isset( $_POST['rafah'] ) ? wp_unslash( $_POST['rafah'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per-field below.

		foreach ( $configs[ $post->post_type ]['fields'] as $tab ) {
			foreach ( $tab['fields'] as $field ) {
				$key = $field['key'];

				if ( 'checkbox' === $field['type'] ) {
					// Default-ON checkboxes opt into persisting an explicit '0' when
					// unchecked (via 'store_unchecked'), so "off" is distinguishable
					// from "never saved". Plain checkboxes are unchanged: they store
					// '1' when checked and delete the meta when unchecked.
					if ( ! empty( $input[ $key ] ) ) {
						$clean = '1';
					} else {
						$clean = ! empty( $field['store_unchecked'] ) ? '0' : '';
					}
				} elseif ( 'repeater' === $field['type'] ) {
					$clean = self::sanitize_repeater( $field, $input[ $key ] ?? array() );
				} else {
					$clean = self::sanitize_value( $field, $input[ $key ] ?? '' );
				}

				if ( '' === $clean || ( is_array( $clean ) && empty( $clean ) ) ) {
					delete_post_meta( $post_id, '_rafah_' . $key );
				} else {
					update_post_meta( $post_id, '_rafah_' . $key, $clean );
				}
			}
		}
	}

	/**
	 * Sanitize a scalar field value by type.
	 */
	private static function sanitize_value( $field, $raw ) {
		switch ( $field['type'] ) {
			case 'number':
				return ( '' === $raw ) ? '' : (string) floatval( $raw );
			case 'url':
				return esc_url_raw( $raw );
			case 'email':
				return sanitize_email( $raw );
			case 'textarea':
				return sanitize_textarea_field( $raw );
			case 'select':
				return array_key_exists( $raw, $field['options'] ?? array() ) ? $raw : '';
			case 'post_select':
			case 'media':
			case 'file':
				return $raw ? (string) absint( $raw ) : '';
			case 'gallery':
				$ids = array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
				return implode( ',', $ids );
			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Sanitize repeater rows.
	 */
	private static function sanitize_repeater( $field, $raw_rows ) {
		if ( ! is_array( $raw_rows ) ) {
			return array();
		}

		$clean_rows = array();

		foreach ( $raw_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean_row = array();
			$has_value = false;

			foreach ( $field['sub_fields'] as $sub ) {
				$clean_row[ $sub['key'] ] = self::sanitize_value( $sub, $row[ $sub['key'] ] ?? '' );
				if ( '' !== $clean_row[ $sub['key'] ] ) {
					$has_value = true;
				}
			}

			if ( $has_value ) {
				$clean_rows[] = $clean_row;
			}
		}

		return array_values( $clean_rows );
	}
}
