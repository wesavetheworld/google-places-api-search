<?php
	//Searching for places named after a certain person, for example 
	// Van Gogh
	// Please provise your Google Places API key when instantiating a new instance of GooglePlacesSearch object

	require('lib/GooglePlacesSearch.php');

	// Keep in mind that the coordinates provided in the input file should be set as google.maps.LatLng objects
	$van_gogh_search = new GooglePlacesSearch('your_API', 'gogh');
	
	$van_gogh_search->Search();
