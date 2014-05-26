<?php
/**
 * @package IssueM's Leaky Paywall
 * @since 1.0.0
 */
 
if ( !function_exists( 'get_issuem_leaky_paywall_settings' ) ) {

	/**
	 * Helper function to get IssueM's Leaky Paywall settings for current site
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Value set for the issuem options.
	 */
	function get_issuem_leaky_paywall_settings() {
	
		global $dl_pluginissuem_leaky_paywall;
		
		return $dl_pluginissuem_leaky_paywall->get_settings();
		
	}
	
}

if ( !function_exists( 'get_issuem_leaky_paywall_subscriber_by_email' ) ) {

	/**
	 * Gets Subscriber infromation from user's email address
	 *
	 * @since 1.0.0
	 *
	 * @param string $email address of user "logged" in
	 * @return mixed $wpdb row object or false
	 */
	function get_issuem_leaky_paywall_subscriber_by_email( $email ) {
	
		global $wpdb;
			
		if ( is_email( $email ) ) {
			
			$settings = get_issuem_leaky_paywall_settings();
			$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';

			$user = get_user_by( 'email', $email );
			
			if ( !empty ( $user ) ) {
				$hash = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_hash', true );
				$subcriber = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_' . $hash, true );
				return $subcriber;
			}
			
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'get_issuem_leaky_paywall_subscriber_by_hash' ) ) {

	/**
	 * Gets Subscriber infromation from user's unique hash
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logged" in
	 * @return mixed $wpdb row object or false
	 */
	function get_issuem_leaky_paywall_subscriber_by_hash( $hash ) {

		if ( preg_match( '#^[0-9a-f]{32}$#i', $hash ) ) { //verify we get a valid 32 character md5 hash
			
			$settings = get_issuem_leaky_paywall_settings();
			$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';
			
			$args = array(
				'meta_key'   => '_issuem_leaky_paywall_' . $mode . '_hash',
				'meta_value' => $hash,
			);
			$users = get_users( $args );
		
			if ( !empty( $users ) ) {
				foreach ( $users as $user ) {
					//should really only be one
					$subcriber = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_' . $hash, true );
					return $subscriber;
				}
			}
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'add_issuem_leaky_paywall_hash' ) ) {

	/**
	 * Adds unique hash to login table for user's login link
	 *
	 * @since 1.0.0
	 *
	 * @param string $email address of user "logging" in
	 * @param string $hash of user "logging" in
	 * @return mixed $wpdb insert ID or false
	 */
	function add_issuem_leaky_paywall_hash( $email, $hash ) {
	
		$expiration = apply_filters( 'leaky_paywall_login_link_expiration', 60 * 60 ); //1 hour
		set_transient( '_lpl_' . $hash, $email, $expiration );
			
	}
	
}

if ( !function_exists( 'verify_unique_issuem_leaky_paywall_hash' ) ) {

	/**
	 * Verifies hash is valid for login link
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logging" in
	 * @return mixed $wpdb var or false
	 */
	function verify_unique_issuem_leaky_paywall_hash( $hash ) {
	
		if ( preg_match( '#^[0-9a-f]{32}$#i', $hash ) ) { //verify we get a valid 32 character md5 hash
			
			return ( false !== get_transient( '_lpl_' . $hash ) );
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'verify_issuem_leaky_paywall_hash' ) ) {

	/**
	 * Verifies hash is valid length and hasn't expired
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logging" in
	 * @return mixed $wpdb var or false
	 */
	function verify_issuem_leaky_paywall_hash( $hash ) {
		
		if ( preg_match( '#^[0-9a-f]{32}$#i', $hash ) ) { //verify we get a valid 32 character md5 hash
				
			return (bool) get_transient( '_lpl_' . $hash );
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'get_issuem_leaky_paywall_email_from_login_hash' ) ) {

	/**
	 * Gets logging in user's email address from login link's hash
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logging" in
	 * @return string email from $wpdb or false if invalid hash or expired link
	 */
	function get_issuem_leaky_paywall_email_from_login_hash( $hash ) {
		
		if ( preg_match( '#^[0-9a-f]{32}$#i', $hash ) ) { //verify we get a valid 32 character md5 hash
				
			return get_transient( '_lpl_' . $hash );
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_has_user_paid' ) ) {

	/**
	 * Verified if user has paid through Stripe
	 *
	 * @since 1.0.0
	 *
	 * @param string $email address of user "logged" in
	 * @return mixed Expiration date or subscriptions status or false if not paid
	 */
	function issuem_leaky_paywall_has_user_paid( $email ) {
		
		$settings = get_issuem_leaky_paywall_settings();
		
		if ( is_email( $email ) ) {
			
			if ( $customer = get_issuem_leaky_paywall_subscriber_by_email( $email ) ) {
		
				try {
					
					$settings = get_issuem_leaky_paywall_settings();
					$secret_key = ( 'on' === $settings['test_mode'] ) ? $settings['test_secret_key'] : $settings['live_secret_key'];
					$expires = $customer['expires'];
					
					if ( 'stripe' === $customer['payment_gateway'] ) {
								
						$cu = Stripe_Customer::retrieve( $customer['subscriber_id'] );
											
						if ( !empty( $cu ) )
							if ( !empty( $cu->deleted ) && true === $cu->deleted )
								return false;
						
						if ( !empty( $customer['plan'] ) ) {
										
							if ( isset( $cu->subscription ) ) {
								
								if ( 'active' === $cu->subscription->status )
									return 'subscription';
						
							}
							
							return false;
							
						}
						
						$ch = Stripe_Charge::all( array( 'count' => 1, 'customer' => $customer['subscriber_id'] ) );
												
						if ( '0000-00-00 00:00:00' !== $expires ) {
							
							if ( strtotime( $expires ) > time() )
								if ( true === $ch->data[0]->paid && false === $ch->data[0]->refunded )
									return $expires;
							else
								return false;
									
						} else {
						
							return 'unlimited';
							
						}
					
					} else if ( 'paypal_standard' === $customer['payment_gateway'] ) {
						
						if ( '0000-00-00 00:00:00' === $expires )
							return 'unlimited';
						
						if ( !empty( $customer['plan'] ) && 'active' == $customer['payment_status'] )
							return 'subscription';
							
						switch( $customer['payment_status'] ) {
						
							case 'active':
							case 'refunded':
							case 'refund':
								if ( strtotime( $expires ) > time() )
									return $expires;
								else
									return false;
								break;
							case 'canceled':
								return 'canceled';
							case 'reversed':
							case 'buyer_complaint':
							case 'denied' :
							case 'expired' :
							case 'failed' :
							case 'voided' :
							case 'deactivated' :
								return false;
								break;
							
						}
						
					} else if ( 'manual' === $customer['payment_gateway'] ) {
							
						switch( $customer['payment_status'] ) {
						
							case 'active':
							case 'refunded':
							case 'refund':
								if ( $expires === '0000-00-00 00:00:00' )
									return 'unlimited';
									
								if ( strtotime( $expires ) > time() )
									return $expires;
								else
									return false;
								break;
							case 'canceled':
								if ( $expires === '0000-00-00 00:00:00' )
									return false;
								else
									return 'canceled';
							case 'reversed':
							case 'buyer_complaint':
							case 'denied' :
							case 'expired' :
							case 'failed' :
							case 'voided' :
							case 'deactivated' :
								return false;
								break;
							
						}
						
					}
					
				} catch ( Exception $e ) {
				
					echo '<h1>' . sprintf( __( 'Error processing request: %s', 'issuem-leaky-paywall' ), $e->getMessage() ) . '</h1>'; 
					
				}
				
				return false;
									
			}
			
		}
	
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_process_paypal_standard_ipn' ) ) {

	/**
	 * Processes a PayPal IPN
	 *
	 * @since 1.1.0
	 *
	 * @param array $request
	 */
	function issuem_process_paypal_standard_ipn() {
		
		$settings = get_issuem_leaky_paywall_settings();
		
		$subscriber_id = !empty( $_REQUEST['subscr_id'] ) ? $_REQUEST['subscr_id'] : false;
		$subscriber_id = !empty( $_REQUEST['recurring_payment_id'] ) ? $_REQUEST['recurring_payment_id'] : $subscriber_id;
		
		if ( !empty( $_REQUEST['txn_type'] ) ) {
			$subscriber = get_issuem_leaky_paywall_subscriber_by_email( $_REQUEST['custom'] );
			
			if ( !empty( $subscriber ) ) {
				
				switch( $_REQUEST['txn_type'] ) {
				
					case 'web_accept':
						switch( strtolower( $_REQUEST['payment_status'] ) ) {
						
							case 'completed' :
							case 'reversed' :
								$subscriber['payment_status'] = strtolower( $_REQUEST['payment_status'] );
								break;			
						}
						break;
						
					case 'subscr_signup':
						$period = $_REQUEST['period3'];
						$subscriber['plan'] = strtoupper( $period );
						break;
						
					case 'subscr_payment':
						if ( $_REQUEST['txn_id'] === $subscriber['subscriber_id'] )
							$subscriber['subscriber_id'] = $_REQUEST['subscr_id'];
							
						if ( !empty( $subscriber['plan'] ) ) {// @todo
							$new_expiration = date( 'Y-m-d 23:59:59', strtotime( '+' . str_replace( array( 'D', 'W', 'M', 'Y' ), array( 'Days', 'Weeks', 'Months', 'Years' ), $subscriber['plan'] ), strtotime( $_REQUEST['payment_date'] ) ) );
							switch( strtolower( $_REQUEST['payment_status'] ) ) {
								case 'completed' :
									$subscriber['expires'] = $new_expiration;
									break;
							}
						}
						break;
						
					case 'subscr_cancel':
						$subscriber['payment_status'] = 'canceled';
						break;
						
					case 'subscr_eot':
						$subscriber['payment_status'] = 'expired';
						break;
					
				}
				
			} else {
			
				error_log( sprintf( __( 'Unable to find PayPal subscriber: %s', 'issuem-leaky-paywall' ), maybe_serialize( $_REQUEST ) ) );
				
			}
			
		}
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_new_subscriber' ) ) {

	/**
	 * Adds new subscriber to subscriber table
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logged" in
	 * @param string $email address of user "logged" in
	 * @param object $customer Stripe object
	 * @param array $args Arguments passed from type of subscriber
	 * @return mixed $wpdb insert ID or false
	 */
	function issuem_leaky_paywall_new_subscriber( $hash, $email, $customer, $args ) {
		
		if ( is_email( $email ) ) {
			
			$settings = get_issuem_leaky_paywall_settings();
			
			$expires = '0000-00-00 00:00:00';
			
			if ( $user = get_user_by( 'email', $email ) ) { 
				//the user already exists
				//grab the ID for later
				$user_id = $user->ID;
			} else {
				//the user doesn't already exist
				//create a new user with their email address as their username
				//grab the ID for later
				$userdata = array(
					'user_login' => $email,
					'user_email' => $email,
					'user_pass'  => wp_generate_password(),
				);
				$user_id = wp_insert_user( $userdata ) ;
			}
			
			if ( isset( $customer->subscription ) ) { //only stripe
			
				$meta = array(
					'user_id'         => $user_id,
					'hash'			  => $hash,
					'subscriber_id'   => $customer->id,
					'price'			  => number_format( $customer->subscription->plan->amount / 100, '2', '.', '' ),
					'description' 	  => $customer->subscription->plan->name,
					'plan'		 	  => $customer->subscription->plan->id,
					'created'		  => date( 'Y-m-d H:i:s', $customer->subscription->start ),
					'expires'		  => $expires,
					'mode'			  => 'off' === $settings['test_mode'] ? 'live' : 'test',
					'payment_gateway' => 'stripe',
					'payment_status'  => $args['payment_status'],
				);		
						
			} else {
				
				if ( 0 !== $args['interval'] )
					$expires = date( 'Y-m-d 23:59:59', strtotime( '+' . $args['interval_count'] . ' ' . $args['interval'] ) ); //we're generous, give them the whole day!
				else if ( !empty( $args['expires'] ) )
					$expires = $args['expires'];
				
				$meta = array(
					'user_id'         => $user_id,
					'hash'			  => $hash,
					'subscriber_id'   => $customer->id,
					'price'			  => $args['price'],
					'description'	  => $args['description'],
					'plan'			  => '',
					'created'		  => date( 'Y-m-d H:i:s' ),
					'expires'		  => $expires,
					'mode'			  => 'off' === $settings['test_mode'] ? 'live' : 'test',
					'payment_gateway' => $args['payment_gateway'],
					'payment_status'  => $args['payment_status'],
				);

			}
			
			$meta = apply_filters( 'issuem_leaky_paywall_new_subscriber_meta', $meta, $email, $customer, $args );

            update_user_meta( $user_id, '_issuem_leaky_paywall_' . $meta['mode'] . '_hash', $hash );
            update_user_meta( $user_id, '_issuem_leaky_paywall_' . $meta['mode'] . '_' . $hash, $meta );
			
			do_action( 'issuem_leaky_paywall_new_subscriber', $email, $meta, $customer, $args );
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_update_subscriber' ) ) {

	/**
	 * Updates an existing subscriber to subscriber table
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logged" in
	 * @param string $email address of user "logged" in
	 * @param object $customer Stripe object
	 * @param array $args Arguments passed from type of subscriber
	 * @return mixed $wpdb insert ID or false
	 */
	function issuem_leaky_paywall_update_subscriber( $hash, $email, $customer, $args ) {
		
		global $wpdb;
		
		if ( is_email( $email ) ) {
			
			$settings = get_issuem_leaky_paywall_settings();
			
			$expires = '0000-00-00 00:00:00';
			
			if ( $user = get_user_by( 'email', $email ) ) { 
				//the user already exists
				//grab the ID for later
				$user_id = $user->ID;
			} else {
				//the user doesn't already exist
				//create a new user with their email address as their username
				//grab the ID for later
				$userdata = array(
					'user_login' => $email,
					'user_email' => $email,
					'user_pass'  => wp_generate_password(),
				);
				$user_id = wp_insert_user( $userdata ) ;
			}
						
			if ( isset( $customer->subscription ) ) { //only stripe
			
				$meta = array(
					'user_id'         => $user_id,
					'hash'			  => $hash,
					'subscriber_id'   => $customer->id,
					'price'			  => number_format( $customer->subscription->plan->amount, '2', '.', '' ),
					'description'	  => $customer->subscription->plan->name,
					'plan'			  => $customer->subscription->plan->id,
					'created'		  => date( 'Y-m-d H:i:s', $customer->subscription->start ),
					'expires'		  => $expires,
					'mode'			  => 'off' === $settings['test_mode'] ? 'live' : 'test',
					'payment_gateway' => 'stripe',
					'payment_status'  => $args['payment_status'],
				);		
						
			} else {
				
				if ( 0 !== $args['interval'] )
					$expires = date( 'Y-m-d 23:59:59', strtotime( '+' . $args['interval_count'] . ' ' . $args['interval'] ) ); //we're generous, give them the whole day!
				else if ( !empty( $args['expires'] ) )
					$expires = $args['expires'];
				
				$meta = array(
					'user_id'         => $user_id,
					'hash'			  => $hash,
					'subscriber_id'   => $customer->id,
					'price'			  => $args['price'],
					'description'	  => $args['description'],
					'plan'			  => '',
					'created'		  => date( 'Y-m-d H:i:s' ),
					'expires'		  => $expires,
					'mode'			  => 'off' === $settings['test_mode'] ? 'live' : 'test',
					'payment_gateway' => $args['payment_gateway'],
					'payment_status'  => $args['payment_status'],
				);

			}
			
			$meta = apply_filters( 'issuem_leaky_paywall_update_subscriber_meta', $meta, $email, $customer, $args );

            update_user_meta( $user_id, '_issuem_leaky_paywall_' . $meta['mode'] . '_hash', $hash );
            update_user_meta( $user_id, '_issuem_leaky_paywall_' . $meta['mode'] . '_' . $hash, $meta );
			
			do_action( 'issuem_leaky_paywall_update_subscriber', $email, $meta, $customer, $args );
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_translate_payment_gateway_slug_to_name' ) ) {
	
	function issuem_translate_payment_gateway_slug_to_name( $slug ) {
		
		switch( $slug ) {
		
			case 'stripe':
				$return = 'Stripe';
				break;
				
			case 'paypal_standard':
				$return = 'PayPal';
				break;
				
			case 'manual':
				$return = __( 'Manually Added', 'issue-leaky-paywall' );
				break;
			
		}
		
		return apply_filters( 'issuem_translate_payment_gateway_slug_to_name', $return, $slug );
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_cancellation_confirmation' ) ) {

	/**
	 * Cancels a subscriber from Stripe subscription plan
	 *
	 * @since 1.0.0
	 *
	 * @return string Cancellation form output
	 */
	function issuem_leaky_paywall_cancellation_confirmation() {
		
		$settings = get_issuem_leaky_paywall_settings();
		
		$form = '';

		if ( isset( $_REQUEST['cancel'] ) && empty( $_REQUEST['cancel'] ) ) {

			$form = '<h3>' . __( 'Cancel Subscription', 'issuem-leaky-paywall' ) . '</h3>';

			$form .= '<p>' . __( 'Cancellations take effect at the end of your billing cycle, and we can’t give partial refunds for unused time in the billing cycle. If you still wish to cancel now, you may proceed, or you can come back later.', 'issuem-leaky-paywall' ) . '</p>';
			$form .= '<p>' . sprintf( __( ' Thank you for the time you’ve spent subscribed to %s. We hope you’ll return someday. ', 'issuem-leaky-paywall' ), $settings['site_name'] ) . '</p>';
			$form .= '<a href="' . add_query_arg( array( 'cancel' => 'confirm' ) ) . '">' . __( 'Yes, cancel my subscription!', 'issuem-leaky-paywall' ) . '</a> | <a href="' . get_home_url() . '">' . __( 'No, get me outta here!', 'issuem-leak-paywall' ) . '</a>';
			
			
		} else if ( !empty( $_REQUEST['cancel'] ) && 'confirm' === $_REQUEST['cancel'] ) {
		
			if ( isset( $_COOKIE['issuem_lp_subscriber'] ) ) {
				
				if ( $customer = get_issuem_leaky_paywall_subscriber_by_hash( $_COOKIE['issuem_lp_subscriber'] ) ) {
					
					if ( 'stripe' === $customer['payment_gateway'] ) {
					
						try {
							
							$secret_key = ( 'test' === $customer['mode'] ) ? $settings['test_secret_key'] : $settings['live_secret_key'];
							
							$expires = $customer['expires'];
														
							$cu = Stripe_Customer::retrieve( $customer['subscriber_id'] );
								
							if ( !empty( $cu ) )
								if ( true === $cu->deleted )
									throw new Exception( __( 'Unable to find valid Stripe customer ID to unsubscribe. Please contact support', 'issuem-leaky-paywall' ) );
							
							$results = $cu->cancelSubscription();
												
							if ( 'canceled' === $results->status ) {
								
								$form .= '<p>' . sprintf( __( 'Your subscription has been successfully canceled. You will continue to have access to %s until the end of your billing cycle. Thank you for the time you have spent subscribed to our site and we hope you will return soon!', 'issuem-leaky-paywall' ), $settings['site_name'] ) . '</p>';
								
								unset( $_SESSION['issuem_lp_hash'] );
								unset( $_SESSION['issuem_lp_email'] );
								unset( $_SESSION['issuem_lp_subscriber'] );
								setcookie( 'issuem_lp_subscriber', null, 0, '/' );
								
							} else {
							
								$form .= '<p>' . sprintf( __( 'ERROR: An error occured when trying to unsubscribe you from your account, please try again. If you continue to have trouble, please contact us. Thank you.', 'issuem-leaky-paywall' ), $settings['site_name'] ) . '</p>';
								
							}
							
							$form .= '<a href="' . get_home_url() . '">' . sprintf( __( 'Return to %s...', 'issuem-leak-paywall' ), $settings['site_name'] ) . '</a>';
							
						} catch ( Exception $e ) {
						
							$results = '<h1>' . sprintf( __( 'Error processing request: %s', 'issuem-leaky-paywall' ), $e->getMessage() ) . '</h1>';
							
						}
					
					} else if ( 'paypal_standard' === $customer['payment_gateway'] ) {

						$paypal_url   = 'test' === $customer['mode'] ? 'https://www.sandbox.paypal.com/' : 'https://www.paypal.com/';
						$paypal_email = 'test' === $customer['mode'] ? $settings['paypal_sand_email'] : $settings['paypal_live_email'];
						$form .= '<p>' . sprintf( __( 'You must cancel your account through PayPal. Please click this unsubscribe button to complete the cancellation process.', 'issuem-leaky-paywall' ), $settings['site_name'] ) . '</p>';
						$form .= '<p><a href="' . $paypal_url . '?cmd=_subscr-find&alias=' . urlencode( $paypal_email ) . '"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_unsubscribe_LG.gif" border="0"></a></p>';
					}
				}
				
			}
			
			
		}
		
		return $form;
		
	}
	
}

if ( !function_exists( 'send_leaky_paywall_email' ) ) {

	/**
	 * Function to generate and send leaky paywall login email to user
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email address of user requesting login link
	 * @return bool True if successful, false if failed
	 */
	function send_leaky_paywall_email( $email ) {
	
		if ( !is_email( $email ) )
			return false; //We already checked, but want to be absolutely sure
			
		global $wpdb;
		
		$settings = get_issuem_leaky_paywall_settings();
		
		$login_url = get_page_link( $settings['page_for_login'] );
		$login_hash = issuem_leaky_paywall_hash( $email );
		
		add_issuem_leaky_paywall_hash( $email, $login_hash );
		
		$message  = 'Log into ' . $settings['site_name']  . ' by opening this link:' . "\r\n";
		$message .= add_query_arg( 'r', $login_hash, $login_url ) . "\r\n";
		$message .= 'This link will expire after an hour and can only be used once. To log into multiple browsers, send a login request from each one.' . "\r\n";
		$message .= " - " . $settings['site_name'] . "'s passwordless login system" . "\r\n";
		
		$message = apply_filters( 'leaky_paywall_login_email_message', $message );
		
		$headers = 'From: ' . $settings['from_name'] .' <' . $settings['from_email'] . '>' . "\r\n";
		
		return wp_mail( $email, __( 'Log into ' . get_bloginfo( 'name' ), 'issuem-leaky-paywall' ), $message, $headers );
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_hash' ) ) {

	/**
	 * Creates a 32-character hash string
	 *
	 * Generally used to create a unique hash for each subscriber, stored in the database
	 * and used for campaign links
	 *
	 * @since 1.0.0
	 *
	 * @param string $str String you want to hash
	 */
	function issuem_leaky_paywall_hash( $str ) {
	
		if ( defined( SECURE_AUTH_SALT ) )
			$salt[] = SECURE_AUTH_SALT;
			
		if ( defined( AUTH_SALT ) )
			$salt[] = AUTH_SALT;
		
		$salt[] = get_bloginfo( 'name' );
		$salt[] = time();
		
		$hash = md5( md5( implode( $salt ) ) . md5( $str ) );
		
		while( verify_unique_issuem_leaky_paywall_hash( $hash ) )
			$hash = issuem_leaky_paywall_hash( $hash ); // I did this on purpose...
			
		return $hash; // doesn't have to be too secure, just want a pretty random and very unique string
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_attempt_login' ) ) {

	function issuem_leaky_paywall_attempt_login( $login_hash ) {

		$_SESSION['issuem_lp_hash'] = $login_hash;

		if ( false !== $email = get_issuem_leaky_paywall_email_from_login_hash( $login_hash ) ) {

			$_SESSION['issuem_lp_email'] = $email;

			if ( false !== $expires = issuem_leaky_paywall_has_user_paid( $email ) ) {

				if ( $customer = get_issuem_leaky_paywall_subscriber_by_email( $email ) ) {

					if ( 'active' === $customer['payment_status'] ) {

						$_SESSION['issuem_lp_subscriber'] = $customer['hash'];
						setcookie( 'issuem_lp_subscriber', $_SESSION['issuem_lp_subscriber'], strtotime( apply_filters( 'issuem_leaky_paywall_logged_in_cookie_expiry', '+1 year' ) ), '/' );
						delete_transient( '_lpl_' . $login_hash ); //one time use
						wp_set_current_user( $customer['user_id'] );
						wp_set_auth_cookie( $customer['user_id'] );
						
					}

				}

			}

		}

	}

}

if ( !function_exists( 'is_issuem_leaky_subscriber_logged_in' ) ) {

	/**
	 * Checks if current user is logged in as a leaky paywall subscriber
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if logged in, else false
	 */
	function is_issuem_leaky_subscriber_logged_in() {
		
		if ( is_user_logged_in() && empty( $_SESSION['issuem_lp_subscriber'] ) ) {
			$settings = get_issuem_leaky_paywall_settings();
			$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';
			$user_id = get_current_user_id();
			if ( $hash = get_user_meta( $user_id, '_issuem_leaky_paywall_' . $mode . '_hash', true ) ) {
				$_SESSION['issuem_lp_subscriber'] = $hash;
			}
		}
	
		if ( !empty( $_SESSION['issuem_lp_subscriber'] ) && empty( $_COOKIE['issuem_lp_subscriber'] ) ) {
		
			$_COOKIE['issuem_lp_subscriber'] = $_SESSION['issuem_lp_subscriber'];
			setcookie( 'issuem_lp_subscriber', $_SESSION['issuem_lp_subscriber'], strtotime( apply_filters( 'issuem_leaky_paywall_logged_in_cookie_expiry', '+1 year' ) ), '/' );

		}
			
		if ( !empty( $_COOKIE['issuem_lp_subscriber'] ) ) {

			$_SESSION['issuem_lp_subscriber'] = $_COOKIE['issuem_lp_subscriber'];
			
			if ( empty( $_SESSION['issuem_lp_email'] ) ) 
				$_SESSION['issuem_lp_email'] = issuem_leaky_paywall_get_email_from_subscriber_hash( $_COOKIE['issuem_lp_subscriber'] );
			
			if ( false !== $expires = issuem_leaky_paywall_has_user_paid( $_SESSION['issuem_lp_email'] ) )
				return true;
			
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_get_email_from_subscriber_hash' ) ){

	/**
	 * Gets email address from subscriber's hash
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash of user "logged" in
	 * @return mixed $wpdb var or false if invalid hash
	 */
	function issuem_leaky_paywall_get_email_from_subscriber_hash( $hash ) {
	
		if ( preg_match( '#^[0-9a-f]{32}$#i', $hash ) ) { //verify we get a valid 32 character md5 hash
			
			$settings = get_issuem_leaky_paywall_settings();
			$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';
			
			$args = array(
				'meta_key'   => '_issuem_leaky_paywall_' . $mode . '_hash',
				'meta_value' => $hash,
			);
			$users = get_users( $args );
		
			if ( !empty( $users ) ) {
				foreach ( $users as $user ) {
					//should really only be one
					return $user->user_email;
				}
			}
		
		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_subscriber_query' ) ){

	/**
	 * Gets leaky paywall subscribers
	 *
	 * @since 1.1.0
	 *
	 * @param array $args Leaky Paywall Subscribers
	 * @return mixed $wpdb var or false if invalid hash
	 */
	function issuem_leaky_paywall_subscriber_query( $args ) {
	
		if ( !empty( $args ) ) {
			
			$settings = get_issuem_leaky_paywall_settings();
			$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';
			
			if ( !empty( $args['search'] ) ) {
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_issuem_leaky_paywall_' . $mode . '_hash',
						'compare' => 'EXISTS',
					),
					array(
						'value'   => $args['search'],
						'compare' => 'LIKE',
					),
				);
				unset( $args['search'] );
			} else {
				$args['meta_query'] = array(
					array(
						'key'     => '_issuem_leaky_paywall_' . $mode . '_hash',
						'compare' => 'EXISTS',
					),
				);
			}
			$users = get_users( $args );
			return $users;

		}
		
		return false;
		
	}
	
}

if ( !function_exists( 'issuem_leaky_paywall_logout_process' ) ) {
	
	/**
	 * Removes all cookies and session variables for Leaky Paywall subscriber
	 *
	 * @since 2.0.0
	 */
	function issuem_leaky_paywall_logout_process() {
		unset( $_SESSION['issuem_lp_hash'] );
		unset( $_SESSION['issuem_lp_email'] );
		unset( $_SESSION['issuem_lp_subscriber'] );
		setcookie( 'issuem_lp_subscriber', null, 0, '/' );
	}
	add_action( 'wp_logout', 'issuem_leaky_paywall_logout_process' ); //hook into the WP logout process too
}

if ( !function_exists( 'issuem_leaky_paywall_server_pdf_download' ) ) {

	function issuem_leaky_paywall_server_pdf_download( $download_id ) {
	    // Grab the download info
	    $url = wp_get_attachment_url( $download_id );
	    	
	    // Attempt to grab file
	    if ( $response = wp_remote_head( str_replace( ' ', '%20', $url ) ) ) {
	        if ( ! is_wp_error( $response ) ) {
	            $valid_response_codes = array(
	                200,
	            );
	            if ( in_array( wp_remote_retrieve_response_code( $response ), (array) $valid_response_codes ) ) {
		
	                // Get Resource Headers
	                $headers = wp_remote_retrieve_headers( $response );
	
	                // White list of headers to pass from original resource
	                $passthru_headers = array(
	                    'accept-ranges',
	                    'content-length',
	                    'content-type',
	                );
	
	                // Set Headers for download from original resource
	                foreach ( (array) $passthru_headers as $header ) {
	                    if ( isset( $headers[$header] ) )
	                        header( esc_attr( $header ) . ': ' . esc_attr( $headers[$header] ) );
	                }
	
	                // Set headers to force download
	                header( 'Content-Description: File Transfer' );
	                header( 'Content-Disposition: attachment; filename=' . basename( $url ) );
	                header( 'Content-Transfer-Encoding: binary' );
	                header( 'Expires: 0' );
	                header( 'Cache-Control: must-revalidate' );
	                header( 'Pragma: public' );
	
	                // Clear buffer
	                flush();
	
	                // Deliver the file: readfile, curl, redirect
	                if ( ini_get( 'allow_url_fopen' ) ) {
	                    // Use readfile if allow_url_fopen is on
	                    readfile( str_replace( ' ', '%20', $url )  );
	                } else if ( is_callable( 'curl_init' ) ) {
	                    // Use cURL if allow_url_fopen is off and curl is available
	                    $ch = curl_init( str_replace( ' ', '%20', $url ) );
	                    curl_exec( $ch );
	                    curl_close( $ch );
	                } else {
	                    // Just redirect to the file becuase their host <strike>sucks</strike> doesn't support allow_url_fopen or curl.
	                    wp_redirect( str_replace( ' ', '%20', $url ) );
	                }
	                die();
	
	            } else {
					$output = '<h3>' . __( 'Error Downloading PDF', 'issuem-leaky-paywall' ) . '</h3>';
		
					$output .= '<p>' . sprintf( __( 'Download Error: Invalid response: %s', 'issuem-leaky-paywall' ), wp_remote_retrieve_response_code( $response ) ) . '</p>';
					$output .= '<a href="' . get_home_url() . '">' . __( 'Home', 'issuem-leak-paywall' ) . '</a>';
	            	
		            wp_die( $output );
	            }
	        } else {
				$output = '<h3>' . __( 'Error Downloading PDF', 'issuem-leaky-paywall' ) . '</h3>';
	
				$output .= '<p>' . sprintf( __( 'Download Error: %s', 'issuem-leaky-paywall' ), $response->get_error_message() ) . '</p>';
				$output .= '<a href="' . get_home_url() . '">' . __( 'Home', 'issuem-leak-paywall' ) . '</a>';
            	
	            wp_die( $output );
	        }
	    }
	}
}

if ( !function_exists( 'wp_print_r' ) ) { 

	/**
	 * Helper function used for printing out debug information
	 *
	 * HT: Glenn Ansley @ iThemes.com
	 *
	 * @since 1.0.0
	 *
	 * @param int $args Arguments to pass to print_r
	 * @param bool $die TRUE to die else FALSE (default TRUE)
	 */
    function wp_print_r( $args, $die = true ) { 
	
        $echo = '<pre>' . print_r( $args, true ) . '</pre>';
		
        if ( $die ) die( $echo );
        	else echo $echo;
		
    }   
	
}

if ( !function_exists( 'issuem_leaky_paywall_jquery_datepicker_format' ) ) { 

	/**
	 * Pass a PHP date format string to this function to return its jQuery datepicker equivalent
	 *
	 * @since 1.1.0
	 * @param string $date_format PHP Date Format
	 * @return string jQuery datePicker Format
	*/
	function issuem_leaky_paywall_jquery_datepicker_format( $date_format ) {
		
		//http://us2.php.net/manual/en/function.date.php
		//http://api.jqueryui.com/datepicker/#utility-formatDate
		$php_format = array(
			//day
			'/d/', //Day of the month, 2 digits with leading zeros
			'/D/', //A textual representation of a day, three letters
			'/j/', //Day of the month without leading zeros
			'/l/', //A full textual representation of the day of the week
			//'/N/', //ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
			//'/S/', //English ordinal suffix for the day of the month, 2 characters
			//'/w/', //Numeric representation of the day of the week
			'/z/', //The day of the year (starting from 0)
			
			//week
			//'/W/', //ISO-8601 week number of year, weeks starting on Monday (added in PHP 4.1.0)
			
			//month
			'/F/', //A full textual representation of a month, such as January or March
			'/m/', //Numeric representation of a month, with leading zeros
			'/M/', //A short textual representation of a month, three letters
			'/n/', //numeric month no leading zeros
			//'t/', //Number of days in the given month
			
			//year
			//'/L/', //Whether it's a leap year
			//'/o/', //ISO-8601 year number. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead. (added in PHP 5.1.0)
			'/Y/', //A full numeric representation of a year, 4 digits
			'/y/', //A two digit representation of a year
		);
		
		$datepicker_format = array(
			//day
			'dd', //day of month (two digit)
			'D',  //day name short
			'd',  //day of month (no leading zero)
			'DD', //day name long
			//'',   //N - Equivalent does not exist in datePicker
			//'',   //S - Equivalent does not exist in datePicker
			//'',   //w - Equivalent does not exist in datePicker
			'z' => 'o',  //The day of the year (starting from 0)
			
			//week
			//'',   //W - Equivalent does not exist in datePicker
			
			//month
			'MM', //month name long
			'mm', //month of year (two digit)
			'M',  //month name short
			'm',  //month of year (no leading zero)
			//'',   //t - Equivalent does not exist in datePicker
			
			//year
			//'',   //L - Equivalent does not exist in datePicker
			//'',   //o - Equivalent does not exist in datePicker
			'yy', //year (four digit)
			'y',  //month name long
		);
		
		return preg_replace( $php_format, $datepicker_format, preg_quote( $date_format ) );
	}
	
}
