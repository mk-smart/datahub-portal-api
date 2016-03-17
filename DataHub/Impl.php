<?php

namespace DataHub\Impl;

use \MKSDC_DataAccess as DataAccess;
use \WP_Query as WP_query;

// XXX We need to remove the filter as user is not authenticated in the portal!
remove_filter ( 'the_posts', 'populate_posts_data' );

function getDatasets($key, $fields = array()) {
	global $wpdb;
	// Get User
	// $user = \DataHub\Bindings\getUserFromKey($key);
	// Get all datasets UUIDs (We need to be fast, this is why we query the DB)
	$Q = "SELECT wp_postmeta.post_id,wp_postmeta.meta_value FROM wp_postmeta
INNER JOIN wp_posts ON (wp_postmeta.post_id = wp_posts.ID) AND wp_posts.post_type = 'mksdc-datasets'
WHERE wp_postmeta.meta_key = '_mksdc_uuid_key'";
	$uuids = $wpdb->get_results ( $Q, OBJECT_K );
	array_walk ( $uuids, function (&$item, $ix) {
		$item = $item->meta_value;
	} );
	// Setup Authorization Object 
	$authorization = \DataHub\Bindings\getAuthorization ( $key, array_values ( $uuids ) ); // \MKSP_Authorization::getInstance ( $user, array_values ( $uuids ) );
	$args = array (
			'post_type' => 'mksdc-datasets',
			'post_status' => 'publish',
			'posts_per_page' => - 1,
			'ignore_sticky_posts' => 1 
	);
	
	$dc = DataAccess::instance ();
	$my_query = new WP_Query ( $args );
	$return = array ();
	while ( $my_query->have_posts () ) {
		$my_query->the_post ();
		$i = get_the_ID ();
		if (isset ( $uuids [$i] ) && $authorization->canView ( $uuids [$i] )) {
			$item = array ();
			$item['id'] = $uuids [$i];
					
			foreach ( $fields as $field ) {
				$skip = FALSE;
				$v = NULL;
				switch ($field) :
					case 'name' :
						$v = get_the_title ();
						break;
					case 'type' :
						$v = \DataHub\Bindings\getDatasetType ( $i );
						break;
					case 'apiUrl' :
						$v = get_site_url () . '/api/dataset/' . $uuids [$i];
						break;
					case 'categories' :
						$c = get_the_category ( $i );
						array_walk ( $c, function (&$item) {
							$item = $item->name;
						} );
						$v = $c;
						break;
					case 'tags' :
						$t = get_the_tags ( $i );
						array_walk ( $t, function (&$item) {
							$item = $item->name;
						} );
						$v = array_values ( $t );
						break;
					case 'formats' :
						$f = wp_get_post_terms ( $i, array (
								'mksdc-dataformats' 
						), array (
								'fields' => 'names' 
						) );
						$v = $f;
						break;
					case 'ownership' :
						$o = wp_get_post_terms ( $i, array (
								'mksdc-dataowners' 
						), array (
								'fields' => 'names' 
						) );
						$v = $o;
						break;
					case 'statement' :
						$v = $dc->readCustomField ( $i, 'attribution' );
						break;
					case 'licenses' :
						$llp = $dc->getLicensesOfDatasetByPostId ( $i );
						$ll = array ();
						foreach ( $llp as $lk => $lv ) {
							$ll [] = get_post ( $lk )->post_name;
						}
						$v = $ll;
						break;
					case 'lastModified' :
						$v = get_the_modified_time ( 'U' );
						break;
					case 'created' :
						$v = get_the_time ( 'U' );
						break;
					case 'pageUrl' :
						$v = get_permalink ( $i );
						break;
					default :
						$skip = TRUE;
				endswitch
				;
				if (! $skip) {
					$item [$field] = $v;
				}
			}
			$return [] = $item;
		}
	}
	wp_reset_postdata ();
	return $return;
}
function getPostIdByUUID($id){
	global $wpdb;
	$Q = "SELECT POST_ID FROM WP_POSTMETA WHERE META_KEY='_mksdc_uuid_key' AND META_VALUE=%s";
	$results = $wpdb->get_results ( $wpdb->prepare($Q, $id), OBJECT_K );
	$postId = array_pop($results)->POST_ID;
	return $postId;
}

function getDataset($key, $id) {
	// Check whether User can see $id
	$authorization = \DataHub\Bindings\getAuthorization($key, array($id));
	if ($authorization->canView ( $id )) {
		$dc = DataAccess::instance ();
		$i = getPostIdByUUID ( $id );
		$return = array ();
		$return ['id'] = $id;
		$return ['type'] = mksis_getDatasetType ( $i );
		$return ['lastModified'] = get_post_modified_time ( 'U', false, $i );
		$return ['created'] = get_post_time ( 'U', false, $i );
		$return ['pageUrl'] = get_permalink ( $i );
		$return ['apiUrl'] = get_site_url () . '/api/dataset/' . $id;
		$return ['info'] = get_site_url () . '/api/dataset/' . $id;
		$return ['access'] = get_site_url () . '/api/dataset/' . $id;
		if ($return ['type'] == 'temporal') {
			$return ['feed'] = get_site_url () . '/api/dataset/' . $id . '/feed';
		} else {
			$return ['uploads'] = get_site_url () . '/api/dataset/' . $id . '/uploads';
			$return ['urls'] = get_site_url () . '/api/dataset/' . $id . '/urls';
		}
		return $return;
	} else {
		return FALSE;
	}
}

function getDatasetInfo($key, $id) {
	// Check whether User can see $id
	$authorization = \DataHub\Bindings\getAuthorization($key, array($id));
	if ($authorization->canView ( $id )) {
		$i = getPostIdByUUID($id);
		$return = array ();

		// INFO
		$return ['info'] = array ();
		$return ['info'] ['name'] = get_the_title ( $i );
		$c = get_the_category ( $i );
		@array_walk ( $c, function (&$item) {
			$item = $item->name;
		} );
		$return ['info'] ['categories'] = $c;
		$t = get_the_tags ( $i );
		@array_walk ( $t, function (&$item) {
			$item = $item->name;
		} );
		$return ['info'] ['tags'] = (is_array($t)) ? array_values ( $t ) : [];
		$return ['info'] ['formats'] = @wp_get_post_terms ( $i, array (
				'mksdc-dataformats'
		), array (
				'fields' => 'names'
		) );
		$return ['info'] ['ownership'] = @wp_get_post_terms ( $i, array (
				'mksdc-dataowners'
		), array (
				'fields' => 'names'
		) );
		$dc = DataAccess::instance();
		$return ['info'] ['sourceWebsite'] = $dc->readCustomField ( $i, 'link_to_source' );
		$return ['info'] ['sourceName'] = $dc->readCustomField ( $i, 'link_to_source_desc' );
		$return ['info'] ['statement'] = $dc->readCustomField ( $i, 'attribution' );
		$llp = $dc->getLicensesOfDatasetByPostId ( $i );
		$ll = array ();
		foreach ( $llp as $lk => $lv ) {
			$ll [] = @get_post ( $lv['id'] )->post_name;
		}
		$return ['info'] ['licenses'] = $ll;
		return $return ['info'];
	} else {
		return FALSE;
	}
}

function getDatasetAccess($key, $id) {
	$authorization = \DataHub\Bindings\getAuthorization($key, array($id));
	
  if ($authorization->canModify ( $id )) {
		// Chech whether can modify?
		$policy = \DataHub\Bindings\getPolicy ( $id );
		$policy = json_decode ( $policy, true );
		return $policy;
	} else {
		return FALSE;
	}
}
