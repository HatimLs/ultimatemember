<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Members' ) ) {


	/**
	 * Class Members
	 * @package um\core
	 */
	class Members {


		/**
		 * @var
		 */
		var $results;


		/**
		 * Members constructor.
		 */
		function __construct() {

			add_filter( 'user_search_columns', array( &$this, 'user_search_columns' ), 99 );
			add_action( 'template_redirect', array( &$this, 'access_members' ), 555 );

			$this->core_search_fields = array(
				'user_login',
				'user_url',
				'display_name',
				'user_email',
				'user_nicename',
			);

			add_filter( 'um_search_select_fields', array( &$this, 'um_search_select_fields' ), 10, 1 );

		}


		/**
		 * Show filter
		 *
		 * @todo make UM:Groups members list via general directory
		 *
		 * @deprecated since 2.1.0 - Use only in UM Groups extension
		 * @param $filter
		 */
		function show_filter( $filter ) {
			/**
			 * @var $type
			 * @var $attrs
			 */
			extract( $this->prepare_filter( $filter ) );

			switch ( $type ) {

				case 'select':
					/*if( isset($attrs) && is_array( $attrs['options'] ) ){
						asort( $attrs['options'] );
					}*/
					if ( isset( $attrs['label'] ) ) {
						$label = $attrs['label'];
					} else {
						$label = isset( $attrs['title'] ) ? $attrs['title'] : '';
					} ?>

					<select name="<?php echo esc_attr( $filter ); ?>" id="<?php echo esc_attr( $filter ); ?>" class="um-s1"
					        style="width: 100%" data-placeholder="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>" <?php if ( ! empty( $attrs['custom_dropdown_options_source'] ) ) { ?> data-um-parent="<?php echo esc_attr( $attrs['parent_dropdown_relationship'] ); ?>" data-mebers-directory="yes"  data-um-ajax-source="<?php echo esc_attr( $attrs['custom_dropdown_options_source'] ) ?>"<?php } ?>>

						<option></option>

						<?php foreach ( $attrs['options'] as $k => $v ) {

							$v = stripslashes( $v );

							$opt = $v;

							if ( strstr( $filter, 'role_' ) ) {
								$opt = $k;
							}

							if ( isset( $attrs['custom'] ) ) {
								$opt = $k;
							} ?>

							<option value="<?php echo esc_attr( $opt ); ?>" <?php um_select_if_in_query_params( $filter, $opt ); ?> <?php selected( isset( $_GET[ $filter ] ) && $_GET[ $filter ] == $v ) ?>><?php esc_html_e( $v, 'ultimate-member'); ?></option>

						<?php } ?>

					</select>

					<?php

					break;

				case 'text':
					?>

					<input type="text" autocomplete="off" name="<?php echo esc_attr( $filter ); ?>" id="<?php echo esc_attr( $filter ); ?>"
					       placeholder="<?php echo isset( $attrs['label'] ) ? esc_attr__( $attrs['label'], 'ultimate-member' ) : ''; ?>"
					       value="<?php echo esc_attr( um_queried_search_value(  $filter, false ) ); ?>" />

					<?php
					break;

			}

		}


		/**
		 * User_search_columns
		 *
		 * @param $search_columns
		 *
		 * @return array
		 */
		function user_search_columns( $search_columns ) {
			if ( is_admin() ) {
				$search_columns[] = 'display_name';
			}
			return $search_columns;
		}


		/**
		 * Members page allowed?
		 */
		function access_members() {
			if ( UM()->options()->get( 'members_page' ) == 0 && um_is_core_page( 'members' ) ) {
				um_redirect_home();
			}
		}


		/**
		 * Tag conversion for member directory
		 *
		 * @param $string
		 * @param $array
		 *
		 * @return mixed
		 */
		function convert_tags( $string, $array ) {

			$search = array(
				'{total_users}',
			);

			$replace = array(
				$array['total_users'],
			);

			$string = str_replace( $search, $replace, $string );
			return $string;
		}

		/**
		 * Prepare filter data
		 *
		 * @param $filter
		 * @return array
		 */
		function prepare_filter( $filter ) {
			$fields = UM()->builtin()->all_user_fields;

			if ( isset( $fields[ $filter ] ) ) {
				$attrs = $fields[ $filter ];
			} else {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_search_field_{$filter}
				 * @description Custom search settings by $filter
				 * @input_vars
				 * [{"var":"$settings","type":"array","desc":"Search Settings"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_custom_search_field_{$filter}', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_custom_search_field_{$filter}', 'my_custom_search_field', 10, 1 );
				 * function my_change_email_template_file( $settings ) {
				 *     // your code here
				 *     return $settings;
				 * }
				 * ?>
				 */
				$attrs = apply_filters( "um_custom_search_field_{$filter}", array() );
			}

			// additional filter for search field attributes
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_search_field_{$filter}
			 * @description Extend search settings by $filter
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"Search Settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_search_field_{$filter}', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_search_field_{$filter}', 'my_search_field', 10, 1 );
			 * function my_change_email_template_file( $settings ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			$attrs = apply_filters( "um_search_field_{$filter}", $attrs );

			$type = UM()->builtin()->is_dropdown_field( $filter, $attrs ) ? 'select' : 'text';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_search_field_type
			 * @description Change search field type
			 * @input_vars
			 * [{"var":"$type","type":"string","desc":"Search field type"},
			 * {"var":"$settings","type":"array","desc":"Search Settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_search_field_type', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_search_field_type', 'my_search_field_type', 10, 2 );
			 * function my_search_field_type( $type, $settings ) {
			 *     // your code here
			 *     return $type;
			 * }
			 * ?>
			 */
			$type = apply_filters( 'um_search_field_type', $type, $attrs );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_search_fields
			 * @description Filter all search fields
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"Search Fields"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_search_fields', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_search_fields', 'my_search_fields', 10, 1 );
			 * function my_search_fields( $settings ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			$attrs = apply_filters( 'um_search_fields', $attrs );

			if ( $type == 'select' ) {
				if ( isset( $attrs ) && is_array( $attrs['options'] ) ) {
					asort( $attrs['options'] );
				}
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_search_select_fields
				 * @description Filter all search fields for select field type
				 * @input_vars
				 * [{"var":"$settings","type":"array","desc":"Search Fields"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_search_select_fields', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_search_select_fields', 'my_search_select_fields', 10, 1 );
				 * function my_search_select_fields( $settings ) {
				 *     // your code here
				 *     return $settings;
				 * }
				 * ?>
				 */
				$attrs = apply_filters( 'um_search_select_fields', $attrs );
			}

			return compact( 'type', 'attrs' );
		}


		/**
		 * Display assigned roles in search filter 'role' field
		 * @param  	array $attrs
		 * @return 	array
		 * @uses  	add_filter 'um_search_select_fields'
		 * @since 	1.3.83
		 */
		function um_search_select_fields( $attrs ) {

			if ( ! empty( $attrs['metakey'] ) && strstr( $attrs['metakey'], 'role_' ) ) {

				$shortcode_roles = get_post_meta( UM()->shortcodes()->form_id, '_um_roles', true );
				$um_roles = UM()->roles()->get_roles( false );

				if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {

					$attrs['options'] = array();

					foreach ( $um_roles as $key => $value ) {
						if ( in_array( $key, $shortcode_roles ) ) {
							$attrs['options'][ $key ] = $value;
						}
					}

				}

			}

			return $attrs;
		}


		/**
		 * Optimizes Member directory with multiple LEFT JOINs
		 * @param  object $vars
		 * @return object $var
		 */
		public function um_optimize_member_query( $vars ) {

			global $wpdb;

			$arr_where = explode("\n", $vars->query_where );
			$arr_left_join = explode("LEFT JOIN", $vars->query_from );
			$arr_user_photo_key = array( 'synced_profile_photo', 'profile_photo', 'synced_gravatar_hashed_id' );

			foreach ( $arr_where as $where ) {

				foreach ( $arr_user_photo_key as $key ) {

					if ( strpos( $where, "'" . $key . "'" ) > -1 ) {

						// find usermeta key
						preg_match("#mt[0-9]+.#",  $where, $meta_key );

						// remove period from found meta_key
						$meta_key = str_replace(".","", current( $meta_key ) );

						// remove matched LEFT JOIN clause
						$vars->query_from = str_replace('LEFT JOIN wp_usermeta AS '.$meta_key.' ON ( wp_users.ID = '.$meta_key.'.user_id )', '',  $vars->query_from );

						// prepare EXISTS replacement for LEFT JOIN clauses
						$where_exists = 'um_exist EXISTS( SELECT '.$wpdb->usermeta.'.umeta_id FROM '.$wpdb->usermeta.' WHERE '.$wpdb->usermeta.'.user_id = '.$wpdb->users.'.ID AND '.$wpdb->usermeta.'.meta_key IN("'.implode('","',  $arr_user_photo_key ).'") AND '.$wpdb->usermeta.'.meta_value != "" )';

						// Replace LEFT JOIN clauses with EXISTS and remove duplicates
						if ( strpos( $vars->query_where, 'um_exist' ) === FALSE ) {
							$vars->query_where = str_replace( $where , $where_exists,  $vars->query_where );
						} else {
							$vars->query_where = str_replace( $where , '1=0',  $vars->query_where );
						}
					}

				}

			}

			$vars->query_where = str_replace( "\n", "", $vars->query_where );
			$vars->query_where = str_replace( "um_exist", "", $vars->query_where );

			return $vars;

		}
	}
}