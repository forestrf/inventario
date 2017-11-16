<html>
<head>
<link rel="stylesheet" type="text/css" href="css/main.css"> 

<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script src="js/ajax.js"></script>
<script src="js/crel2.js"></script>

<!-- Tokenfield -->
<script type="text/javascript" src="js/libs/jquery/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/libs/jquery/jquery-ui-1.10.3.min.js"></script>
<script type="text/javascript" src="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.js"></script>
<link href="js/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="js/libs/jquery/themes/smoothness/jquery-ui.min.css" type="text/css" rel="stylesheet">
<link href="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.min.css" type="text/css" rel="stylesheet">
</head>
<body>



<div class="buscador">
	Búsqueda: <input id="buscador" type="text" placeholder="Búsqueda" class="form-control"/>
</div>

<div id="inventario"></div>
<div class="clearer"></div>

<button>+ Objeto</button>




<script>

var tagsArrayAutocomplete = [];

var lista;

AJAX('php/ajax.php?action=getinventario', null, function(x) {
	lista = JSON.parse(x.responseText);
	
	var objetosById = {};
	for (var i in lista.objetos) objetosById[lista.objetos[i].id] = lista.objetos[i];
	lista.objetos = objetosById;
	
	FixTags(lista.objetos);
	
	// Dibujar toda la lista en el DOM
	C(document.getElementById("inventario"), DrawInventory(lista));
	
	tagsArrayAutocomplete = GetAutocompleteTags(lista.objetos);
	
	// Preparar buscador
	var buscador = document.getElementById("buscador");
	buscador.onkeyup = function() {
		FilterSearch.process(buscador.value, lista,
			function(DOM) { DOM.style.display = "unset"; },
			function(DOM) { DOM.style.display = "none"; }
		);
	};
	
}, console.log);


function FixTags(objetos) {
	for (var i in objetos) {
		objetos[i].tags = objetos[i].tags.split(",");
	}
}


var popups = [];
function closePopup() {
	var dom = popups.pop();
	document.body.removeChild(dom);
}
function showPopup(contentsDOM) {
	var dom = C("div", ["class", "popup"],
		C("div", ["class", "bg", "onclick", closePopup]),
		C("div", ["class", "msg"], contentsDOM)
	);
	document.body.appendChild(dom);
	popups.push(dom);
}



function GetAutocompleteTags(objetos) {
	var arr = [];
	for (var i in objetos) {
		arr = arr.concat(objetos[i].tags.filter(function(x){ return arr.indexOf(x) === -1; }));
	}
	return arr
}



function DrawInventory(lista) {
	var contenedor = C("div");
	
	for (var i in lista.objetos) {
		C(contenedor, DrawObjeto(lista.objetos[i]));
	}
	
	return contenedor;
}

