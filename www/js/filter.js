/*
Given a search string search inside the list of inventory items and if there is a match call callbackShow, call callbackHide otherwise
*/


var FilterSearch = (function(){
	function filterAndSort(text, callbackShow, callbackHide) {
		// For each word remove tildes and lower the case
		var search = text.trim().latinize().toLowerCase().split(" ");
		UnShowAndReturn(lista, search, callbackShow, callbackHide);
	}
	
	// Recursively find matches. callbackShow and callbackHide receive the dom element that should be hidden/showed if there is a match
	function UnShowAndReturn(json, search, callbackShow, callbackHide) {
		var somethingFound = false;
		for (var i = 0; i < json.length; i++) {
			var lastLevel = undefined === json[i].contenido;
			var somethingFoundInside = !lastLevel ? UnShowAndReturn(json[i].contenido, search, callbackShow, callbackHide) : false;
			var hereFound = false;
			if (lastLevel) {
				hereFound = hereFound || Test(search, json[i].Nombre);
			}
			if (undefined !== json[i].Tags) {
				for (var j = 0; !hereFound && j < json[i].Tags.length; j++) {
					hereFound = hereFound || Test(search, json[i].Tags[j]);
				}
			}
			somethingFound = somethingFound || somethingFoundInside || hereFound;
			
			// Execute callbacks
			var callback = somethingFoundInside || hereFound ? callbackShow : callbackHide;
			callback(json[i].DOM);
		}
		return somethingFound;
	}
	
	function Test(search, text) {
		text = text.latinize().toLowerCase();
		for (var i = 0; i < search.length; i++) {
			if (text.indexOf(search[i]) != -1) {
				return true;
			}
		}
		
		return false;
	}
	
	
	
	// API
	return {
		process: filterAndSort
	};
})();
