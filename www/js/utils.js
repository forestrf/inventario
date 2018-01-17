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
		finishNow: function(key) {
			if (list.containsKey(key)) {
				list.get(key).func();
				this.del(key);
				return true;
			}
			return false;
		},
		del: function(key) {
			if (list.containsKey(key)) {
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

function shallowClone(variable) {
	switch (typeof variable) {
		case "object":
			if (variable instanceof Array) {
				var clon = [];
				for (var i in variable) {
					clon[i] = shallowClone(variable[i]);
				}
				return clon;
			} else {
				if (isElement(variable)) {
					return variable;
				} else {
					var clon = {};
					for (var i in variable) {
						clon[i] = shallowClone(variable[i]);
					}
					return clon;
				}
			}
		case "function":
		case "undefined":
		case "boolean":
		case "number":
		case "string":
			return variable;
	}
}

// https://stackoverflow.com/questions/384286/javascript-isdom-how-do-you-check-if-a-javascript-object-is-a-dom-object
function isElement(obj) {
	try {
		//Using W3 DOM2 (works for FF, Opera and Chrome)
		return obj instanceof HTMLElement;
	}
	catch(e) {
		//Browsers not supporting W3 DOM2 don't have HTMLElement and
		//an exception is thrown and we end up here. Testing some
		//properties that all elements have (works on IE7)
		return (typeof obj==="object") &&
			(obj.nodeType===1) && (typeof obj.style === "object") &&
			(typeof obj.ownerDocument ==="object");
	}
}	

var id_generator = (function(c) {
	return function() { return c++; };
})(0);

function onlyUnique(value, index, self) { 
	return self.indexOf(value) === index;
}
