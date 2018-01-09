if (typeof String.prototype.trim !== 'function') {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, ''); 
	}
}

var timeouts = (function(list) {
	return {
		add: function(func, milliseconds) {
			var key = window.setTimeout(function() {
				delete list[key];
				func();
			}, milliseconds);
			
			list.put(key, {
				timeoutId: key,
				func: func
			});
			
			return key;
		},
		addWithKey: function(key, func, milliseconds) {
			list.put(key, {
				timeoutId: -1,
				func: func
			});
			
			list.get(key).timeoutId = window.setTimeout(function() {
				list.remove(key);
				func();
			}, milliseconds);
			
			return key;
		},
		get: function(key) {
			return list.containsKey(key) ? list.get(key).func : null;
		},
		del: function(key) {
			if(list.containsKey(key)) {
				window.clearTimeout(list.get(key).timeoutId);
				list.remove(key);
			}
		}
	};
})(new Hashtable());

function AddClass(dom, className) {
	var clases = dom.className.split(" ");
	clases.push(className);
	dom.className = clases.filter(onlyUnique).join(" ");
}
function RemoveClass(dom, className) {
	dom.className = dom.className.split(" ").filter(function(c) { return c != className; }).join(" ");
}

function shallowClone(objeto) {
	var clon = {};
	for (var i in objeto) clon[i] = objeto[i];
	return clon;
}

var id_generator = (function(c) {
	return function() { return c++; };
})(0);

function onlyUnique(value, index, self) { 
	return self.indexOf(value) === index;
}
