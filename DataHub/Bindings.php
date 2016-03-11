<?php
namespace DataHub\Bindings;

function datasets($key, $fields = array('id','type','apiUrl')){
	return apply_filters('datahub_api_datasets', $key, $fields);
}

function dataset($key, $id){
	return apply_filters('datahub_api_dataset', $key, $id);
}

function datasetInfo($key, $id){
	return apply_filters('datahub_api_dataset_info', $key, $id);
}

function datasetAccess($key, $id){
	return apply_filters('datahub_api_dataset_access', $key, $id);
}


function datasetFeed($key, $id){
	return apply_filters('datahub_api_dataset_feed', $key, $id);
}