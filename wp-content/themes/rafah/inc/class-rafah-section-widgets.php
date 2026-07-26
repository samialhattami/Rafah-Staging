<?php
/**
 * Rafah — discrete project SECTION widgets for Elementor (FREE-compatible).
 *
 * Phase 2 of the Elementor architecture: every project section becomes its own
 * draggable Elementor widget under the "Rafah" category, instead of the single
 * "Project Section" dropdown widget. Each widget:
 *   • pulls its DATA from Rafah Core (via rafah_theme_render_section) — the theme
 *     markup + CSS is the DEFAULT look and the guaranteed fallback;
 *   • exposes rich NATIVE Elementor Style controls (typography, colour,
 *     background, border, radius, shadow, padding, alignment, responsive, hover)
 *     scoped to that instance with {{WRAPPER}}, targeting the section's internal
 *     elements — so the whole page can be redesigned visually without PHP/CSS.
 *
 * Controls are built with the shared Rafah_Style_Controls helper (Rafah Core).
 * Setting no control = the theme's current design renders unchanged.
 *
 * Registered by the theme through the `rafah_core_widgets` filter (presentation
 * lives in the theme; Rafah Core stays the data layer). Works on Elementor Free.
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Base class — resolves the project, renders a section via Rafah Core, and
 * builds the Style tab from each child's declarative $style_map().
 */
abstract class Rafah_Section_Widget_Base extends \Elementor\Widget_Base {

	/** Section id in the Rafah Core registry (e.g. 'hero', 'facts'). */
	abstract protected function skey();

	/** Declarative list of Rafah_Style_Controls calls: array( array( method, ...args ) ). */
	protected function style_map() {
		return array(
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}

	public function get_name() {
		return 'rafah-project-' . $this->skey();
	}

	public function get_categories() {
		return array( 'rafah' );
	}

	public function get_keywords() {
		return array( 'rafah', 'project', 'section', $this->skey() );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}

	/** Render the section body. Overridable for non-registry widgets (breadcrumbs). */
	protected function render_body( $project_id ) {
		if ( function_exists( 'rafah_theme_render_section' ) ) {
			rafah_theme_render_section( 'project', $this->skey(), $project_id );
		}
	}

	protected function register_controls() {
		$this->start_controls_section( 'rafah_content', array( 'label' => __( 'Content', 'rafah' ) ) );
		$this->add_control( 'project_id', array(
			'label'       => __( 'Project ID', 'rafah' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => '',
			'description' => __( 'Leave blank to use the current project. Data always comes from Rafah Core.', 'rafah' ),
		) );
		$this->end_controls_section();

		// Build the Style tab from the child's map, using the shared helper.
		if ( class_exists( 'Rafah_Style_Controls' ) ) {
			foreach ( $this->style_map() as $entry ) {
				$method = array_shift( $entry );
				if ( is_callable( array( 'Rafah_Style_Controls', $method ) ) ) {
					Rafah_Style_Controls::$method( $this, ...$entry );
				}
			}
		}
	}

	protected function resolve_project_id() {
		$s = $this->get_settings_for_display();
		if ( ! empty( $s['project_id'] ) ) {
			return (int) $s['project_id'];
		}
		if ( ! empty( $GLOBALS['rafah_bridge_project_id'] ) ) {
			return (int) $GLOBALS['rafah_bridge_project_id'];
		}
		return (int) get_the_ID();
	}

	protected function render() {
		$pid = $this->resolve_project_id();

		ob_start();
		$this->render_body( $pid );
		$html = trim( (string) ob_get_clean() );

		if ( '' === $html ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="rafah-widget-hint">' . esc_html( sprintf(
					/* translators: %s: widget title */
					__( '%s — no data for this project yet. It appears automatically once the project has the relevant fields.', 'rafah' ),
					$this->get_title()
				) ) . '</div>';
			}
			return;
		}

		echo '<div class="rafah-project-section rafah-project-' . esc_attr( $this->skey() ) . '">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme section markup.
	}
}

/* ============================ Concrete widgets ============================ */