function DrawObjeto(objeto) {
	var cantidad = GetCantidad(objeto);
	var tags;
	objeto["DOM"] = C("button", ["class", "objeto obj-" + objeto.id, "onclick", edit],
		C("div", ["class", "titulo"],
			C("div", ["class", "nombre"], objeto["nombre"])
		),
		C("div", ["class", "img-container"],
			C("span", ["class", "helper"]),
			C("img", ["class", "img img-" + objeto.id, "src", GetImagenObjeto(objeto)])
		),
		C("div", ["class", "info"],
			C("div", ["class", "cantidad"], "Cantidad: ", cantidad),
			C("div", ["class", "minimo"], "Mínimo: ", objeto["minimo_alerta"]),
			C("div", ["class", "tags"], "Tags: ", tags = C("span", ["class", "tags-list"]))
		)
	);
	var tagsArr = objeto["tags"] = objeto["tags"].filter(function(x){return x !== ""});
	for (var i in tagsArr) C(tags, C("span", tagsArr[i]));
	
	objeto["DOM"]["objeto"] = objeto;
	var cb = cantidad < parseInt(objeto["minimo_alerta"]) ? AddClass : RemoveClass;
	cb(objeto["DOM"], "alerta");
	
	return objeto["DOM"];

	
	
	function edit() {
		var objetoLocal = cloneObjecto(objeto)
		cantidad = GetCantidad(objetoLocal);
		var cantidadROInput = C("input", ["type", "text", "value", cantidad, "class", "form-control", "readonly", 1], cantidad);
		var tags;
		var cantidades;
		var popupDOM = C("div",
			C("div", ["style", "padding: 1%"],
				C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php", "onsubmit", update],
					C("div", "Nombre"),
					C("div", C("input", ["name", "nombre", "type", "text", "value", objetoLocal["nombre"], "class", "form-control", "onkeyup", compruebaCambios])),
					C("input", ["type", "submit"]),
					C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
					C("input", ["type", "hidden", "name", "action", "value", "update-object-name"])
				),
				C("form", ["class", "right_big", "method", "post", "action", "php/ajax.php", "onsubmit", update],
					C("div", "Imagen"),
					C("div",
						C("img", ["src", GetImagenObjeto(objetoLocal), "id", "img_objeto", "class", "img-" + objetoLocal.id]),
						C("input", ["name", "imagen", "type", "file", "accept", "image/*", "capture", "camera", "onchange", compruebaCambios])
					), 
					C("input", ["type", "submit"]),
					C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
					C("input", ["type", "hidden", "name", "action", "value", "update-object-image"])
				),
				C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php", "onsubmit", update],
					C("div", "Cantidad mínima"),
					C("div", C("input", ["name", "minimo", "type", "text", "value", objetoLocal["minimo_alerta"], "class", "form-control", "onchange", onMinimoChange, "onkeyup", compruebaCambios])),
					C("input", ["type", "submit"]),
					C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
					C("input", ["type", "hidden", "name", "action", "value", "update-object-minimo"])
				),
				C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php", "onsubmit", update],
					C("div", "Cantidad"),
					C("div", C("div", ["class", "cantidades"],
						cantidades = C("div"), 
						C("div", ["class", "btn btn-primary add", "onclick", function(){
							objetoLocal.secciones[objetoLocal.secciones.length] = {cantidad: 0, id_seccion: Object.keys(lista.secciones)[0]};
							C(cantidades, DrawCantidadInput(objetoLocal["secciones"][objetoLocal.secciones.length - 1]));
							return false;
						}], "+ Añadir a otro lugar"),
						C("span", C("span", "Total:"), cantidadROInput)
					)),
					C("input", ["type", "submit"]),
					C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
					C("input", ["type", "hidden", "name", "action", "value", "update-object-cantidades"])
				),
				C("form", ["class", "right_big", "method", "post", "action", "php/ajax.php", "onsubmit", update],
					C("div", "Tags"),
					C("div", tags = C("input", ["name", "", "type", "text", "value", objetoLocal.tags, "class", "form-control", "onchange", compruebaCambios])),
					C("input", ["type", "submit"]),
					C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
					C("input", ["type", "hidden", "name", "action", "value", "update-object-tags"])
				),
				C("div", ["class", "clear"])
			),
			C("div", ["class", "botonesAceptarCancelar"],
				C("input", ["type", "button", "class", "btn btn-success aceptar", "value", "Guardar cambios", "onclick", guardarCambios]),
				C("input", ["type", "button", "class", "btn btn-default cancelar", "value", "Cancelar", "onclick", closePopup]),
				C("div", ["style", "text-align: left; display: none;"], "ID: ", objetoLocal.id)
			)
		);
		
		for (var i = 0; i < objetoLocal["secciones"].length; i++) {
			C(cantidades, DrawCantidadInput(objetoLocal["secciones"][i]));
		}
		var arr = popupDOM.querySelectorAll("input[type=text]");
		for (var i = 0; i < arr.length; i++) {
			if (arr[i].originalValue === undefined)
				arr[i].originalValue = arr[i].value;
		}
		
		var forms = popupDOM.querySelectorAll("form");
		for (var i = 0; i < forms.length; i++) {
			var f = forms[i];
			f.submitter = f.querySelector("input[type=submit]");
			f.submitter.style = "display: none";
		}
		
		$(tags).tokenfield({
			autocomplete: {
				source: tagsArrayAutocomplete,
				delay: 100
			  },
			showAutocompleteOnFocus: true,
			allowEditing: true
		});
		
		showPopup(popupDOM);
		
		
		function compruebaCambios(ev) {
			// Esta comprobación la hago porque tokenfield cambia el parent del input
			var parent = ev.target.parentElement.parentElement.tagName == "FORM" ? 
				ev.target.parentElement :
				ev.target.parentElement.parentElement;
			if (ev.target.value != ev.target.originalValue) {
				//RemoveClass(parent, "hideNextElement");
			} else {
				//AddClass(parent, "hideNextElement");
			}
		}
		
		
		function onMinimoChange(ev) {
			if (ev.target.value < 0) ev.target.value = 0;
			onNumberChange(ev);
		}
		function onNumberChange(ev) {
			var numeroSinCerosDelante = /^0*(.*)/.exec(ev.target.value)[1];
			ev.target.value = eval(numeroSinCerosDelante);
			if (isNaN(ev.target.value)) ev.target.value = 0;
		}
		
		function DrawCantidadInput(seccionObjeto) {
			var seccion = lista.secciones[seccionObjeto["id_seccion"]];
			var almacen = lista.almacenes[seccion.id_almacen];
			var rId = random_id_generator();
			var seccionesSelect, almacenesSelect;
			var cantidadBlock = C("div", ["class", "cantidad-block"],
				C("div", ["class", "contenido c1"],
					almacenesSelect = C("select", ["name", "almacen-" + rId]),
					seccionesSelect = C("select", ["name", "seccion-" + rId]),
					C("input", ["name", "cantidad-" + rId, "type", "text", "value", seccionObjeto.cantidad, "class", "form-control", "onchange", function(ev) {
						onMinimoChange(ev);
						seccionObjeto.cantidad = ev.target.value;
						UpdateROCantidad();
					}])
				),
				C("div", ["class", "borrar"],
					C("div", ["class", "btn btn-danger", "onclick", function(){
						cantidadBlock.parentNode.removeChild(cantidadBlock);
						objetoLocal.secciones = objetoLocal.secciones.filter(function(x){ return x.id_seccion !== seccionObjeto.id_seccion; });
						UpdateROCantidad();
					}], "X")
				)
			);
			ToOptions(almacenesSelect, lista.almacenes, almacen);
			ToOptions(seccionesSelect, filterSecciones(almacen), seccion);
			almacenesSelect.onchange = function(ev) {
				almacen = lista.almacenes[ev.target.value];
				ToOptions(seccionesSelect, filterSecciones(almacen), seccion);
				
				var event = new Event('change');
				seccionesSelect.dispatchEvent(event);
			}
			seccionesSelect.onchange = function(ev) {
				seccionObjeto.id_seccion = parseInt(ev.target.value);
				seccion = lista.secciones[seccionObjeto.id_seccion];
			}
			
			return cantidadBlock;
		}
		
		function UpdateROCantidad() {
			cantidadROInput.value = GetCantidad(objeto);
		}
		
		function ToOptions(parentElement, elementos, selected) {
			for (var i = parentElement.childNodes.length - 1; i >= 0; i--) {
				parentElement.removeChild(parentElement.childNodes[i]);
			}
			for (var i in elementos) {
				var option = C("option", ["value", elementos[i].id], elementos[i].nombre);
				if (selected.id === elementos[i].id) option.setAttribute("selected", 1);
				C(parentElement, option);
			}
		}
		
		function filterSecciones(almacen) {
			var seccionesFiltradas = {};
			for (var i in lista.secciones) {
				if (lista.secciones[i].id_almacen === almacen.id) {
					seccionesFiltradas[i] = lista.secciones[i];
				}
			}
			return seccionesFiltradas;
		}
		
		function guardarCambios() {
			var forms = popupDOM.querySelectorAll("form");
			for (var i = 0; i < forms.length; i++) {
				// if form has changes to send, then send
				forms[i].submitter.click();
			}
		}
	}
}


