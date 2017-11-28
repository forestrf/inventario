/*
Given a search string search inside the list of inventory items and if there is a match call callbackShow, call callbackHide otherwise
*/

if (typeof String.prototype.trim !== 'function') {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, ''); 
	}
}

var FilterSearch = (function(){
	function filterAndSort(text, lista, TestCustomKeyword, callbackShow, callbackHide) {
		// For each word remove tildes and lower the case
		var search = text.trim().latinize().toLowerCase().split(" ");
		Search(lista, search, TestCustomKeyword, callbackShow, callbackHide);
	}
	
	// Recursively find matches. callbackShow and callbackHide receive the dom element that should be hidden/showed if there is a match. Returns true if there is a match
	function Search(lista, search, TestCustomKeyword, callbackShow, callbackHide) {
		var objetos = lista.objetos;
		
		for (var i in objetos) {
			var found = false;
			for (var j = 0; !found && j < search.length; j++)
				found = TestCustomKeyword(search[j], objetos[i]);
			if (!found)
				found = Test(search, objetos[i].nombre);
			for (var j = 0; !found && j < objetos[i].tags.length; j++)
				found = Test(search, objetos[i].tags[j]);
			
			// Execute callbacks
			var callback = found ? callbackShow : callbackHide;
			callback(objetos[i].DOM);
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
