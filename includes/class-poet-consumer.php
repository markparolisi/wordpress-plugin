<?php
/**
 * Class to interact with the Po.et API
 *
 * @package Poet
 */

namespace Poet;

defined( 'ABSPATH' ) or exit;

/**
 * Class Consumer
 *
 * Holds the responsibility of contacting the API and exchanging data with it
 *
 * @package Poet
 */
class Consumer {

	/**
	 * Name of the author
	 *
	 * @var string
	 */
	protected $author;

	/**
	 * API URL
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * API Token
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * WP_Post data
	 *
	 * @var array
	 */
	protected $post;

	/**
	 * Consumer constructor.
	 *
	 * @param string $author name of the author.
	 * @param string $url API url.
	 * @param string $token API token.
	 * @param array  $post WordPress post data.
	 */
	public function __construct( $author, $url, $token, $post ) {
		$this->author = $author;
		$this->url    = $url;
		$this->token  = $token;
		$this->post   = $post;
	}

	/**
	 * Get Author Name method
	 *
	 * Returns author name set in settings page or the name of the user who published the post
	 *
	 * @return string
	 */
	private function get_author_name() {
		$author = $this->author;

		if ( empty( $author ) && property_exists( $this->post, 'post_author' ) ) {
			$user = get_user_by( 'ID', $this->post->post_author );
			if ( ! empty( $user ) && is_a( $user, 'WP_User' ) ) {
				$author = $user->display_name;
			}
		}

		return $author;
	}

	/**
	 * Consume method
	 *
	 * The main method used to send articles to Po.et API
	 *
	 * @return array|\WP_Error
	 */
	public function consume() {

		$response = false;

		if ( property_exists( $this->post, 'ID' ) ) {
			$tags_array = wp_get_post_tags( $this->post->ID, [ 'fields' => 'names' ] );
			$tags       = implode( ',', $tags_array );

			$body_array = [
				'name'          => $this->post->post_title,
				'datePublished' => get_the_modified_time( 'c', $this->post ),
				'dateCreated'   => get_the_time( 'c', $this->post ),
				'author'        => $this->get_author_name(),
				'tags'          => $tags,
				'content'       => $this->post->post_content,
			];

			$body_json = wp_json_encode( $body_array );

			$response = wp_remote_post(
				$this->url, [
					'method'  => 'POST',
					'timeout' => 30,
					'headers' => [
						'Content-Type' => 'application/json',
						'token'        => $this->token,
					],
					'body'    => $body_json,
				]
			);
		}

		return $response;
	}
}
