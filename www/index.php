<html>
<head>
<link rel="stylesheet" type="text/css" href="css/main.css"> 

<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script src="js/ajax.js"></script>
<script src="js/crel2.js"></script>
<script src="js/libs/jshashtable/hashtable.js"></script>
<script src="js/utils.js"></script>

<!-- Tokenfield -->
<script src="js/libs/jquery/jquery-1.9.1.min.js"></script>
<script src="js/libs/jquery/jquery-ui-1.10.3.min.js"></script>
<script src="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.js"></script>
<script src="js/libs/Sortable/Sortable.min.js"></script>
<script defer src="https://use.fontawesome.com/releases/v5.0.2/js/all.js"></script>
<link href="js/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="js/libs/jquery/themes/smoothness/jquery-ui.min.css" type="text/css" rel="stylesheet">
<link href="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.min.css" type="text/css" rel="stylesheet">
</head>
<body>


<div class="buscador">
	<div class="fixed">
		Búsqueda: <input id="buscador" type="text" placeholder="Búsqueda" class="form-control" autofocus="autofocus" onfocus="this.select()"/>
		<button class="btn btn-primary" style="display:none" id="BTN_BUSQUEDAS_PREPARADAS">Búsquedas guardadas</button>
	</div>
</div>

<button onclick="addObjeto()" class="btn btn-primary">Añadir nuevo objeto</button>
<button onclick="ListarAlmacenesSecciones()" class="btn btn-primary">Editar Almacenes y secciones</button>
<button onclick="MostrarHistorial()" class="btn btn-primary">Historial</button>


<div id="inventario"></div>
<div class="clearer"></div>



