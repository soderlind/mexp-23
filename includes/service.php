<?php

/**
 * 23 service for Media Explorer.
 *
 * @since 0.1.0
 * @author Akeda Bagus <admin@gedex.web.id>
 */
class MEXP_23_Service extends MEXP_Service {

	/**
	 * Service name.
	 */
	const NAME = '23_mexp_service';

	/**
	 * Number of images to return by default.
	 */
	const DEFAULT_PER_PAGE = 18;

	/**
	 * Constructor.
	 *
	 * Sets template.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function __construct() {
		$this->set_template( new MEXP_23_Template );
	}

	/**
	 * Fired when the service is loaded.
	 *
	 * Enqueue static assets.
	 *
	 * Hooks into MEXP tabs and labels.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function load() {
		add_action( 'mexp_enqueue', array( $this, 'enqueue_statics' ) );
		add_action( 'mexp_tabs',    array( $this, 'tabs' ), 10, 1 );
		add_action( 'mexp_labels',  array( $this, 'labels' ), 10, 1 );
	}


	/**
	 * Enqueue static assets (CSS/JS).
	 *
	 * @since 0.1.0
	 * @action mexp_enqueue
	 * @return void
	 */
	public function enqueue_statics() {
		wp_enqueue_style(
			'mexp-23',
			trailingslashit( MEXP_23_URL ) . 'css/mexp-23.css',
			array( 'mexp' ),
			MEXP_23::VERSION
		);
	}

	/**
	 * Returns an array of tabs (routers) for the service's media manager panel.
	 *
	 * @since 0.1.0
	 * @filter mexp_tabs.
	 * @param array   $tabs Associative array of default tab items.
	 * @return array Associative array of tabs. The key is the tab ID and the value is an array of tab attributes.
	 */
	public function tabs( array $tabs ) {
		$tabs[ self::NAME ] = array(
			'all' => array(
				'text'       => _x( 'All', 'Tab title', 'mexp-23' ),
				'defaultTab' => true,
				'fetchOnRender' => true,
			),
			'tag' => array(
				'text' => _x( 'By Tag', 'Tab title', 'mexp-23' ),
			),
			// 'user' => array(
			//  'text' => _x( 'By User', 'Tab title', 'mexp-23' ),
			// ),
		);

		return $tabs;
	}

	/**
	 * Returns an array of custom text labels for this service.
	 *
	 * @since 0.1.0
	 * @filter mexp_labels
	 * @param array   $labels Associative array of default labels.
	 * @return array Associative array of labels.
	 */
	public function labels( array $labels ) {
		$labels[ self::NAME ] = array(
			'title'     => __( 'Insert 23 Video', 'mexp-23' ),
			'insert'    => __( 'Insert', 'mexp-23' ),
			'noresults' => __( 'No videos matched your search query', 'mexp-23' ),
			'loadmore'  => __( 'Load more videos', 'mexp-23' ),
		);

		return $labels;
	}

	public function request( array $request ) {


		if ( ! empty( $request['max_id'] ) ) {
			$p = $request['max_id'];
		} else {
			$p = 1;
		}

		if ( ! empty( $request['params']['text'] ) ) {
			$search = $request['params']['text'];
		}
		if ( ! empty( $request['params']['tag'] ) ) {
			$tags = $request['params']['tag'];
		}
		$size = (int) apply_filters( 'mexp_23_per_page', self::DEFAULT_PER_PAGE );
		$include_unpublished_p = 0;
		$video_p = 1;



		$options = get_option( 'v23_options' );

		if ( !isset( $options ) ) {
			return new WP_Error( 'missing-23-options', __( 'You must set the settings on the "Settings->23 Video" setting page.', 'mexp-23' ) );
		}

		extract( $options, EXTR_SKIP );
		require_once MEXP_23_DIR . 'lib/class-wp-23-video.php';
		$client = new WP_23_Video( $apiurl, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret );

		$request_args   = compact( 'include_unpublished_p', 'video_p', 'search', 'tags', 'p', 'size' );
		// Response from feed.
		$search_response = $client->get( '/api/photo/list', $request_args );
		if ( is_wp_error( $search_response ) )
			return $search_response;


		$response = new MEXP_Response();
		foreach ( $search_response['photos'] as $index => $photo ) {
			$item = new MEXP_Response_Item();

			//$item->set_id( $p + $index );
			$item->set_id( $photo['photo_id'] );
			$item->set_url( $photo['absolute_url'] );
			$item->set_content( $photo['title'] );

			$thumbnail_url = sprintf( "%s%s", $apiurl, $photo['medium_download'] );
			$item->set_thumbnail( $thumbnail_url );

			$item->add_meta( 'video', array(
					'video_length'        => $this->_seconds2time( $photo['video_length'] )
				) );
			$item->add_meta( 'user', $photo['display_name'] );
			$item->set_date( $photo['publish_date_epoch'] );
			$item->set_date_format( get_option( 'date_format' ) );

			$response->add_item( $item );
		}

		$response->add_meta( 'max_id', $p + 1 );

		return $response;
	}

	private function _seconds2time( $seconds = 0 ) {
		$hours = floor( $seconds / 3600 );
		$mins = floor( ( $seconds - ( $hours*3600 ) ) / 60 );
		$secs = floor( $seconds % 60 );

		return sprintf( "%02d:%02d:%02d", $hours, $mins, $secs );
	}

}
