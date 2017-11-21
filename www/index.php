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
<button>+ Sección Almacen</button>


<script>
	if (typeof String.prototype.trim !== 'function') {
		String.prototype.trim = function() {
			return this.replace(/^\s+|\s+$/g, ''); 
		}
	}
	
	var timeouts = (function(list) {
		return {
			add: function(func, milliseconds) {
				var id = window.setTimeout(func, milliseconds);
				list[id] = func;
				return id;
			},
			get: function(id) {
				return list[id] ? list[id] : null;
			},
			del: function(id) {
				if(list[id]) {
					window.clearTimeout(list[id]);
					delete list[id];
				}
			}
		};
	})({});
	
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

	function clone(objeto) {
		return JSON.parse(JSON.stringify(objeto));
	}

	var random_id_generator = (function(c) {
		return function() { return c++; };
	})(0);
	
	var popups = (function(stack) {
		return {
			showPopup: function(contentsDOM) {
				var dom = C("div", ["class", "popup"],
					C("div", ["class", "bg", "onclick", popups.closePopup]),
					C("div", ["class", "msg"], contentsDOM)
				);
				document.body.appendChild(dom);
				stack.push(dom);
			},
			closePopup: function() {
				document.body.removeChild(stack.pop());
			}
		}
	})([]);
</script>

<script>

var lista;

function fixObjetoFromJSON(objeto) {
	objeto.tags = objeto.tags.split(",").filter(function(x){return x !== ""}).map(function(t) {return t.trim();});
	return objeto;
}