class Rafah_Widget_Section_Hero extends Rafah_Section_Widget_Base {
	protected function skey() { return 'hero'; }
	public function get_title() { return __( 'Project · Hero', 'rafah' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	protected function style_map() {
		return array(
			array( 'box', 'herobox', __( 'Hero Box', 'rafah' ), '.rafah-project-hero', array( 'min_height' => true, 'align' => true ) ),
			array( 'overlay', '.rafah-project-hero__overlay', 'hoverlay' ),
			array( 'image', '.rafah-project-hero__bg', 'herobg' ),
			array( 'text', 'htitle', __( 'Title', 'rafah' ), '.rafah-project-hero__title', true ),
			array( 'text', 'hsub', __( 'Subtitle', 'rafah' ), '.rafah-project-hero__subtitle', true ),
			array( 'text', 'hloc', __( 'Location', 'rafah' ), '.rafah-project-hero__location', true ),
			array( 'badges' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

class Rafah_Widget_Section_Facts extends Rafah_Section_Widget_Base {
	protected function skey() { return 'facts'; }
	public function get_title() { return __( 'Project · Facts Bar', 'rafah' ); }
	public function get_icon() { return 'eicon-menu-bar'; }
	protected function style_map() {
		return array(
			array( 'box', 'bar', __( 'Facts Bar', 'rafah' ), '.rafah-facts-bar', array( 'align' => true ) ),
			array( 'box', 'fact', __( 'Each Fact', 'rafah' ), '.rafah-fact', array() ),
			array( 'text', 'flabel', __( 'Label', 'rafah' ), '.rafah-fact__label', true ),
			array( 'text', 'fvalue', __( 'Value', 'rafah' ), '.rafah-fact__value', true ),
			array( 'text', 'fgold', __( 'Highlighted Value', 'rafah' ), '.rafah-fact__value--gold', false ),
			array( 'grid', '.rafah-facts-bar', 'facts' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

class Rafah_Widget_Section_Overview extends Rafah_Section_Widget_Base {
	protected function skey() { return 'overview'; }
	public function get_title() { return __( 'Project · Overview', 'rafah' ); }
	public function get_icon() { return 'eicon-text-area'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'text', 'body', __( 'Body Text', 'rafah' ), '.rafah-content', true ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Details extends Rafah_Section_Widget_Base {
	protected function skey() { return 'project-details'; }
	public function get_title() { return __( 'Project · Details', 'rafah' ); }
	public function get_icon() { return 'eicon-table-of-contents'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'detail', __( 'Detail Card', 'rafah' ), '.rafah-detail', array() ),
			array( 'text', 'dlabel', __( 'Label', 'rafah' ), '.rafah-detail__label', false ),
			array( 'text', 'dvalue', __( 'Value', 'rafah' ), '.rafah-detail__value', false ),
			array( 'grid', '.rafah-details-grid', 'details' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Video extends Rafah_Section_Widget_Base {
	protected function skey() { return 'video'; }
	public function get_title() { return __( 'Project · Video', 'rafah' ); }
	public function get_icon() { return 'eicon-youtube'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'embed', __( 'Video Frame', 'rafah' ), '.rafah-embed', array() ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Tour extends Rafah_Section_Widget_Base {
	protected function skey() { return 'tour'; }
	public function get_title() { return __( 'Project · Virtual Tour', 'rafah' ); }
	public function get_icon() { return 'eicon-preview-medium'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'embed', __( 'Tour Frame', 'rafah' ), '.rafah-embed', array() ),
			array( 'button', '.rafah-tour-cta .rafah-btn', 'tourbtn' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_FloorPlans extends Rafah_Section_Widget_Base {
	protected function skey() { return 'floor-plans'; }
	public function get_title() { return __( 'Project · Floor Plans', 'rafah' ); }
	public function get_icon() { return 'eicon-dashboard'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'plan', __( 'Plan Card', 'rafah' ), '.rafah-plan', array( 'hover' => true ) ),
			array( 'text', 'ptitle', __( 'Plan Title', 'rafah' ), '.rafah-plan__title', false ),
			array( 'image', '.rafah-plan', 'planimg' ),
			array( 'button', '.rafah-plan .rafah-btn', 'planbtn' ),
			array( 'grid', '.rafah-plans', 'plans' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

class Rafah_Widget_Section_Units extends Rafah_Section_Widget_Base {
	protected function skey() { return 'units'; }
	public function get_title() { return __( 'Project · Units', 'rafah' ); }
	public function get_icon() { return 'eicon-table'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'ubox', __( 'Table Wrapper', 'rafah' ), '.rafah-units-front', array( 'padding' => false ) ),
			array( 'box', 'uctrl', __( 'Search / Filter Bar', 'rafah' ), '.rafah-units-front__controls', array( 'shadow' => false ) ),
			array( 'box', 'uhead', __( 'Header Cells', 'rafah' ), '.rafah-units-fronttable thead th', array( 'radius' => false, 'shadow' => false ) ),
			array( 'text', 'uheadt', __( 'Header Text', 'rafah' ), '.rafah-units-fronttable thead th', false ),
			array( 'box', 'urow', __( 'Rows', 'rafah' ), '.rafah-units-fronttable tbody tr', array( 'radius' => false, 'shadow' => false, 'padding' => false, 'hover' => true ) ),
			array( 'box', 'ucell', __( 'Cells', 'rafah' ), '.rafah-units-fronttable tbody td', array( 'bg' => false, 'radius' => false, 'shadow' => false ) ),
			array( 'text', 'ucellt', __( 'Cell Text', 'rafah' ), '.rafah-units-fronttable tbody td', false ),
			array( 'text', 'uprice', __( 'Price Cells', 'rafah' ), '.rafah-units-front__price', false ),
			array( 'box', 'ubadge', __( 'Status Badge', 'rafah' ), '.rafah-ubadge', array( 'shadow' => false ) ),
			array( 'text', 'ubadget', __( 'Status Badge Text', 'rafah' ), '.rafah-ubadge', false ),
			array( 'text', 'uyes', __( 'Available Mark', 'rafah' ), '.rafah-units-front__yes', false ),
			array( 'text', 'uno', __( 'Unavailable Mark', 'rafah' ), '.rafah-units-front__no', false ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Amenities extends Rafah_Section_Widget_Base {
	protected function skey() { return 'amenities'; }
	public function get_title() { return __( 'Project · Amenities & Features', 'rafah' ); }
	public function get_icon() { return 'eicon-checkbox'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'chip', __( 'Chip', 'rafah' ), '.rafah-chips li', array() ),
			array( 'text', 'chiptext', __( 'Chip Text', 'rafah' ), '.rafah-chips li', false ),
			array( 'grid', '.rafah-chips', 'chips' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Nearby extends Rafah_Section_Widget_Base {
	protected function skey() { return 'nearby'; }
	public function get_title() { return __( 'Project · Nearby Places', 'rafah' ); }
	public function get_icon() { return 'eicon-map-pin'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'text', 'ngroup', __( 'Group Title', 'rafah' ), '.rafah-nearby__group-title', false ),
			array( 'text', 'ndist', __( 'Distance', 'rafah' ), '.rafah-nearby__distance', false ),
			array( 'grid', '.rafah-nearby', 'nearby' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Payment extends Rafah_Section_Widget_Base {
	protected function skey() { return 'payment'; }
	public function get_title() { return __( 'Project · Payment Plans', 'rafah' ); }
	public function get_icon() { return 'eicon-price-list'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'pcard', __( 'Plan Card', 'rafah' ), '.rafah-payment', array( 'align' => true, 'hover' => true ) ),
			array( 'text', 'pdown', __( 'Down Payment', 'rafah' ), '.rafah-payment__down', false ),
			array( 'text', 'ptitle', __( 'Plan Title', 'rafah' ), '.rafah-payment__title', false ),
			array( 'text', 'pdesc', __( 'Description', 'rafah' ), '.rafah-payment__desc', false ),
			array( 'grid', '.rafah-payments', 'pay' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

class Rafah_Widget_Section_Location extends Rafah_Section_Widget_Base {
	protected function skey() { return 'location'; }
	public function get_title() { return __( 'Project · Location / Map', 'rafah' ); }
	public function get_icon() { return 'eicon-google-maps'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'text', 'addr', __( 'Address', 'rafah' ), '.rafah-content', false ),
			array( 'box', 'embed', __( 'Map Frame', 'rafah' ), '.rafah-embed', array( 'radius' => true, 'shadow' => true ) ),
			array( 'button', '.rafah-btn', 'locbtn' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Downloads extends Rafah_Section_Widget_Base {
	protected function skey() { return 'downloads'; }
	public function get_title() { return __( 'Project · Downloads', 'rafah' ); }
	public function get_icon() { return 'eicon-download-bold'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'dl', __( 'Download Item', 'rafah' ), '.rafah-download', array( 'hover' => true ) ),
			array( 'text', 'dltext', __( 'Item Text', 'rafah' ), '.rafah-download span', false ),
			array( 'grid', '.rafah-downloads', 'dls' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_RequestInfo extends Rafah_Section_Widget_Base {
	protected function skey() { return 'request-info'; }
	public function get_title() { return __( 'Project · Request Info Form', 'rafah' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	protected function style_map() {
		return array(
			array( 'text', 'head', __( 'Heading', 'rafah' ), 'h2', true ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Related extends Rafah_Section_Widget_Base {
	protected function skey() { return 'related'; }
	public function get_title() { return __( 'Project · Related Projects', 'rafah' ); }
	public function get_icon() { return 'eicon-posts-grid'; }
	protected function style_map() {
		return array(
			array( 'heading', '.rafah-section-head__title' ),
			array( 'grid', '.rafah-grid', 'related' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

class Rafah_Widget_Section_Breadcrumbs extends Rafah_Section_Widget_Base {
	protected function skey() { return 'breadcrumbs'; }
	public function get_title() { return __( 'Project · Breadcrumbs', 'rafah' ); }
	public function get_icon() { return 'eicon-navigation-horizontal'; }
	protected function render_body( $project_id ) {
		if ( function_exists( 'rafah_theme_breadcrumbs' ) ) {
			rafah_theme_breadcrumbs();
		}
	}
	protected function style_map() {
		return array(
			array( 'text', 'bc', __( 'Breadcrumbs', 'rafah' ), '.rafah-breadcrumbs', true ),
			array( 'text', 'bclink', __( 'Links', 'rafah' ), '.rafah-breadcrumbs a', false ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Agent extends Rafah_Section_Widget_Base {
	protected function skey() { return 'agent'; }
	public function get_title() { return __( 'Project · Agent Card', 'rafah' ); }
	public function get_icon() { return 'eicon-person'; }
	protected function render_body( $project_id ) {
		if ( function_exists( 'rafah_theme_project_agent_card' ) ) {
			rafah_theme_project_agent_card( $project_id );
		}
	}
	protected function style_map() {
		return array(
			array( 'box', 'card', __( 'Agent Card', 'rafah' ), '.rafah-agent-mini', array( 'align' => true, 'hover' => true ) ),
			array( 'image', '.rafah-agent-mini img', 'aphoto' ),
			array( 'text', 'aname', __( 'Name', 'rafah' ), '.rafah-agent-mini__name', false ),
			array( 'text', 'apos', __( 'Position', 'rafah' ), '.rafah-agent-mini__position', false ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array( 'align' => true ) ),
		);
	}
}

class Rafah_Widget_Section_Sidebar extends Rafah_Section_Widget_Base {
	protected function skey() { return 'sidebar'; }
	public function get_title() { return __( 'Project · Sidebar Card', 'rafah' ); }
	public function get_icon() { return 'eicon-sidebar'; }
	protected function render_body( $project_id ) {
		if ( function_exists( 'rafah_theme_project_sidebar' ) ) {
			rafah_theme_project_sidebar( $project_id );
		}
	}
	protected function style_map() {
		return array(
			array( 'box', 'price', __( 'Price Box', 'rafah' ), '.rafah-aside-price', array( 'align' => true ) ),
			array( 'text', 'plabel', __( 'Price Label', 'rafah' ), '.rafah-aside-price__label', false ),
			array( 'text', 'pvalue', __( 'Price Value', 'rafah' ), '.rafah-aside-price__value', false ),
			array( 'box', 'card', __( 'Contact Card', 'rafah' ), '.rafah-aside-card', array() ),
			array( 'text', 'ctitle', __( 'Card Title', 'rafah' ), '.rafah-aside-card__title', false ),
			array( 'text', 'csub', __( 'Card Subtitle', 'rafah' ), '.rafah-aside-card__sub', false ),
			array( 'box', 'agent', __( 'Agent Card', 'rafah' ), '.rafah-agent-mini', array() ),
			array( 'text', 'aname', __( 'Agent Name', 'rafah' ), '.rafah-agent-mini__name', false ),
			array( 'button', '.rafah-btn--whatsapp', 'wbtn' ),
			array( 'button', '.rafah-btn--primary', 'cbtn' ),
			array( 'button', '.rafah-btn--secondary', 'bbtn' ),
			array( 'box', 'sec', __( 'Section Box', 'rafah' ), '.rafah-project-section', array() ),
		);
	}
}

/** Registry: widget key => class, merged into `rafah_core_widgets`. */
class Rafah_Section_Widgets {
	public static function classes() {
		return array(
			'section-breadcrumbs' => 'Rafah_Widget_Section_Breadcrumbs',
			'section-hero'        => 'Rafah_Widget_Section_Hero',
			'section-facts'       => 'Rafah_Widget_Section_Facts',
			'section-overview'    => 'Rafah_Widget_Section_Overview',
			'section-details'     => 'Rafah_Widget_Section_Details',
			'section-video'       => 'Rafah_Widget_Section_Video',
			'section-tour'        => 'Rafah_Widget_Section_Tour',
			'section-floorplans'  => 'Rafah_Widget_Section_FloorPlans',
			'section-units'       => 'Rafah_Widget_Section_Units',
			'section-agent'       => 'Rafah_Widget_Section_Agent',
			'section-sidebar'     => 'Rafah_Widget_Section_Sidebar',
			'section-amenities'   => 'Rafah_Widget_Section_Amenities',
			'section-nearby'      => 'Rafah_Widget_Section_Nearby',
			'section-payment'     => 'Rafah_Widget_Section_Payment',
			'section-location'    => 'Rafah_Widget_Section_Location',
			'section-downloads'   => 'Rafah_Widget_Section_Downloads',
			'section-request'     => 'Rafah_Widget_Section_RequestInfo',
			'section-related'     => 'Rafah_Widget_Section_Related',
		);
	}
}