function cloneObjecto(objeto) {
	var cloned = {};
	for (var i in objeto) {
		if (i != "DOM") {
			cloned[i] = JSON.parse(JSON.stringify(objeto[i]));
		}
	}
	return cloned;
}

function GetCantidad(objeto) {
	if (objeto.secciones.length == 0) return 0;
	return objeto.secciones.reduce(function(prev, cur) {
		return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
	})["cantidad"];
}

function GetImagenObjeto(objeto) {
	return objeto.imagen === null ? "http://via.placeholder.com/128x128" : "php/ajax.php?action=getfile&id=" + objeto.imagen;
}

function AddClass(dom, className) {
	var clases = dom.className.split(" ");
	clases.push(className);
	dom.className = clases.filter(onlyUnique).join(" ");
	function onlyUnique(value, index, self) { 
		return self.indexOf(value) === index;
	}
}
function RemoveClass(dom, className) {
	dom.className = dom.className.split(" ").filter(function(c) { return c != className; }).join(" ");
}

var random_id_generator = (function() {
	var c = 0;
	return function() {
		return c++;
	};
})();

function update(event) {
	event.preventDefault();
	console.log(event);
	var target = event.originalTarget !== undefined ? event.originalTarget : event.target;
	var formData = new FormData(target);
	for (var key of formData.entries()) console.log(key[0] + ', ' + key[1]);
	AJAX('php/ajax.php', formData, function(msg) {
		var json = JSON.parse(msg.response);
		formPoke(target, json.STATUS, json.MESSAGE);
		if (json.STATUS === "OK") {
			// Resetear botones de actualizar
			var arr = target.querySelectorAll("input[type=text]");
			for (var i = 0; i < arr.length; i++) {
				arr[i].originalValue = arr[i].value;
				arr[i].dispatchEvent(new Event('change'));
				arr[i].dispatchEvent(new Event('keyup'));
			}
		}
		eval(json.EVAL);
	}, function(msg) {
		alert("ERROR: " + msg.response);
	});
}