<script>
var popups = (function(stack) {
	return {
		showPopup: function(contentsDOM) {
			var dom = C("div", ["class", "popup"],
				C("div", ["class", "bg"]),
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

function GetObjectKeysSorted(obj) {
	return Object.keys(obj).map(function(x) { return parseInt(x) }).sort(function(a, b){ return a - b });
}

function GetFirstFreeID(ids) {
	var id = 1;
	while (ids.indexOf(id) !== -1) id++;
	return id;
}
</script>

<script>

var lista, tagsArrayAutocomplete;

function fixObjetoFromJSON(objeto) {
	objeto.tags = objeto.tags.split(",").filter(function(x){return x !== ""}).map(function(t) {return t.trim();});
	return objeto;
}


function DrawObjeto(i) {
	var objeto = lista.objetos[i];
	var cantidad, tagsDOM, domObjetoEnLista;
	return GeneraDomObjeto();

	
	function GeneraDomObjeto() {
		cantidad = GetCantidad(objeto);
		domObjetoEnLista = C("button", ["class", "objeto obj-" + objeto.id, "onclick", function() { edit(UpdateListObject); }],
			C("div", ["class", "titulo"],
				C("div", ["class", "nombre"], objeto.nombre)
			),
			C("div", ["class", "img-container"],
				C("img", ["class", "img img-" + objeto.id, "src", GetImagenObjeto(objeto)])
			),
			C("div", ["class", "info"],
				C("div", ["class", "cantidad"], "Cantidad: ", cantidad),
				C("div", ["class", "minimo"], "Mínimo: ", objeto.minimo),
				C("div", ["class", "tags"], "Palabras clave: ", tagsDOM = C("span", ["class", "tags-list"]))
			)
		);
		for (var j in objeto.tags) C(tagsDOM, C("span", objeto.tags[j]));
		(cantidad < parseInt(objeto.minimo) ? AddClass : RemoveClass)(domObjetoEnLista, "alerta");
		objeto.onRemove = function() {
			AJAX('php/ajax.php', 'action=remove-object&id-object=' + objeto.id, function(msg) {
				var json = JSON.parse(msg.response);
				if (json.STATUS === "OK") {
					domObjetoEnLista.parentNode.removeChild(domObjetoEnLista);
					popups.closePopup();
					popups.closePopup();
				}
			}, console.log);
		};
		return objeto.DOM = domObjetoEnLista;
	}
	
	function UpdateListObject(objetoCallback) {
		AJAX('php/ajax.php?action=getinventarioitem&id=' + objeto.id, null, function(msg) {
			lista.objetos[i] = objeto = fixObjetoFromJSON(JSON.parse(msg.response));
			var aBorrar = domObjetoEnLista;
			domObjetoEnLista.parentNode.insertBefore(GeneraDomObjeto(), aBorrar);
			domObjetoEnLista.parentNode.removeChild(aBorrar);
			if (objetoCallback) objetoCallback(shallowClone(objeto));
		}, console.log);
	}
}

function DrawObjectList() {
	document.getElementById("inventario").innerHTML = "";

	AJAX('php/ajax.php?action=getinventario', null, function(msg) {
		lista = JSON.parse(msg.response);
		
		var objetosById = {};
		for (var i in lista.objetos) {
			objetosById[lista.objetos[i].id] = fixObjetoFromJSON(lista.objetos[i]);
		}
		lista.objetos = objetosById;
		
		// Dibujar toda la lista en el DOM
		C(document.getElementById("inventario"), DrawInventory(lista));
		
		tagsArrayAutocomplete = GetAutocompleteTags(lista.objetos);
		
		// Preparar buscador
		var buscador = document.getElementById("buscador");
		buscador.onkeyup = buscador.onchange = function() {
			FilterSearch.process(buscador.value, lista,
				TestCustomKeyword,
				true,
				function(DOM) { DOM.style.display = "unset"; },
				function(DOM) { DOM.style.display = "none"; }
			);
		};
		
		
		
		function DrawInventory(lista) {
			contenedorListaObjetos = C("div");
			for (var i in lista.objetos) C(contenedorListaObjetos, DrawObjeto(i));
			return contenedorListaObjetos;
		}
		
		function TestCustomKeyword(keyword, object) {
			switch (keyword) {
				case "minimo":
					return object.minimo > GetCantidad(object);
			}
		}
		
		function GetAutocompleteTags(objetos) {
			var arr = [];
			for (var i in objetos) arr = arr.concat(objetos[i].tags);
			return arr.filter(onlyUnique);
		}
	}, console.log);
}
	
function GetCantidad(objeto) {
	if (objeto.secciones.length == 0) return 0;
	return objeto.secciones.reduce(function(prev, cur) {
		return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
	})["cantidad"];
}

function edit(UpdateListObject) {
	var cantidades;
	var tags;
	var forms;
	var updaterDOM;
	var objetoLocal;
	var cantidadROInput;
	var popupDOM;
	UpdateListObject(function(newObjetoLocal) {
		objetoLocal = newObjetoLocal;
		cantidad = GetCantidad(objetoLocal);
		cantidadROInput = C("input", ["type", "text", "value", cantidad, "class", "form-control", "readonly", 1], cantidad);
		
		popupDOM = C("div",
			updaterDOM = C("div", ["style", "padding: 1%", "UpdateListObject", UpdateListObject],
				C("form", ["class", "ajax left_big", "method", "post", "action", "php/ajax.php"],
					C("div", "Nombre"),
					C("div", C("input", ["name", "nombre", "type", "text", "value", objetoLocal.nombre, "class", "form-control"])),
					DOMInputAction("update-object-name")
				),
				C("form", ["class", "ajax right_big img", "method", "post", "action", "php/ajax.php"],
					C("div", "Imagen"),
					C("div",
						C("img", ["src", GetImagenObjeto(objetoLocal), "id", "img_objeto", "class", "img-" + objetoLocal.id]),
						C("input", ["name", "imagen", "type", "file", "accept", "image/*", "capture", "camera"])
					),
					DOMInputAction("update-object-image")
				),
				C("form", ["class", "ajax left_big", "method", "post", "action", "php/ajax.php"],
					C("div", ["class", "has-help"],
						"Cantidad mínima",
						C("div", ["class", "desc"],
							"Se mostrará una alerta si la cantidad total de objetos es menor que este valor.",
							C("img", ["src", "media/inputs.gif"])
						)
					),
					C("div", C("input", ["name", "minimo", "type", "text", "value", objetoLocal.minimo, "class", "form-control", "onchange", onPositiveNumberChange])),
					DOMInputAction("update-object-minimo")
				),
				C("form", ["class", "ajax left_big", "method", "post", "action", "php/ajax.php"],
					C("div", ["class", "has-help"],
						"Cantidad",
						C("div", ["class", "desc"],
							C("img", ["src", "media/inputs.gif"])
						)
					),
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
						C("div", ["class", "btn btn-primary add", "onclick", function() {
							objetoLocal.secciones[objetoLocal.secciones.length] = {cantidad: 0, id_seccion: Object.keys(lista.secciones)[0]};
							C(cantidades, DrawCantidadInput(objetoLocal.secciones[objetoLocal.secciones.length - 1]));
						}], "+ Añadir a otro lugar"),
						C("span", C("span", "Total:"), cantidadROInput)
					)),
					DOMInputAction("update-object-cantidades")
				),
				C("form", ["class", "ajax right_big", "method", "post", "action", "php/ajax.php"],
					C("div", ["class", "has-help"], "Palabras clave", C("div", ["class", "desc"], "Palabras clave para filtrar una búsqueda y encontrar este objeto")),
					C("div", tags = C("input", ["name", "tags", "type", "text", "value", objetoLocal.tags, "class", "form-control"])),
					DOMInputAction("update-object-tags")
				),
				C("div", ["class", "clear"])
			),
			PieGuardarCancelar("Guardar cambios", "btn-success guarda", guardarCambios, "Cerrar", popups.closePopup, true, "Borrar", function() { abrirBorrarVentana("confirmBorrar", "btn-danger", C("div", "¿Seguro que quiere borrar este objeto?", C("br"), "Esta acción se puede deshacer (por hacer) desde el historial de acciones pasadas"), objetoLocal.onRemove) })
		);
		
		for (var i = 0; i < objetoLocal.secciones.length; i++) {
			C(cantidades, DrawCantidadInput(objetoLocal.secciones[i]));
		}
		
		forms = popupDOM.querySelectorAll("form");
		
		for (var i = 0; i < forms.length; i++) {
			C(forms[i], forms[i].submitter = C("input", ["type", "submit", "style", "display: none"]));
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
	});
	
	
	
	function DOMInputAction(action) {
		return C("span",
			C("input", ["type", "hidden", "name", "id-object", "value", objetoLocal.id]),
			C("input", ["type", "hidden", "name", "action", "value", action])
		);
	}
	function onPositiveNumberChange(ev) {
		var soloNumeros = ev.target.value.replace(/[^0-9 +*/-]/g, ""); // Quitar letras y +
		var numeroSinCerosDelante = soloNumeros.replace(/^0*/, ""); // Evitar octal quitando ceros del inicio
		var numeroFinal = eval(numeroSinCerosDelante); // Procesar +-*/
		ev.target.value = isNaN(numeroFinal) || numeroFinal < 0 ? 0 : numeroFinal; // Hacer positivo
	}
	
	function DrawCantidadInput(seccionObjeto) {
		var seccion = lista.secciones[seccionObjeto.id_seccion];
		var almacen = lista.almacenes[seccion.id_almacen];
		var rId = id_generator();
		var seccionesSelect, almacenesSelect, cantidadInput;
		var cantidadBlock = C("div", ["class", "cantidad-block"],
			C("div", ["class", "contenido c1"],
				almacenesSelect = C("select", ["name", "almacen-" + rId]),
				seccionesSelect = C("select", ["name", "id_seccion-" + rId]),
				cantidadInput = C("input", ["name", "cantidad-" + rId, "type", "text", "value", seccionObjeto.cantidad, "class", "form-control", "onchange", function(ev) {
					onPositiveNumberChange(ev);
					seccionObjeto.cantidad = ev.target.value;
					UpdateROCantidad();
				}])
			),
			C("div", ["class", "borrar"],
				C("div", ["class", "btn btn-danger", "onclick", function() {
					objetoLocal.secciones.splice(Array.prototype.indexOf.call(cantidadBlock.parentNode.childNodes, cantidadBlock) - 1, 1); // Indice 0 ocupado por la cabecera de la tabla
					cantidadBlock.parentNode.removeChild(cantidadBlock);
					UpdateROCantidad();
				}], "X")
			)
		);
		ToOptions(almacenesSelect, lista.almacenes, almacen);
		ToOptions(seccionesSelect, filterSecciones(almacen), seccion);
		almacenesSelect.onchange = function(ev) {
			almacen = lista.almacenes[ev.target.value];
			var seccionesDisponibles = filterSecciones(almacen);
			ToOptions(seccionesSelect, seccionesDisponibles, seccion);
			cantidadInput.disabled = Object.keys(seccionesDisponibles).length == 0;
			if (cantidadInput.disabled) cantidadInput.value = "0";
			
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
			if (typeof selected !== "undefined" && selected.id === elementos[i].id) option.setAttribute("selected", 1);
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
		for (var i = 0; i < forms.length; i++) {
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
			switch (json.STATUS) {
				case "OK":
					target.parentNode.UpdateListObject(updateForm);
				case "ERROR":
					TemporalMessage(target, json.STATUS, json.MESSAGE, 10000);
					break;
				case "SAME":
					TemporalMessage(target, json.STATUS, json.MESSAGE, 2500);
					break;
				case "RELOAD":
					TemporalMessage(target, json.STATUS, json.MESSAGE, 5000);
					if (confirm("No se han guardado los cambios porque se han realizado cambios más recientes. ¿Desea recargar la página para que muestre los cambios más recientes en este objeto?")) {
						target.parentNode.UpdateListObject(function(newObjetoLocal) {
							popups.closePopup();
							edit(UpdateListObject);
						});
					}
					break;
			}
		}, function(msg) {
			alert("ERROR: " + msg.response);
		});
	}
	
	function updateForm(newObjetoLocal) {
		objetoLocal = newObjetoLocal;
		/*var inputs = popupDOM.querySelectorAll("input[name=version]");
		for (var i = 0; i < inputs.length; i++) inputs[i].value = objetoLocal.version;*/
		popupDOM.querySelector("img[id=img_objeto]").setAttribute("src", GetImagenObjeto(objetoLocal));
	}
}

function TemporalMessage(target, className, msg, milliseconds) {
	timeouts.finishNow(target);
	
	AddClass(target, className);
	
	(function(msgDOM) {
		C(target, msgDOM);
		
		timeouts.addWithKey(target, function() {
			RemoveClass(target, className);
			target.removeChild(msgDOM);
		}, milliseconds);
	})(C("span", ["class", "msg"], msg));
}

function abrirBorrarVentana(popupClass, btnClass, msg, onRemoveCallback) {
	popups.showPopup(C("div", ["class", popupClass],
		C("div", ["class", "titulo"], msg),
		PieGuardarCancelar("Borrar", btnClass + " borra", onRemoveCallback, "Cancelar", popups.closePopup)
	));
}

function addObjeto() {
	AJAX('php/ajax.php', 'action=create-empty-object', function(msg) {
		var json = JSON.parse(msg.response);
		var id = json.MESSAGE;
		
		AJAX('php/ajax.php?action=getinventarioitem&id=' + id, null, function(msg) {
			lista.objetos[id] = fixObjetoFromJSON(JSON.parse(msg.response));
			contenedorListaObjetos.appendChild(DrawObjeto(id));
			lista.objetos[id].DOM.onclick();
		}, console.log);
	}, console.log);
}

function GetImagenObjeto(objeto) {
	return objeto.imagen === null ? "http://via.placeholder.com/128x128" : "php/ajax.php?action=getfile&id=" + objeto.imagen;
}

function PieGuardarCancelar(guardarStr, guardarClass, guardarFunc, cerrarStr, cerrarFunc, mostrarBorrar, borrarStr, borrarFunc) {
	var pie = C("div", ["class", "botonesAceptarCancelar"],
		C("button", ["type", "button", "class", "btn " + guardarClass, "onclick", guardarFunc], guardarStr),
		C("button", ["type", "button", "class", "btn btn-default cierra", "onclick", cerrarFunc], cerrarStr)
	);
	if (mostrarBorrar) {
		C(pie, C("div", ["class", "borra"],
			C("button", ["type", "button", "class", "btn btn-danger borra", "onclick", borrarFunc], borrarStr)
		));
	}
	return pie;
}

function ListarAlmacenesSecciones() {
	var arbolContainer;
	popups.showPopup(C("div", ["class", "lista-almacenes-secciones"],
		arbolContainer = C("div"),
		C("div", ["class", "addAlmacenContainer"], C("button", ["class", "btn btn-primary", "onclick", AddAlmacen], "Añadir Almacén")),
		PieGuardarCancelar("Guardar cambios", "btn-success guarda", Guardar, "Cancelar", popups.closePopup, false)
	));
	
	var almacenes = JSON.parse(JSON.stringify(lista.almacenes));
	var secciones = JSON.parse(JSON.stringify(lista.secciones));
	
	// No queremos crear secciones iguales a secciones borradas justo ahora para evitar mover cantidades en lugar de borrarlas.
	var almacenesUsados = GetObjectKeysSorted(almacenes);
	var seccionesUsados = GetObjectKeysSorted(secciones);
	
	for (var i in almacenes) {
		DrawAlmacen(almacenes[i]);
	}
	
	
	
	function Guardar(forzar) {
		var post = 'action=update-almacenes-secciones&almacenes=' + JSON.stringify(almacenes) + "&secciones=" + JSON.stringify(secciones);
		if (typeof forzar !== undefined) post += "&forzar=" + (true === forzar ? "OK" : "NO");
		AJAX('php/ajax.php', post, function(msg) {
			var json = JSON.parse(msg.response);
			if (json.STATUS === "ASK") {
				var contenedor = C("div");
				for (var i in json.MESSAGE) {
					C(contenedor, C("div", ["class", "aBorrarNombre"], "Objeto: ", C("span", ["class", "var"], lista.objetos[i].nombre)));
					for (var j in json.MESSAGE[i]) {
						console.log(json.MESSAGE[i][j]);
						C(contenedor, C("div", ["class", "aBorrarSeccionCantidad"],
							"Sección: ", C("span", ["class", "var"], lista.secciones[json.MESSAGE[i][j].id_seccion].nombre),
							", Stock: ", C("span", ["class", "var"], json.MESSAGE[i][j].cantidad)
						));
					}
				}
				abrirBorrarVentana("confirmBorrar", "btn-danger", C("div", C("div", "Se van a borrar secciones que contienen objetos. El Stock de los siguientes objetos se borrará:"), contenedor), function() { popups.closePopup(); Guardar(true); });
			} else {
				// Redibujar listado completo de objetos y actualizar listado de almacenes
				popups.closePopup();
				DrawObjectList();
				popups.showPopup(C("div",
					C("div", ["class", "titulo", "style", "color: #222"], json.MESSAGE),
					C("div", ["class", "botonesAceptarCancelar"],
						C("button", ["type", "button", "class", "btn btn-default cierra", "onclick", popups.closePopup], "Cerrar")
					)
				));
			}
		}, console.log);
	}
	
	function AddAlmacen() {
		var keys = GetObjectKeysSorted(almacenes).concat(almacenesUsados).filter(onlyUnique);
		var id = GetFirstFreeID(keys);
		almacenes[id] = {
			id: id,
			nombre: "Almacen " + id
		};
		DrawAlmacen(almacenes[id]);
	}
	
	function DrawAlmacen(almacen) {
		var contenedor;
		var seccionesContainer;
		C(arbolContainer,
			contenedor = C("div", ["class", "almacen"],
				C("div", ["class", "header first"], "Almacén"),
				C("input", ["type", "text", "class", "form-control", "value", almacen.nombre, "onchange", OnChangeAlmacen, "onkeyup", OnChangeAlmacen]),
				C("div", ["class", "header"], "Secciones"),
				seccionesContainer = C("div"),
				C("div", ["class", "btn addseccion btn-primary", "onclick", addSeccion], "Añadir Sección"),
				C("div", ["class", "btn btn-danger borraalmacen", "onclick", borrarAlmacen], "Borrar Almacen")
			)
		);
		
		RedibujarSecciones();
		
		
		
		function OnChangeAlmacen(ev) {
			almacen.nombre = ev.target.value;
		}
		
		function addSeccion() {
			var keys = GetObjectKeysSorted(secciones).concat(seccionesUsados).filter(onlyUnique);
			var id = GetFirstFreeID(keys);
			secciones[id] = {
				id: id,
				id_almacen: almacen.id,
				nombre: "Sección " + id
			};
			
			RedibujarSecciones();
		}
		
		function borrarAlmacen() {
			delete almacenes[almacen.id];
			var seccionesToDelete = [];
			for (var i in secciones)
				if (secciones[i].id_almacen == almacen.id)
					seccionesToDelete.push(i);
			for (var i = 0; i < seccionesToDelete.length; i++)
				delete secciones[seccionesToDelete[i]];
			arbolContainer.removeChild(contenedor);
		}
		
		function RedibujarSecciones() {
			seccionesContainer.innerHTML = "";
			
			for (var j in secciones) {
				if (secciones[j].id_almacen == almacen.id) {
					(function (seccion) {
						C(seccionesContainer,
							C("div", ["class", "seccion"],
								C("input", ["type", "text", "class", "form-control", "value", seccion.nombre, "onchange", OnChangeSeccion, "onkeyup", OnChangeSeccion]),
								C("div", ["class", "btn btn-danger", "onclick", OnRemoveSeccion], "X")
							)
						);
						
						function OnChangeSeccion(ev) {
							seccion.nombre = ev.target.value;
						}
						function OnRemoveSeccion() {
							delete secciones[seccion.id];
							RedibujarSecciones();
						}
					})(secciones[j]);
				}
			}
		}
	}
}

AJAX('php/ajax.php?action=getbusquedaspreparadas', null, function(msg) {
	var busquedasArr = JSON.parse(msg.response);
	
	BTN_BUSQUEDAS_PREPARADAS.style.display = "";
	BTN_BUSQUEDAS_PREPARADAS.onclick = popupBusquedasPreparadas;
	
	function popupBusquedasPreparadas() {
		var busquedas_ul = C("ul", ["class", "busquedas"]);
		for (var i = 0; i < busquedasArr.length; i++) {
			AddBusquedapreparada(busquedasArr[i].nombre, busquedasArr[i].busqueda);
		}
		var contenedorBusquedas = C("div", ["class", "busquedas-contenedor ajax"],
			busquedas_ul,
			C("button", ["class", "btn btn-default", "onclick", add], "Añadir búsqueda"),
		);
		popups.showPopup(C("div",
			contenedorBusquedas,
			PieGuardarCancelar("Guardar cambios", "btn-success guarda", guardar, "Cerrar", popups.closePopup, false)
		));
		
		var sortable = Sortable.create(busquedas_ul, {
			handle: ".handle"
		});
		
		function add() {
			AddBusquedapreparada("Nueva búsqueda", "Texto que buscar");
		}
		
		function guardar() {
			// recorrer todos los LI del UL
			var busquedas = [];
			var items = busquedas_ul.getElementsByTagName("li");
			for (var i = 0; i < items.length; i++) {
				busquedas.push({nombre: items[i].b.innerHTML, busqueda: items[i].b.busqueda});
			}
			console.log(busquedas);
			
			AJAX('php/ajax.php', 'action=update-busquedaspreparadas&busquedaspreparadas=' + encodeURIComponent(JSON.stringify(busquedas)), function(msg) {
				var json = JSON.parse(msg.response);
				busquedasArr = busquedas;
				TemporalMessage(contenedorBusquedas, json.STATUS, json.MESSAGE, 10000);
			}, console.log);
		}
		
		function AddBusquedapreparada(nombre, busqueda) {
			var li, b;
			C(busquedas_ul, li = C("li",
				C("span", ["class", "btn btn-info handle"], "⬍"),
				b = C("button", ["class", "btn btn-primary", "onclick", click], nombre),
				C("button", ["class", "btn btn-warning boton", "onclick", edit], C("i", ["class", "far fa-edit"])),
				C("button", ["class", "btn btn-danger boton", "onclick", borrar], "X")));
			b.busqueda = busqueda;
			li.b = b;
			
			
			function click() {
				popups.closePopup();
				buscador.value = b.busqueda;
				buscador.onchange();			
			}
			
			function borrar() {
				busquedas_ul.removeChild(li);
			}
		
			function edit() {
				var nom, bus;
				popups.showPopup(C("div",
					C("table", ["class", "editbusqueda"],
						C("tr",
							C("td", "Nombre del botón"),
							C("td", nom = C("input", ["class", "form-control", "value", nombre]))
						),
						C("tr",
							C("td", "Texto a buscar"),
							C("td", bus = C("input", ["class", "form-control", "value", busqueda]))
						)
					),
					PieGuardarCancelar("Aceptar", "btn-success guarda", aceptar, "Cancelar", popups.closePopup, false)
				));
				
				function aceptar() {
					b.innerHTML = nom.value;
					b.busqueda = bus.value;
					popups.closePopup();
				}
			}
		}
	}
}, console.log);

function MostrarHistorial() {
	var container;
	popups.showPopup(C("div", ["class", "historial"],
		container = C("div", "Si se realiza un deshacer se agrupará los cambios e indicará a qué id se deshizo. La acción de deshacer también se puede deshacer, y deshacer se queda registrado en el historial como una acción. No se puede borrar una acción, por lo que deshacer no deshacer sino que repite acciones pasadas en sentido inverso, creando nuevos eventos. Por ejemplo, se cambia valor mínimo de objeto tal de esta cantidad a esta otra (que lo puedes clicar si esque existe, y abre el objeto para que lo veas).", C("br")),
		PieGuardarCancelar("Deshacer cambios", "btn-success guarda", Deshacer, "Cancelar", popups.closePopup, false)
	));
	
	// Obtener listado
	AJAX('php/ajax.php?action=gethistory', null, function(msg) {
		var listado = JSON.parse(msg.response);
		listado = listado.reverse();
		
		var transaccion = C("div");
		
		for (var i = 0; i < listado.length; i++) {
			(function(i) {
				var step = listado[i];
				if (step.ACCION === "SPACING") {
					transaccion = C("div");
					C(container,
						C("div", ["class", "accion"],
							C("button", ["class", "btn btn-danger deshacer has-help", "onclick", Undo],
								"Deshacer",
								C("div", ["class", "desc"],
									"Clicar en este botón deshace todos los cambios realizados aquí y posteriores."
								)
							),
							C("span", ["class", "id"], step.ID),
							C("span", ["class", "nombre"], step.T1),
							C("span", ["class", "fecha"], step.Fecha),
							C("br"),
							transaccion
						),
						C("br")
					);
				} else {
					var entradaWrapp = C("div", ["class", "entrada"]);			
					C(entradaWrapp, C("div", ["class", "nombre"], step.ACCION));
					var entrada = C("div", ["class", "contenido"]);
					C(entradaWrapp, entrada);
					
					switch (step.ACCION) {
						case "DELETE ALMACEN":
							C(entrada, C("div", "Id almacén: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							break;
						case "INSERT ALMACEN":
							C(entrada, C("div", "Id almacén: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							break;
						case "UPDATE ALMACEN":
							C(entrada, C("div", "Id almacén: " + step.I1));
							CambioDescription(entrada, "Nombre", step.T1, step.T2);
							break;
						case "DELETE FILE":
							C(entrada, C("div", "Id file: " + step.I1));
							C(entrada, C("div", "Binario: " + step.B1));
							C(entrada, C("div", "Mimetype: " + step.T1));
							break;
						case "INSERT FILE":
							C(entrada, C("div", "Id file: " + step.I1));
							C(entrada, C("div", "Binario: " + step.B1));
							C(entrada, C("div", "Mimetype: " + step.T1));
							break;
						case "DELETE OBJETO":
							C(entrada, C("div", "Id objeto: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							C(entrada, C("div", "Mínimo: " + step.I2));
							C(entrada, C("div", "Imagen: " + step.T2));
							C(entrada, C("div", "Tags: " + step.T3));
							break;
						case "INSERT OBJETO":
							C(entrada, C("div", "Id objeto: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							C(entrada, C("div", "Mínimo: " + step.I2));
							C(entrada, C("div", "Imagen: " + step.T2));
							C(entrada, C("div", "Tags: " + step.T3));
							break;
						case "UPDATE OBJETO":
							C(entrada, C("div", "Id objeto: " + step.I1));
							CambioDescription(entrada, "Nombre", step.T1, step.T2);
							CambioDescription(entrada, "Mínimo", step.I2, step.I3);
							CambioDescription(entrada, "Id imagen", step.T3, step.T4);
							CambioDescription(entrada, "Tags", step.T5, step.T6);
							break;
						case "DELETE OBJETO_SECCION":
							C(entrada, C("div", "Id objeto: " + step.I1));
							C(entrada, C("div", "Id sección: " + step.I2));
							CambioDescription(entrada, "Cantidad", step.I3, "---");
							break;
						case "INSERT OBJETO_SECCION":
							C(entrada, C("div", "Id objeto: " + step.I1));
							C(entrada, C("div", "Id sección: " + step.I2));
							CambioDescription(entrada, "Cantidad", "---", step.I3);
							break;
						case "UPDATE OBJETO_SECCION":
							C(entrada, C("div", "Id objeto: " + step.I1));
							C(entrada, C("div", "Id sección: " + step.I2));
							CambioDescription(entrada, "Cantidad", step.I3, step.I4);
							break;
						case "DELETE SECCION":
							C(entrada, C("div", "Id sección: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							C(entrada, C("div", "Id almacén: " + step.I2));
							break;
						case "INSERT SECCION":
							C(entrada, C("div", "Id sección: " + step.I1));
							C(entrada, C("div", "Nombre: " + step.T1));
							C(entrada, C("div", "Id almacén: " + step.I2));
							break;
						case "UPDATE SECCION":
							C(entrada, C("div", "Id sección: " + step.I1));
							CambioDescription(entrada, "Nombre", step.T1, step.T2);
							CambioDescription(entrada, "Id almacén", step.I2, step.I3);
							break;
					}
					
					C(transaccion, entradaWrapp);
				}
				
				function Undo() {
					//console.log(step);
					AJAX('php/ajax.php', 'action=rollback-history&step=' + step.ID, function(msg) {
						console.log(msg);
						// Reload page
					}, console.log);
				}
			})(i);
		}
	}, console.log);
	
	function CambioDescription(parentNode, txt, valueBefore, valueAfter) {
		if (valueBefore != valueAfter) C(parentNode, C("div", txt + ": " + valueBefore + " => " + valueAfter));
	}
	
	function Deshacer() {
		
	}
}

DrawObjectList();
</script>



</body>
</html>
