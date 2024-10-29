<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://accessbynft.com
 * @since      1.0.0
 *
 * @package    Web3devs_ABNFT
 * @subpackage Web3devs_ABNFT/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Web3devs_ABNFT
 * @subpackage Web3devs_ABNFT/public
 * @author     Web3devs <wordpress@web3devs.com>
 */
class Web3devs_ABNFT_Public {
	/**
	 * The cookie name used to store individual user's connection id
	 */
	const COOKIE_NAME = 'web3devs-abnft';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_filter( 'request', array( $this, 'handleCallback' ));
		add_action( "the_content", array( $this, 'handleContentAccess' ));
		add_filter( "get_the_excerpt", array( $this, 'handleExcerptAccess' ));
		add_filter( "comments_open", array( $this, 'handleCommentsAccess' ));
		add_filter( "wp_list_comments_args", array( $this, 'handleCommentsListAccess' ));
		add_filter( "get_comments_number", array( $this, 'handleCommentsNumberAccess' ));
		add_action( 'add_meta_boxes', array($this, 'addWeb3devsABNFTBox'));
		add_action( 'save_post', array($this, 'handleSavePost'));

		add_action('init', array($this, 'initiateConnection'));
	}

	public function addWeb3devsABNFTBox( ) {
		 add_meta_box(
            'web3devs_abnft_box_id',
            'Access by NFT',
            array($this, 'renderWeb3devsABNFTBox'),
			'',             // 'post', // leave empty to add to all post types
			// 'side',
        );
	}

	public function renderWeb3devsABNFTBox( $post ) {
		$abnft_access = get_post_meta($post->ID, '_web3devs_abnft_meta_key', true);
		$coins = get_option('web3devs_abnft_configured_coins_setting');
	?>
		<label for="web3devs_abnft_field">Token restriction:</label>
		<select name="web3devs_abnft_field" id="web3devs_abnft_field" class="postbox">
			<option value="">No token required...</option>
			<?php foreach ($coins as $coin): ?>
				<option value="<?php echo esc_attr($coin['network'].'|'.$coin['contract']); ?>" <?php echo ($coin['network'].'|'.$coin['contract'] == $abnft_access) ? 'selected' : '' ?>><?php echo esc_html($coin['symbol']); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function handleSavePost($post_id) {
		if (array_key_exists('web3devs_abnft_field', $_POST)) {
			update_post_meta(
				$post_id,
				'_web3devs_abnft_meta_key',
				$_POST['web3devs_abnft_field']
			);
		}
	}

	private function responseError($error) {
		$resp = [
			'error' => $error
		];

		$selected = get_option('web3devs_abnft_denial_page_setting');
		$p = get_permalink($selected);
		if ($p) {
			$resp['redirect'] = $p;
		}

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: content-type");
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($resp);
		exit;
	}

	public function handleCallback($vars) {
		if (isset($_GET['web3devs-abnft-callback'])) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
				header("Access-Control-Allow-Origin: *");
				header("Access-Control-Allow-Headers: content-type");
				exit;
			}
			
			if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
				header("Access-Control-Allow-Origin: *");
				header("Access-Control-Allow-Headers: content-type");
				exit;
			}

			//See if we have token associated with this page
			$pid = url_to_postid(sanitize_url($_SERVER['HTTP_ORIGIN'].$_SERVER['REQUEST_URI']));
			$token = get_post_meta($pid, '_web3devs_abnft_meta_key', true);
			if (!$token) {
				header("Access-Control-Allow-Origin: *");
				header("Access-Control-Allow-Headers: content-type");
				exit;
			}

			//Grab request body
			$req = $entityBody = file_get_contents('php://input');
			$data = json_decode($req, true);

			//Validate required fields:
			$required = array(
				'network',
				'walletAddress',
				'publicKey',
				'token',
				'signature',
			);
			foreach ($required as $field) {
				if (!isset($data[$field]) || strlen($data[$field]) == 0) {
					$this->responseError('Missing required field: '.$field);
				}
			}

			//Verify signature
			if (!$this->verifyAccess(
				trim($data['network']),
				trim($data['walletAddress']),
				trim($data['publicKey']),
				trim($data['token']),
				trim($data['signature']),
			)) {
				$this->responseError('Signature verification failed');
			}

			//You've proven you have what we want, yay! Let's store it so you don't need to keep doing that every time!
            if (!$this->cacheToken($token)) {
				$this->responseError('Failed caching the token');
			}

            header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Headers: content-type");
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('message' => 'OK'));
			exit;
		}

		return $vars;
	}

	private function verifyAccess($network, $walletAddress, $publicKey, $token, $signature) {
		$rpc = 'https://api.accessbynft.com/check-token/wordpress';
		$apiKey = get_option('web3devs_abnft_api_key_setting');
		$options = [
			'headers'     => [
				'Content-Type' => 'application/json',
				'x-api-key'    => $apiKey,
			],
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.1',
			'sslverify'   => false,
			'body'        => wp_json_encode([
				'network' 		=> $network,
				'walletAddress' => $walletAddress,
				'publicKey' 	=> $publicKey,
				'token' 		=> $token,
				'signature' 	=> $signature,
			]),
		];

		$resp = wp_remote_post($rpc, $options);
		if (is_wp_error($resp)) {
			die("Error: call to URL $rpc failed with status $status, response $resp");
		}
		$body 	= json_decode(wp_remote_retrieve_body($resp), true);

		if ($body['error'] === null) {
			return true;
		}

		return false;
	}

	private function renderComponent() {
		global $post;
		$p = get_permalink($post);
		$access_token = get_post_meta($post->ID, '_web3devs_abnft_meta_key', true);
		$coins = get_option('web3devs_abnft_configured_coins_setting');
		$token = null;
		foreach ($coins as $coin) {
			if ($coin['network'].'|'.$coin['contract'] === $access_token) {
				$token = $coin;
			}
		}
		
		if ($token === null) {
			return null;
		}
		
		$url_parts = parse_url($p);
		$params = [];
		if (isset($url_parts['query'])) {
			parse_str($url_parts['query'], $params);
		}
		$params['web3devs-abnft-callback'] = '';
		$url_parts['query'] = http_build_query($params);
		$port = isset($url_parts['port']) ? ':'.$url_parts['port'] : '';
		$callback_url = $url_parts['scheme'].'://'.$url_parts['host'].$port.$url_parts['path'].'?'.$url_parts['query'];

		$data = [
			'message' 		=> hash('sha256', $this->getConnectionID()), //secret message to sign
			'callback' 		=> sanitize_url($callback_url),
			'tokenName'		=> $token['symbol'],
			'tokenAddress' 	=> $token['contract'],
			'network' 		=> $token['network'],
		];

		return '<div style="display: flex; align-items: center; flex-direction: column"><web-greeting tokenName="'.esc_attr($data['tokenName']).'" tokenAddress="'.esc_attr($data['tokenAddress']).'" network="'.esc_attr($data['network']).'" callback="'.esc_attr($data['callback']).'" message="'.esc_attr($data['message']).'"></web-greeting></div>';
	}

	//Check if we our plugin should control this page
	private function shouldControl() {
		global $post;
		$abnft_access = get_post_meta($post->ID, '_web3devs_abnft_meta_key', true);
		if (!empty($abnft_access)) {
			return true;
		}

		return false;
	}

	private function hasAccess() {
		//Get the required token
		global $post;
		$token = get_post_meta($post->ID, '_web3devs_abnft_meta_key', true);

        return $this->hasCachedToken($token);
    }

	public function handleContentAccess($content) {
		if ($this->shouldControl() && !$this->hasAccess()) {
			return $this->renderComponent();
		}

		return $content;
	}

	public function handleExcerptAccess($content) {
		if ($this->shouldControl() && !$this->hasAccess()) {
			return __( 'There is no excerpt because this is a protected post.' );
		}

		return $content;
	}

	public function handleCommentsAccess($open) {
		if ($this->shouldControl() && !$this->hasAccess()) {
			return false;
		}

		return $open;
	}

	public function handleCommentsListAccess($args) {
		if ($this->shouldControl() && !$this->hasAccess()) {
			$args['page'] = -1;
			$args['per_page'] = -1;
			$args['type'] = 'none';
		}

		return $args;
	}

	public function handleCommentsNumberAccess($number) {
		if ($this->shouldControl() && !$this->hasAccess()) {
			return 0;
		}

		return $number;
	}

	private function getPageToken() {
		global $post;
		$abnft_access = get_post_meta($post->ID, '_web3devs_abnft_meta_key', true);
		if (!$abnft_access) return null;

		$pts = explode('|', strtolower(trim($abnft_access)));
		if (count($pts) !== 2) {
			return null;
		}

		$npts = explode(':', $pts[0]);
		$chainID = $npts[0];
		$network = $npts[1];
		$token = $pts[1];

		return [
			'chainID' 	=> $chainID,
			'network' 	=> $network,
			'token' 	=> $token,
		];
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$pt = $this->getPageToken();
		if (!$pt) return;
		
		$manifest = json_decode(file_get_contents(dirname(__FILE__).'/js/'.$pt['chainID'].'-component/build/asset-manifest.json'), true);

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/web3devs-abnft-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$pt = $this->getPageToken();
		if (!$pt) return;

		$manifest = json_decode(file_get_contents(dirname(__FILE__).'/js/'.$pt['chainID'].'-component/build/asset-manifest.json'), true);

		//Yeah, we REALLY need this "defer"
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( $this->plugin_name . '-web-component' !== $handle ) {
				return $tag;
			}

			return str_replace( ' src', ' defer src', $tag ); // defer the script
		}, 10, 2 );
		wp_enqueue_script( $this->plugin_name . '-web-component-polyfil', plugin_dir_url( __FILE__ ) . 'js/custom-elements.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-web-component', plugin_dir_url( __FILE__ ) . 'js/'.$pt['chainID'].'-component/build'.$manifest['files']['main.js'], array( 'jquery' ), $this->version, false );
	}

	/**
	 * Returns cache ID
	 *
	 * Cache ID is built from our connection ID and a prefix (which in this case is our cookie's name)
	 */
	private function getCacheID() {
		return self::COOKIE_NAME.'_'.$this->getConnectionID();
	}

    private function cacheToken($token): bool
    {
		//fetch existing tokens
		$tokens = get_transient($this->getCacheID());
        if (is_null($tokens) || !is_array($tokens)) {
            $tokens = [];
        }
        $tokens[] = $token; //add a new one
        $tokens = array_unique($tokens); //make them unique

		delete_transient($this->getCacheID()); //because updating transients sometimes fails

		//set it back with the same transient
		return set_transient($this->getCacheID(), $tokens, 15 * 60); //store it for 15 minutes
    }

    private function hasCachedToken($token): bool
    {
		//get tokens from cache
        $tokens = get_transient($this->getCacheID());

        // check tokens fetched from cache are valid
        if (!is_array($tokens)) {
            return false;
        }

        foreach ($tokens as $t) {
            if ($t === $token) {
                return true;
            }
        }

        return false;
    }

	/**
	 * Creates connection ID from ChaCha20 Poly1305 key and stores it as a cookie
	 *
	 * The key can't be guessed, can't be manipulated
	 */
	private function createConnectionID()
	{
		$connectionID = bin2hex(sodium_crypto_aead_chacha20poly1305_keygen());
		setcookie(self::COOKIE_NAME, $connectionID, strtotime('+15min'), '/');

		return $connectionID;
	}

	/**
	 * Initiates a connection
	 *
	 * If there's no cookie set (or available right away), creates a connection ID and sotres it as a cookie
	 *
	 * This should be a one-off call
	 */
    public function initiateConnection()
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            $_COOKIE[self::COOKIE_NAME] = $this->createConnectionID();
        }
    }

	/**
	 * Validates connection ID
	 *
	 * ...which is 64 alphanumeric
	 */
    private function validateConnectionID($tokenFromCookie)
    {
        return preg_match('/^[a-z0-9]{64}$/', $tokenFromCookie) === 1;
    }

	/**
	 * Returns Connection ID
	 *
	 * If the cookie-provided connection ID seams to be valid - return it (again: it can't be guessed, it can't be manipulated)
	 * If it's not - creates a new one (essentially replacing the one in a cookie provided)
	 */
	private function getConnectionID() {
		if ($this->validateConnectionID($_COOKIE[self::COOKIE_NAME])) {
			return $_COOKIE[self::COOKIE_NAME];
		}

		return $this->createConnectionID();
	}
}