function formPoke(form, className, msg) {
	if (form.poked !== undefined) {
		getTimeout(form.poked)();
		delTimeout(form.poked);
	}
	AddClass(form, className);
	var msgDOM;
	if (msg !== undefined && msg !== null) C(form, msgDOM = C("span", ["class", "msg"], msg));
	form.poked = addTimeout(function() {
		RemoveClass(form, className);
		if (msgDOM !== null) form.removeChild(msgDOM);
		form.poked = undefined;
	}, 10000);
}

function updateImagen(id_objeto, id_imagen) {
	var objeto = lista.objetos[id_objeto];
	objeto.imagen = id_imagen;
	var imgs = document.querySelectorAll(".img-" + id_objeto);
	for (var i in imgs) {
		imgs[i].src = GetImagenObjeto(objeto);
	}
}

function updateNombre(id_objeto, nombre) {
	var objeto = lista.objetos[id_objeto];
	document.querySelector(".obj-" + id_objeto + " .titulo .nombre").innerHTML = nombre;
}





</script>




<script>
	var timeout_funcs = {};

	function addTimeout(func,time) {
		var id = window.setTimeout(func,time);
		timeout_funcs[id] = func;
		return id;
	}

	function getTimeout(id) {
		return timeout_funcs[id] ? timeout_funcs[id] : null;
	}

	function delTimeout(id) {
		if(timeout_funcs[id]) {
			window.clearTimeout(timeout_funcs[id]);
			delete timeout_funcs[id];
		}
	}
</script>



</body>
</html>