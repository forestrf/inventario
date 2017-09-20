/*
Given a search string search inside the list of inventory items and if there is a match call callbackShow, call callbackHide otherwise
*/


var FilterSearch = (function(){
	function filterAndSort(text, lista, callbackShow, callbackHide) {
		// For each word remove tildes and lower the case
		var search = text.trim().latinize().toLowerCase().split(" ");
		RecursiveSearch(lista, search, callbackShow, callbackHide);
	}
	
	var levels = ["secciones", "objetos"];
	
	// Recursively find matches. callbackShow and callbackHide receive the dom element that should be hidden/showed if there is a match. Returns true if there is a match
	function RecursiveSearch(json, search, callbackShow, callbackHide, lvl) {
		if (lvl === undefined) lvl = 0;
		var somethingFound = false;
		for (var i = 0; i < json.length; i++) {
			var lastLevel = undefined === json[i][levels[lvl]];
			var somethingFoundInside = !lastLevel ? RecursiveSearch(json[i][levels[lvl]], search, callbackShow, callbackHide, lvl + 1) : false;
			var hereFound = lastLevel && Test(search, json[i]["nombre"]);
			if (undefined !== json[i]["tags"]) {
				for (var j = 0; !hereFound && j < json[i]["tags"].length; j++) {
					hereFound = hereFound || Test(search, json[i]["tags"][j]);
				}
			}
			somethingFound = somethingFound || somethingFoundInside || hereFound;
			
			// Execute callbacks
			var callback = somethingFoundInside || hereFound ? callbackShow : callbackHide;
			callback(json[i]["DOM"]);
		}
		return somethingFound;
	}
	
	// True if text contains all of the strings in the search array. False otherwise
	function Test(search, text) {
		text = text.latinize().toLowerCase();
		for (var i = 0; i < search.length; i++)
			if (text.indexOf(search[i]) == -1)
				return false;
		
		return true;
	}
	
	
	
	// API
	return {
		process: filterAndSort
	};
})();
