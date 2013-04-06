<?php
	require('lib/GooglePlacesSearch.php');

	// Keep in mind that the coordinates provided in the input file should be set as google.maps.LatLng objects
	$gandi = new GooglePlacesSearch('insert_your_google_places_api_key', 'gandi');
	$gandi->Search();