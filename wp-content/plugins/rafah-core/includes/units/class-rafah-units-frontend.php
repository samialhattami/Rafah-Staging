<?php
/**
 * Rafah Core — Frontend units table + statistics.
 *
 * Renders the customer-facing comparison table: sticky grouped headers,
 * live search / status filter / price & area sorting (client-side — even
 * 300 rows are trivial for the DOM), status badges, and a pure-CSS
 * transformation into cards on mobile. Fully RTL/LTR aware.
 *
 * The theme only calls rafah_units_table() / rafah_units_stats_strip() —
 * all logic stays in the plugin.
 *
 * @package Rafah_Core
 */

defined( 'ABSPATH' ) || exit;

class Rafah_Units_Frontend {

	public static function init() {}

	/**
	 * Resolve translated projects to the default-language project so both
	 * languages share one set of units.
	 */
	public static function resolve_project( $project_id ) {
		if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
			$default = pll_get_post( $project_id, pll_default_language() );

			if ( $default ) {
				return (int) $default;
			}
		}

		return (int) $project_id;
	}

	/**
	 * Availability stats strip (counts + percentage bar).
	 */
	public static function stats_strip( $project_id ) {
		$project_id = self::resolve_project( $project_id );
		$stats      = Rafah_Units_DB::stats( $project_id );

		if ( ! $stats['visible'] ) {
			return;
		}

		$statuses = Rafah_Units_DB::statuses();
		?>
		<div class="rafah-ustats">
			<div class="rafah-ustats__bar" role="img" aria-label="<?php echo esc_attr( rafah_text( 'units' ) ); ?>">
				<?php foreach ( array( 'available', 'reserved', 'sold' ) as $key ) : ?>
					<?php if ( $stats[ $key . '_pct' ] > 0 ) : ?>
						<span style="width:<?php echo esc_attr( $stats[ $key . '_pct' ] ); ?>%;background:<?php echo esc_attr( $statuses[ $key ]['color'] ); ?>"></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<div class="rafah-ustats__legend">
				<?php foreach ( array( 'available', 'reserved', 'sold' ) as $key ) : ?>
					<span class="rafah-ustats__item">
						<i style="background:<?php echo esc_attr( $statuses[ $key ]['color'] ); ?>"></i>
						<?php echo esc_html( $statuses[ $key ]['label'] . ' ' . $stats[ $key ] . ' (' . $stats[ $key . '_pct' ] . '%)' ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * The units comparison table — built entirely from the project's
	 * user-defined columns (no fixed fields). Status is the only system
	 * column shown. Number/price columns are client-sortable; search matches
	 * across every value.
	 */
	public static function table( $project_id ) {
		$project_id = self::resolve_project( $project_id );
		$units      = Rafah_Units_DB::visible_units( $project_id );

		if ( ! $units ) {
			return;
		}

		$groups  = Rafah_Units_Columns::groups( $project_id );
		$columns = Rafah_Units_Columns::flat_columns( $project_id );

		if ( ! $columns ) {
			return;
		}

		$statuses = Rafah_Units_DB::statuses();
		$ar       = rafah_is_rtl_lang();

		$labels = array(
			'status' => $ar ? 'الحالة' : 'Status',
			'search' => $ar ? 'ابحث في الوحدات…' : 'Search units…',
			'all'    => $ar ? 'جميع الحالات' : 'All statuses',
			'none'   => $ar ? 'لا توجد وحدات مطابقة.' : 'No matching units.',
		);

		// Group header cells (only groups that have named columns). The trailing
		// Status column is a system column and is not grouped.
		$group_cells = array();
		$has_groups  = false;
		foreach ( $groups as $group ) {
			$named = array_values( array_filter( (array) ( $group['columns'] ?? array() ), function ( $c ) {
				return '' !== trim( (string) ( $c['label'] ?? '' ) );
			} ) );
			if ( ! $named ) {
				continue;
			}
			$label = trim( (string) ( $group['label'] ?? '' ) );
			if ( '' !== $label ) {
				$has_groups = true;
			}
			$group_cells[] = array( 'label' => $label, 'span' => count( $named ) );
		}

		$is_num = function ( $type ) {
			return 'number' === $type || 'price' === $type;
		};
		?>
		<div class="rafah-units-front" data-rafah-units>
			<div class="rafah-units-front__controls">
				<input type="search" data-usearch placeholder="<?php echo esc_attr( $labels['search'] ); ?>" aria-label="<?php echo esc_attr( $labels['search'] ); ?>">
				<select data-ustatus aria-label="<?php echo esc_attr( $labels['status'] ); ?>">
					<option value=""><?php echo esc_html( $labels['all'] ); ?></option>
					<?php foreach ( $statuses as $key => $def ) : ?>
						<?php if ( 'hidden' !== $key ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def['label'] ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="rafah-units-front__scroll">
				<table class="rafah-units-fronttable">
					<thead>
						<?php if ( $has_groups ) : ?>
							<tr class="rafah-units-front__grouprow">
								<?php foreach ( $group_cells as $gc ) : ?>
									<th colspan="<?php echo (int) $gc['span']; ?>" class="<?php echo '' !== $gc['label'] ? 'is-group' : ''; ?>"><?php echo esc_html( $gc['label'] ); ?></th>
								<?php endforeach; ?>
								<th></th>
							</tr>
						<?php endif; ?>
						<tr>
							<?php foreach ( $columns as $column ) : ?>
								<?php $sortable = $is_num( $column['type'] ); ?>
								<th class="<?php echo $sortable ? 'is-sortable' : ''; ?>" <?php echo $sortable ? 'data-usort="s_' . esc_attr( $column['id'] ) . '"' : ''; ?>><?php echo esc_html( $column['label'] ); ?><?php echo $sortable ? ' <span aria-hidden="true">↕</span>' : ''; ?></th>
							<?php endforeach; ?>
							<th><?php echo esc_html( $labels['status'] ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $units as $unit ) :
							$status        = $statuses[ $unit->status ] ?? $statuses['available'];
							$search_parts  = array( (string) $unit->internal_id );
							$sort_attrs    = '';
							foreach ( $columns as $column ) {
								$value = $unit->specs[ $column['id'] ] ?? '';
								if ( 'bool' !== $column['type'] ) {
									$search_parts[] = (string) $value;
								}
								if ( $is_num( $column['type'] ) ) {
									$sort_attrs .= ' data-s_' . esc_attr( $column['id'] ) . '="' . esc_attr( (float) $value ) . '"';
								}
							}
							$search = mb_strtolower( trim( implode( ' ', $search_parts ) ) );
							?>
							<tr data-search="<?php echo esc_attr( $search ); ?>" data-status="<?php echo esc_attr( $unit->status ); ?>"<?php echo $sort_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php foreach ( $columns as $column ) : ?>
									<?php $value = $unit->specs[ $column['id'] ] ?? ''; ?>
									<td data-label="<?php echo esc_attr( $column['label'] ); ?>" class="<?php echo $is_num( $column['type'] ) ? 'is-num' : ''; ?><?php echo 'bool' === $column['type'] ? ' is-dyn' : ''; ?>">
										<?php
										if ( 'bool' === $column['type'] ) {
											echo $value && '0' !== $value ? '<span class="rafah-units-front__yes">✓</span>' : '<span class="rafah-units-front__no">—</span>';
										} elseif ( 'price' === $column['type'] && '' !== $value ) {
											echo '<span class="rafah-units-front__price">' . esc_html( rafah_price( $value, $project_id ) ) . '</span>';
										} elseif ( 'number' === $column['type'] && '' !== $value ) {
											echo esc_html( number_format( (float) $value ) );
										} else {
											echo esc_html( $value );
										}
										?>
									</td>
								<?php endforeach; ?>
								<td data-label="<?php echo esc_attr( $labels['status'] ); ?>">
									<span class="rafah-ubadge" style="--ubadge:<?php echo esc_attr( $status['color'] ); ?>"><?php echo esc_html( $status['label'] ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="rafah-units-front__none" hidden><?php echo esc_html( $labels['none'] ); ?></p>
		</div>
		<?php
	}
}

/**
 * Theme-facing helpers (presentation calls only).
 */
function rafah_units_table( $project_id = null ) {
	Rafah_Units_Frontend::table( $project_id ?: get_the_ID() );
}

function rafah_units_stats_strip( $project_id = null ) {
	Rafah_Units_Frontend::stats_strip( $project_id ?: get_the_ID() );
}

function rafah_units_count( $project_id = null ) {
	return Rafah_Units_DB::stats( Rafah_Units_Frontend::resolve_project( $project_id ?: get_the_ID() ) );
}
