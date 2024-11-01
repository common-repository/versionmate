<?php
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Class Versionmate_Notification
 */
class Versionmate_Notification {

	/**
	 * Notification type
	 * @var string
	 */
	private $type = 'notice';

	/**
	 * @var string
	 */
	private $text = '';

	/**
	 * @param string $text
	 */
	public function setText( $text ) {
		$this->text = $text;
	}

	/**
	 * @param string $type
	 */
	public function setType( $type ) {
		$this->type = $type;
	}


	/**
	 *
	 */
	public function show() {
		switch ( $this->type ) {
			case 'error':
				add_action( 'admin_notices', array( $this, 'error' ) );
				break;
			default:
				add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	}

	/**
	 * Show error notification
	 */
	public function error() {
		?>
		<div class="error">
			<p><?php echo $this->text ?></p>
		</div>
		<?php
	}

	/**
	 * Show notice notification
	 */
	public function notice() {
		?>
		<div class="notice">
			<p><?php echo $this->text ?></p>
		</div>
		<?php
	}

}