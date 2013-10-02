<?php
	//Searching for places named after a certain person, for example 
	// Van Gogh

	require('lib/GooglePlacesSearch.php');

	// Keep in mind that the coordinates provided in the input file should be set as google.maps.LatLng objects
	$van_gogh_search = new GooglePlacesSearch('AIzaSyDFdGgXfnlFQZBwght6Ho_3jKudlTWkZkY', 'gogh');
	
	$van_gogh_search->Search();