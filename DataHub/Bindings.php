<?php
namespace DataHub\Bindings;

function getUserFromKey($key){
	return apply_filters('datahub_api_getUserFromKey', $key);
}
function getDatasetType($uuid){
	return apply_filters('datahub_api_getDatasetType', $uuid);
}
function getAuthorization($key, $uuids){
	return apply_filters('datahub_api_getAuthorization', $key, $uuids);
}
function getPolicy($uuid){
	return apply_filters('datahub_api_getPolicy', $uuid);
}
function getDatasetFeed($key, $uuid){
	return apply_filters('datahub_api_dataset_feed', $key, $uuid);
}