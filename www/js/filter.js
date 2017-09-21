/*
Given a search string search inside the list of inventory items and if there is a match call callbackShow, call callbackHide otherwise
*/


var FilterSearch = (function(){
	function filterAndSort(text, lista, callbackShow, callbackHide) {
		// For each word remove tildes and lower the case
		var search = text.trim().latinize().toLowerCase().split(" ");
		Search(lista, search, callbackShow, callbackHide);
	}
	
	// Recursively find matches. callbackShow and callbackHide receive the dom element that should be hidden/showed if there is a match. Returns true if there is a match
	function Search(lista, search, callbackShow, callbackHide) {
		var objetos = lista.objetos;
		var hitSecciones = [], hitAlmacenes = [];
		
		for (var i = 0; i < objetos.length; i++) {
			var hereFound = Test(search, objetos[i]["nombre"]);
			if (undefined !== objetos[i]["tags"]) {
				for (var j = 0; !hereFound && j < objetos[i]["tags"].length; j++) {
					hereFound = hereFound || Test(search, objetos[i]["tags"][j]);
				}
			}
			
			if (hereFound) {
				hitSecciones.push(objetos[i]["secciones"]);
				//hitAlmacenes TO DO;
			}
			
			// Execute callbacks
			var callback = hereFound ? callbackShow : callbackHide;
			for (var j in objetos[i]["secciones"])
				callback(objetos[i]["secciones"][j]["DOM"]);
		}
		
		
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
