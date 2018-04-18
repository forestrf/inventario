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
<button onclick="ListarAlmacenes()" class="btn btn-primary">Editar Almacenes</button>


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
				C("table", C("tr", C("td",
					C("img", ["class", "img img-" + objeto.id, "src", GetImagenObjeto(objeto)])
				)))
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
			// Regenerate miniature in the list
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
					return object.minimo < GetCantidad(object);
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
	if (objeto.almacenes.length == 0) return 0;
	return objeto.almacenes.reduce(function(prev, cur) {
		return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
	})["cantidad"];
}
		
function almacenNombreCompleto(elementos, i) {
	if (elementos[i].padre == null) {
		return elementos[i].nombre;
	} else {
		return almacenNombreCompleto(elementos, elementos[i].padre) + " / " + elementos[i].nombre;
	}
}

function edit(UpdateListObject) {
	var cantidadROInput;
	var objetoLocal;
	var updaterDOM;
	var cantidades;
	var popupDOM;
	var forms;
	var tags;
	UpdateListObject(function(newObjetoLocal) {
		objetoLocal = newObjetoLocal;
		cantidad = GetCantidad(objetoLocal);
		cantidadROInput = C("input", ["type", "text", "value", cantidad, "class", "form-control", "readonly", 1], cantidad);
		
		popupDOM = C("div",
			updaterDOM = C("div", ["class", "content", "style", "padding: 1%", "UpdateListObject", UpdateListObject],
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
									C("span", "Cantidad")
								)
							)
						),
						C("div", ["class", "btn btn-primary add", "onclick", function() {
							objetoLocal.almacenes[objetoLocal.almacenes.length] = {cantidad: 0, id_almacen: Object.keys(lista.almacenes)[0]};
							C(cantidades, DrawCantidadInput(objetoLocal.almacenes[objetoLocal.almacenes.length - 1]));
						}], "+ Añadir a otro lugar"),
						C("span", C("span", "Total:"), cantidadROInput)
					)),
					DOMInputAction("update-object-cantidades")
				),
				C("form", ["class", "ajax right_big", "method", "post", "action", "php/ajax.php"],
					C("div", ["class", "has-help"], "Palabras clave", C("div", ["class", "desc"], "Palabras clave para filtrar una búsqueda y encontrar este objeto")),
					C("div", tags = C("input", ["name", "tags", "type", "text", "value", objetoLocal.tags.join(","), "class", "form-control"])),
					DOMInputAction("update-object-tags")
				),
				C("div", ["class", "clear"])
			),
			PieGuardarCancelar("Guardar cambios", "btn-success guarda", guardarCambios, "Cerrar", popups.closePopup, "Borrar", function() { abrirBorrarVentana("confirmBorrar", "btn-danger", C("div", "¿Seguro que quiere borrar este objeto?", C("br")), objetoLocal.onRemove) })
		);
		
		for (var i = 0; i < objetoLocal.almacenes.length; i++) {
			C(cantidades, DrawCantidadInput(objetoLocal.almacenes[i]));
		}
		
		forms = popupDOM.querySelectorAll("form");
		
		for (var i = 0; i < forms.length; i++) {
			C(forms[i], forms[i].submitter = C("input", ["type", "submit", "style", "display: none"]));
			forms[i].onsubmit = update;
			C(forms[i], C("input", ["name", "version", "type", "hidden", "value", objetoLocal.version]));
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
	
	function DrawCantidadInput(almacenObjeto) {
		var almacen = lista.almacenes[almacenObjeto.id_almacen];
		var rId = id_generator();
		var almacenesSelect, cantidadInput;
		var cantidadBlock = C("div", ["class", "cantidad-block"],
			C("div", ["class", "contenido c1"],
				almacenesSelect = C("select", ["name", "id_almacen-" + rId]),
				cantidadInput = C("input", ["name", "cantidad-" + rId, "type", "text", "value", almacenObjeto.cantidad, "class", "form-control", "onchange", function(ev) {
					onPositiveNumberChange(ev);
					almacenObjeto.cantidad = ev.target.value;
					UpdateROCantidad();
				}])
			),
			C("div", ["class", "borrar"],
				C("div", ["class", "btn btn-danger", "onclick", function() {
					objetoLocal.almacenes.splice(Array.prototype.indexOf.call(cantidadBlock.parentNode.childNodes, cantidadBlock) - 1, 1); // Indice 0 ocupado por la cabecera de la tabla
					cantidadBlock.parentNode.removeChild(cantidadBlock);
					UpdateROCantidad();
				}], "X")
			)
		);
		ToOptions(almacenesSelect, lista.almacenes, almacen);
		almacenesSelect.onchange = function(ev) {
			almacen = lista.almacenes[ev.target.value];
			cantidadInput.disabled = Object.keys(lista.almacenes).length == 0;
			if (cantidadInput.disabled) cantidadInput.value = "0";
			
			var event = new Event('change');
			
			almacenObjeto.id_almacen = parseInt(ev.target.value);
			almacen = lista.almacenes[almacenObjeto.id_almacen];
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
			var option = C("option", ["value", elementos[i].id], almacenNombreCompleto(elementos, i));
			if (typeof selected !== "undefined" && selected.id === elementos[i].id)
				option.setAttribute("selected", 1);
			C(parentElement, option);
		}
	}
	
	var guardarPendiente = [];
	function guardarCambios() {
		guardarPendiente = guardarPendiente.concat(Array.from(forms));
		ProcesarPendiente();
	}
	
	function ProcesarPendiente() {
		if (guardarPendiente.length > 0) guardarPendiente.pop().submitter.click();
	}
	
	function update(event) {
		event.preventDefault();
		var target = event.originalTarget !== undefined ? event.originalTarget : event.target;
		var formData = new FormData(target);
		//for (var key of formData.entries()) console.log(key[0] + ', ' + key[1]);
		AJAX('php/ajax.php', formData, function(msg) {
			var json = JSON.parse(msg.response);
			switch (json.STATUS) {
				case "RELOAD":
					TemporalMessage(target, json.STATUS, json.MESSAGE, 5000);
					if (!confirm("No se han guardado los cambios porque se han realizado cambios más recientes. ¿Desea recargar la página para que muestre los cambios más recientes en este objeto?")) break;
					target.parentNode.UpdateListObject(function(newObjetoLocal) {
						popups.closePopup();
						edit(UpdateListObject);
					});
					break;
				case "OK":
					target.parentNode.UpdateListObject(updateForm);
				case "SAME":
				case "ERROR":
					TemporalMessage(target, json.STATUS, json.MESSAGE, 5000);
					if (json.VERSION) {
						var inputsVersion = popupDOM.querySelectorAll("input[name=version]");
						for (var i = 0; i < inputsVersion.length; i++) inputsVersion[i].value = json.VERSION;
					}
					ProcesarPendiente();
					break;
			}
		}, function(msg) {
			alert("ERROR: " + msg.response);
		});
	}
	
	function updateForm(newObjetoLocal) {
		objetoLocal = newObjetoLocal;
		UpdateROCantidad();
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
	return (objeto.imagen === null || 0 === objeto.imagen.length) ? "http://via.placeholder.com/128x128" : "php/ajax.php?action=getfile&id=" + objeto.imagen;
}

function PieGuardarCancelar(guardarStr, guardarClass, guardarFunc, cerrarStr, cerrarFunc, borrarStr, borrarFunc) {
	var pie = C("div", ["class", "botonesAceptarCancelar"]);
	if (guardarStr) C(pie, C("button", ["type", "button", "class", "btn " + guardarClass, "onclick", guardarFunc], guardarStr));
	if (cerrarStr) C(pie, C("button", ["type", "button", "class", "btn btn-default cierra", "onclick", cerrarFunc], cerrarStr));
	if (borrarStr) C(pie, C("div", ["class", "borra"], C("button", ["type", "button", "class", "btn btn-danger borra", "onclick", borrarFunc], borrarStr)));
	return pie;
}

function ListarAlmacenes() {
	var almacenes = shallowClone(lista.almacenes);
	var arbolContainer;
	popups.showPopup(C("div", ["class", "lista-almacenes"],
		arbolContainer = C("div", ["class", "content"]),
		PieGuardarCancelar("Guardar cambios", "btn-success guarda", Guardar, "Cancelar", popups.closePopup)
	));
	
	var u = C("ul");
	C(arbolContainer, u);
	C(arbolContainer, C("button", ["class", "btn btn-primary", "onclick", function() { AddAlmacen("", u); }], "Añadir Almacén"));
	CreateSortable(u);
	
	// No queremos crear secciones iguales a secciones borradas justo ahora para evitar mover cantidades en lugar de borrarlas.
	var almacenesUsados = GetObjectKeysSorted(lista.almacenes);
	
	
	var almacenesCopia = shallowClone(almacenes);
	var almacenesEscritos = {};
	while (Object.keys(almacenesCopia).length > 0) {
		var key = Object.keys(almacenesCopia)[0];
		var alm = almacenesCopia[key];
		delete almacenesCopia[key];
		if (alm.padre == null) {
			almacenesEscritos[alm.id] = DrawAlmacen(alm.id, alm.nombre, u);
		} else {
			if (undefined !== almacenesEscritos[alm.padre]) {
				almacenesEscritos[alm.id] = DrawAlmacen(alm.id, alm.nombre, almacenesEscritos[alm.padre].interior);
			} else {
				almacenesCopia.push(alm);
			}
		}
	}
	
	

	function DrawAlmacen(id, nombre, donde) {
		var input = C("input", ["type", "text", "class", "form-control", "value", nombre/*, "onchange", OnChangeAlmacen, "onkeyup", OnChangeAlmacen*/]);
		var li = C("li", ["class", "almacen", "id", id],
			C("span", ["class", "btn btn-info handle"], "⬍"),
			"ID: ", id, " - ",
			"Nombre: ", // Tmbn podemos poner aquí un Handle, podría quedar mejor
			input
		);
		
		var ul = C("ul");
		CreateSortable(ul);
		C(li, C("button", ["class", "btn btn-primary boton", "onclick", function() { AddAlmacen("", ul); }], "+"));
		C(li, C("button", ["class", "btn btn-danger boton", "onclick", function() { RemoveAlmacenRecursivo(li); }], "X"));
		C(li, ul);
		
		C(donde, li);
		
		return { element: li, interior: ul, input: input };
		
		
		function RemoveAlmacenRecursivo(li) {
			var ul = li.querySelector("ul");
			var children = ul.querySelectorAll(".almacen");
			for (var i = 0; i < children.length; i++) {
				var child = children[i];
				RemoveAlmacen(child);
			}
			RemoveAlmacen(li);
		}
		
		function RemoveAlmacen(li) {
			var id = li.getAttribute("id");
			li.parentNode.removeChild(li)
			delete almacenesEscritos[id];
		}
	}
	
	function AddAlmacen(nombre, where) {
		var keys = GetObjectKeysSorted(almacenesEscritos).concat(almacenesUsados).filter(onlyUnique);
		var id = GetFirstFreeID(keys);
		
		almacenesEscritos[id] = DrawAlmacen(id, nombre, where);
	}
	
	function CreateSortable(ul) {
		Sortable.create(ul, {
			group: "almacenes",
			handle: ".handle"
		});
	}
	
	
	
	function Guardar(forzar) {
		var almacenesAGuardar = [];
		for (var i in almacenesEscritos) {
			var padre = almacenesEscritos[i].element.parentElement.parentElement;
			almacenesAGuardar.push({
				id: almacenesEscritos[i].element.getAttribute("id"),
				padre: padre.nodeName === "LI" ? padre.getAttribute("id") : null,
				nombre: almacenesEscritos[i].input.value
			});
		}
		// sacar DOM a lista para enviar
		var post = 'action=update-almacenes&almacenes=' + JSON.stringify(almacenesAGuardar);
		if (typeof forzar !== undefined) post += "&forzar=" + (true === forzar ? "OK" : "NO");
		AJAX('php/ajax.php', post, function(msg) {
			var json = JSON.parse(msg.response);
			if (json.STATUS === "ASK") {
				var contenedor = C("div");
				for (var i in json.MESSAGE) {
					C(contenedor, C("div", ["class", "aBorrarNombre"], "Objeto: ", C("span", ["class", "var"], lista.objetos[i].nombre)));
					for (var j in json.MESSAGE[i]) {
						C(contenedor, C("div", ["class", "aBorrarAlmacenCantidad"],
							"Almacén: ", C("span", ["class", "var"], almacenNombreCompleto(lista.almacenes, json.MESSAGE[i][j].id_almacen)),
							", Stock: ", C("span", ["class", "var"], json.MESSAGE[i][j].cantidad)
						));
					}
				}
				abrirBorrarVentana("confirmBorrar", "btn-danger", C("div", C("div", "Se van a borrar almacenes que contienen objetos. El Stock de los siguientes objetos se borrará:"), contenedor), function() { popups.closePopup(); Guardar(true); });
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
}

function SetupBusquedasPreparadas() {
	AJAX('php/ajax.php?action=getbusquedas', null, function(msg) {
		var response = JSON.parse(msg.response);
		if (!response) response = { value: "[]", version: 0 };
		var busquedasArr = JSON.parse(response.value);
		var version = response.version;
		
		BTN_BUSQUEDAS_PREPARADAS.style.display = "";
		BTN_BUSQUEDAS_PREPARADAS.onclick = popupBusquedasPreparadas;
		
		function popupBusquedasPreparadas() {
			var busquedas_ul = C("ul", ["class", "busquedas"]);
			for (var i = 0; i < busquedasArr.length; i++) {
				AddBusquedapreparada(busquedasArr[i].nombre, busquedasArr[i].busqueda);
			}
			var contenedorBusquedas = C("div", ["class", "content busquedas-contenedor ajax"],
				busquedas_ul,
				C("button", ["class", "btn btn-default", "onclick", add], "Añadir búsqueda")
			);
			popups.showPopup(C("div",
				contenedorBusquedas,
				PieGuardarCancelar("Guardar cambios", "btn-success guarda", guardar, "Cerrar", popups.closePopup)
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
				
				AJAX('php/ajax.php', 'action=update-busquedas&version=' + version + '&busquedas=' + encodeURIComponent(JSON.stringify(busquedas)), function(msg) {
					var json = JSON.parse(msg.response);
					switch (json.STATUS) {
						case "OK":
							version = json.VERSION;
							break;
						case "RELOAD":
							SetupBusquedasPreparadas();
							break;
					}
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
						C("table", ["class", "editbusqueda content"],
							C("tr",
								C("td", "Nombre del botón"),
								C("td", nom = C("input", ["class", "form-control", "value", nombre]))
							),
							C("tr",
								C("td", "Texto a buscar"),
								C("td", bus = C("input", ["class", "form-control", "value", busqueda]))
							)
						),
						PieGuardarCancelar("Aceptar", "btn-success guarda", aceptar, "Cancelar", popups.closePopup)
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
}
SetupBusquedasPreparadas();

DrawObjectList();
</script>



</body>
</html>