AJAX('php/ajax.php?action=getinventario', null, function(x) {
	lista = JSON.parse(x.responseText);
	
	var objetosById = {};
	for (var i in lista.objetos) {
		objetosById[obj.id] = fixObjetoFromJSON(lista.objetos[i]);
	}
	lista.objetos = objetosById;
	
	// Dibujar toda la lista en el DOM
	C(document.getElementById("inventario"), DrawInventory(lista));
	
	var tagsArrayAutocomplete = GetAutocompleteTags(lista.objetos);
	
	// Preparar buscador
	var buscador = document.getElementById("buscador");
	buscador.onkeyup = function() {
		FilterSearch.process(buscador.value, lista,
			function(DOM) { DOM.style.display = "unset"; },
			function(DOM) { DOM.style.display = "none"; }
		);
	};
	
	
	
	function GetAutocompleteTags(objetos) {
		var arr = [];
		for (var i in objetos) arr = arr.concat(objetos[i].tags.filter(function(x){ return arr.indexOf(x) === -1; }));
		return arr
	}
	
	function GetCantidad(objeto) {
		if (objeto.secciones.length == 0) return 0;
		return objeto.secciones.reduce(function(prev, cur) {
			return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
		})["cantidad"];
	}	
		
	function DrawInventory(lista) {
		var contenedor = C("div");
		for (var i in lista.objetos) C(contenedor, DrawObjeto(i));
		return contenedor;
	}
	
	function DrawObjeto(i) {
		var objeto = lista.objetos[i];
		var cantidad, tagsDOM, domObjetoEnLista;
		return GeneraDomObjeto();

		
		
		function GeneraDomObjeto() {
			cantidad = GetCantidad(objeto);
			domObjetoEnLista = C("button", ["class", "objeto obj-" + objeto.id, "onclick", edit],
				C("div", ["class", "titulo"],
					C("div", ["class", "nombre"], objeto.nombre)
				),
				C("div", ["class", "img-container"],
					C("img", ["class", "img img-" + objeto.id, "src", GetImagenObjeto(objeto)])
				),
				C("div", ["class", "info"],
					C("div", ["class", "cantidad"], "Cantidad: ", cantidad),
					C("div", ["class", "minimo"], "Mínimo: ", objeto.minimo_alerta),
					C("div", ["class", "tags"], "Tags: ", tagsDOM = C("span", ["class", "tags-list"]))
				)
			);
			for (var i in objeto.tags) C(tagsDOM, C("span", objeto.tags[i]));
			(cantidad < parseInt(objeto.minimo_alerta) ? AddClass : RemoveClass)(domObjetoEnLista, "alerta");
			return domObjetoEnLista;
		}
		
		function updateListObject() {
			AJAX('php/ajax.php?action=getinventarioitem&id=' + objeto.id, null, function(x) {
				lista.objetos[i] = objeto = fixObjetoFromJSON(JSON.parse(x.responseText)[0]);
				var aBorrar = domObjetoEnLista;
				domObjetoEnLista.parentNode.insertBefore(GeneraDomObjeto(), aBorrar);
				domObjetoEnLista.parentNode.removeChild(aBorrar);
			}, console.log);
		}
		
		function edit() {
			var objetoLocal = clone(objeto)
			cantidad = GetCantidad(objetoLocal);
			var cantidadROInput = C("input", ["type", "text", "value", cantidad, "class", "form-control", "readonly", 1], cantidad);
			var tags;
			var cantidades;
			var popupDOM = C("div",
				C("div", ["style", "padding: 1%", "updateListObject", updateListObject],
					C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php"],
						C("div", "Nombre"),
						C("div", C("input", ["name", "nombre", "type", "text", "value", objetoLocal.nombre, "class", "form-control"])),
						C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
						C("input", ["type", "hidden", "name", "action", "value", "update-object-name"])
					),
					C("form", ["class", "right_big img", "method", "post", "action", "php/ajax.php"],
						C("div", "Imagen"),
						C("div",
							C("img", ["src", GetImagenObjeto(objetoLocal), "id", "img_objeto", "class", "img-" + objetoLocal.id]),
							C("input", ["name", "imagen", "type", "file", "accept", "image/*", "capture", "camera"])
						), 
						C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
						C("input", ["type", "hidden", "name", "action", "value", "update-object-image"])
					),
					C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php"],
						C("div", "Cantidad mínima"),
						C("div", C("input", ["name", "minimo", "type", "text", "value", objetoLocal.minimo_alerta, "class", "form-control", "onchange", onPositiveNumberChange])),
						C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
						C("input", ["type", "hidden", "name", "action", "value", "update-object-minimo"])
					),
					C("form", ["class", "left_big", "method", "post", "action", "php/ajax.php"],
						C("div", "Cantidad"),
						C("div", C("div", ["class", "cantidades"],
							cantidades = C("div", ["class", "tabla"],
								C("div", ["class", "cantidad-block"],
									C("div", ["class", "contenido"],
										C("span", "Almacen"),
										C("span", "Sección"),
										C("span", "Cantidad")
									)
								)
							),
							C("div", ["class", "btn btn-primary add", "onclick", function(){
								objetoLocal.secciones[objetoLocal.secciones.length] = {cantidad: 0, id_seccion: Object.keys(lista.secciones)[0]};
								C(cantidades, DrawCantidadInput(objetoLocal.secciones[objetoLocal.secciones.length - 1]));
							}], "+ Añadir a otro lugar"),
							C("span", C("span", "Total:"), cantidadROInput)
						)),
						C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
						C("input", ["type", "hidden", "name", "action", "value", "update-object-cantidades"])
					),
					C("form", ["class", "right_big", "method", "post", "action", "php/ajax.php"],
						C("div", ["class", "has-help"], "Tags", C("div", ["class", "desc"], "Palabras claves usadas para filtrar la búsqueda y encontrar este elemento")),
						C("div", tags = C("input", ["name", "tags", "type", "text", "value", objetoLocal.tags, "class", "form-control"])),
						C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
						C("input", ["type", "hidden", "name", "action", "value", "update-object-tags"])
					),
					C("div", ["class", "clear"])
				),
				C("div", ["class", "botonesAceptarCancelar"],
					C("input", ["type", "button", "class", "btn btn-success guarda", "value", "Guardar cambios", "onclick", guardarCambios]),
					C("input", ["type", "button", "class", "btn btn-default cierra", "value", "Cancelar", "onclick", popups.closePopup]),
					C("div", ["style", "text-align: left; display: none;"], "ID: ", objetoLocal.id)
				)
			);
			
			for (var i = 0; i < objetoLocal.secciones.length; i++) {
				C(cantidades, DrawCantidadInput(objetoLocal.secciones[i]));
			}
			
			var forms = popupDOM.querySelectorAll("form");
			for (var i = 0; i < forms.length; i++) {
				C(forms[i], forms[i].submitter = C("input", ["type", "submit"]));
				forms[i].submitter.style = "display: none";
				forms[i].onsubmit = update;
			}
			
			$(tags).tokenfield({
				autocomplete: {
					source: tagsArrayAutocomplete,
					delay: 100
				  },
				showAutocompleteOnFocus: true,
				allowEditing: true
			});
			
			popups.showPopup(popupDOM);
			
			
			
			function onPositiveNumberChange(ev) {
				var soloNumeros = ev.target.value.replace(/[^0-9 +*/-]/g, ""); // Quitar letras y +
				var numeroSinCerosDelante = soloNumeros.replace(/^0*/, ""); // Evitar octal quitando en el inicio
				var numeroFinal = eval(numeroSinCerosDelante); // Procesar +-*/
				ev.target.value = isNaN(numeroFinal) || numeroFinal < 0 ? 0 : numeroFinal;
			}
			
			function DrawCantidadInput(seccionObjeto) {
				var seccion = lista.secciones[seccionObjeto.id_seccion];
				var almacen = lista.almacenes[seccion.id_almacen];
				var rId = random_id_generator();
				var seccionesSelect, almacenesSelect;
				var cantidadBlock = C("div", ["class", "cantidad-block"],
					C("div", ["class", "contenido c1"],
						almacenesSelect = C("select", ["name", "almacen-" + rId]),
						seccionesSelect = C("select", ["name", "seccion-" + rId]),
						C("input", ["name", "cantidad-" + rId, "type", "text", "value", seccionObjeto.cantidad, "class", "form-control", "onchange", function(ev) {
							onPositiveNumberChange(ev);
							seccionObjeto.cantidad = ev.target.value;
							UpdateROCantidad();
						}])
					),
					C("div", ["class", "borrar"],
						C("div", ["class", "btn btn-danger", "onclick", function(){
							objetoLocal.secciones.splice(Array.prototype.indexOf.call(cantidadBlock.parentNode.childNodes, cantidadBlock) - 1, 1); // -1 porque el indice 0 esta ocupado por la cabecera de la tabla
							cantidadBlock.parentNode.removeChild(cantidadBlock);
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
				cantidadROInput.value = GetCantidad(objetoLocal);
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
			
			function update(event) {
				event.preventDefault();
				var target = event.originalTarget !== undefined ? event.originalTarget : event.target;
				var formData = new FormData(target);
				//for (var key of formData.entries()) console.log(key[0] + ', ' + key[1]);
				AJAX('php/ajax.php', formData, function(msg) {
					var json = JSON.parse(msg.response);
					formPoke(target, json.STATUS, json.MESSAGE);
					if (json.STATUS === "OK") {
						// Actualizar y Redibujar
						target.parentNode.updateListObject();
					}
					//eval(json.EVAL);
				}, function(msg) {
					alert("ERROR: " + msg.response);
				});
			}

			function formPoke(form, className, msg) {
				if (form.poked !== undefined) {
					timeouts.get(form.poked)();
					timeouts.gel(form.poked);
				}
				AddClass(form, className);
				var msgDOM;
				if (msg !== undefined && msg !== null) C(form, msgDOM = C("span", ["class", "msg"], msg));
				form.poked = timeouts.add(function() {
					RemoveClass(form, className);
					if (msgDOM !== null) form.removeChild(msgDOM);
					form.poked = undefined;
				}, 10000);
			}
		}
	}
}, console.log);













function GetImagenObjeto(objeto) {
	return objeto.imagen === null ? "http://via.placeholder.com/128x128" : "php/ajax.php?action=getfile&id=" + objeto.imagen;
}
</script>



</body>
</html>