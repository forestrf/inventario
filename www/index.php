<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script src="js/ajax.js"></script>
<link rel="stylesheet" type="text/css" href="css/main.css"> 
<script>
crel2=function(){var c=arguments,b=c[0],g=c.length,b="string"===typeof b?document.createElement(b):b;if(1===g)return b;var a=c[1],e=2;if(a instanceof Array)for(var d=a.length,f;d;)switch(typeof(f=a[--d])){case "string":case "number":b.setAttribute(a[--d],f);break;default:b[a[--d]]=f}else--e;for(;g>e;)a=c[e++],"object"!==typeof a&&"function"!==typeof a&&(a=document.createTextNode(a)),b.appendChild(a);return b};

C = crel2;
</script>





<pre>
Buscador por tags, nombre, sección y almancén

Clicar en un botón (como número o un tag) abre un popup que da funcionalidad (cambiar cantidad, quitar el tag o filtrar usando ese tag, etc)

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda

</pre>


<input id="Buscador" type="text" placeholder="Búsqueda"/>

<div id="inventario"></div>
<div class="clearer"></div>

<button>+ Objeto</button>

<div class="popup" id="popup" style="display:none">
	<div class="bg" id="bg" onclick="closePopup()"></div>
	<div class="msg" id="msg">
		jojojojo
	</div>
	<div class="close"><button onclick="closePopup()">X</botton></div>
</div>



<script>


AJAX('php/ajax.php?action=getinventario', null, function(x) {
	var lista = JSON.parse(x.responseText);
	r = lista;
	
	// Dibujar toda la lista en el DOM
	C(document.getElementById("inventario"), DrawInventory(lista));
	C(document.getElementById("tagMatrix"), DrawTagMatrix(lista));
	
	// Preparar buscador
	var Buscador = document.getElementById("Buscador");
	Buscador.onkeyup = function() {
		FilterSearch.process(Buscador.value, lista,
			function(DOM) { DOM.style.display = "unset"; },
			function(DOM) { DOM.style.display = "none"; }
		);
	};
	
}, console.log);





function closePopup() {
	document.getElementById("popup").style = "display:none";
	document.getElementById("msg").innerHTML = "";
}

function showPopup(contentsDOM) {
	closePopup();
	document.getElementById("popup").style = "";
	C(document.getElementById("msg"), contentsDOM);
}





function nombreDescripcion(json) {
	return C("div",
		C("div", ["class", "nombre"], json["nombre"]),
		C("div", ["class", "descripcion"], json["descripcion"])
	);
}

function DrawInventory(lista) {
	var contenedor = C("div");
	
	for (var i in lista.objetos) {
		C(contenedor, DrawObjeto(lista.objetos[i], lista));
	}
	
	return contenedor;
}

function DrawObjeto(objeto, lista) {
	var cantidad = GetCantidad(objeto);
	var tags;
	objeto["DOM"] = C("button", ["class", "objeto", "onclick", edit],
		C("div", ["class", "titulo"],
			C("div", ["class", "nombre"], objeto["nombre"]),
			C("div", ["class", "descripcion"], objeto["descripcion"])
		),
		C("div", ["class", "cantidad"], "Cantidad: ", cantidad),
		C("div", ["class", "minimo"], "Mínimo: ", objeto["minimo_alerta"]),
		C("div", ["class", "tags"], "Tags: ", tags = C("span", ["class", "tags-list"]))
	);
	for (var l in objeto["tags"]) C(tags, objeto["tags"][l]);
	if (objeto["tags"].length === 0) C(tags, "---");
	
	objeto["DOM"]["objeto"] = objeto;
	var cb = cantidad < parseInt(objeto["minimo_alerta"]) ? AddClass : RemoveClass;
	cb(objeto["DOM"], "alerta");
	
	
	return objeto["DOM"];

	function edit() {
		var popupDOM = C();
		/*
		for (var s in objeto["secciones"]) {
			console.log(objeto["secciones"][s]);
			var seccion = lista.secciones[objeto["secciones"][s]["id_seccion"]];
			var almacen = lista.almacenes[seccion.id_almacen];
			C(cnt, C("Button", almacen.nombre + " / " + seccion.nombre, ": ", objeto["secciones"][s]["cantidad"]));
		}
		*/
		
		showPopup(popupDOM);
	}
}

function GetCantidad(objeto) {
	return objeto["secciones"].reduce(function(prev, cur) {
		return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
	})["cantidad"];
}

function AddClass(dom, className) {
	dom.className += " " + className;
}
function RemoveClass(dom, className) {
	dom.className = dom.className.split(" " + className).join("");
}





function GetTagList(lista) {
	var allTags = [];
	var allTagsTmp = {};
	for (var a in lista) {
		var secciones = lista[a]["secciones"];
		for (var s in secciones) {
			var objetos = secciones[s]["objetos"];
			for (var o in objetos) {
				var tags = objetos[o]["tags"];
				for (var l in tags) {
					allTagsTmp[tags[l]] = true;
				}
			}
		}
	}
	for (var i in allTagsTmp) allTags.push(i);
	return allTags;
}

function DrawTagMatrix(lista) {
	var allTags = GetTagList(lista);
	
	
	
	var contenedor = C("table", ["border", 1]);
	var tr0 = C("tr");
	C(contenedor, tr0);
	C(tr0, C("th", ["colspan", 3], "Objetos"));
	C(tr0, C("th", ["colspan", allTags.length], "Tags"));
	
	var tr1 = C("tr");
	C(contenedor, tr1);
	C(tr1, C("td", "Almacén"));
	C(tr1, C("td", "Sección"));
	C(tr1, C("td", "Objeto"));
	for (var i in allTags) {
		C(tr1, C("td", allTags[i]));
	}
	
	for (var a in lista) {
		var almacen = lista[a];
		for (var s in almacen["secciones"]) {
			var seccion = almacen["secciones"][s];
			for (var o in seccion["objetos"]) {
				var objeto = seccion["objetos"][o];
				
				var trn = C("tr");
				C(contenedor, trn);
				C(trn, C("td", almacen["nombre"]));
				C(trn, C("td", seccion["nombre"]));
				C(trn, C("td", objeto["nombre"]));
				
				for (var t in allTags) {
					var cb = C("input", [
						"type", "checkbox",
						"checked", objeto["tags"].indexOf(allTags[t]) !== -1,
						"onclick", function(obj) {
							//console.log(obj);
							//console.log(obj.target);
							//console.log(obj.target.objeto);
							console.log(obj.target.tag);
						}
					]);
					cb["objeto"] = objeto;
					cb["tag"] = allTags[t];
					C(trn, cb);
				}
			}
		}
	}
	return contenedor;
}









</script>


<div>
Matriz con todos los objetos y tags
<div id="tagMatrix"></div>
</div>


