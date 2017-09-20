<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script src="js/ajax.js"></script>
<script>
crel2=function(){var c=arguments,b=c[0],g=c.length,b="string"===typeof b?document.createElement(b):b;if(1===g)return b;var a=c[1],e=2;if(a instanceof Array)for(var d=a.length,f;d;)switch(typeof(f=a[--d])){case "string":case "number":b.setAttribute(a[--d],f);break;default:b[a[--d]]=f}else--e;for(;g>e;)a=c[e++],"object"!==typeof a&&"function"!==typeof a&&(a=document.createTextNode(a)),b.appendChild(a);return b};

C = crel2;
</script>

<style>
.almacen, .seccion, .objeto {
	float: left;
	border: 1px solid #000;
	margin: 2px;
	background-color: rgba(0, 0, 0, 0.1);
}
.objeto .titulo {
	background-color: #000;
	color: #fff;
}
.nombre {
	display: inline;
}
.descripcion {
	margin-left: 20px;
	display: inline;
	font-size: 0.7em;
	opacity: 0.7;
}
.clearer {
	clear: both;
}
.objeto.alerta {
	background-color: #F00;
	color: #fff;
}
.objeto > div {
    padding: 1px 2px 1px 2px;
}
.objeto > .titulo {
    padding: 5px;
}
</style>




<pre>
Buscador por tags, nombre, sección y almancén

Clicar en un botón (como número o un tag) abre un popup que da funcionalidad (cambiar cantidad, quitar el tag o filtrar usando ese tag, etc)

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda

</pre>


<input id="Buscador" type="text" placeholder="Búsqueda"/>



<div class="clearer"></div>

<div id="inventario"></div>
<div class="clearer"></div>



<script>


AJAX('php/ajax.php?action=getinventario', null, function(x) {
	var lista = JSON.parse(x.responseText);
	
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










function DrawInventory(lista) {
	var contenedor = C("div");
	for (var i in lista) {
		var almacen = lista[i];
		almacen["DOM"] = C("div", ["class", "almacen"], C("div",
			C("div", ["class", "nombre"], almacen["nombre"]),
			C("div", ["class", "descripcion"], almacen["descripcion"])
		));
		C(contenedor, almacen["DOM"]);
		for (var j in almacen["secciones"]) {
			var seccion = almacen["secciones"][j];
			seccion["DOM"] = C("div", ["class", "seccion"], C("div",
				C("div", ["class", "nombre"], seccion["nombre"]),
				C("div", ["class", "descripcion"], seccion["descripcion"])
			));
			C(almacen["DOM"], seccion["DOM"]);
			for (var k in seccion["objetos"]) {
				var objeto = seccion["objetos"][k];
				var objetoClass = "objeto";
				objetoClass = GetMinimoAlert(objeto, objetoClass);
				var tagsDom;
				objeto["DOM"] = C("div", ["class", objetoClass],
					C("div", ["class", "titulo"],
						C("div", ["class", "nombre"], C("button", objeto["nombre"])),
						C("div", ["class", "descripcion"], objeto["descripcion"])
					),
					C("div", ["class", "cantidad"],
						"Cantidad: ",
						objeto["DOM_CNT"] = C("Button", objeto["cantidad"])
					),
					C("div", ["class", "minimo"],
						"Mínimo: ", C("Button", objeto["minimo_alerta"])
					),
					C("div", ["class", "tags"], "Tags: ",
						tagsDom = C("span", ["class", "tags-list"])
					)
				);
				
				for (var l = 0; l < objeto["tags"].length; l++) {
					C(tagsDom, C("Button", objeto["tags"][j]));
				}
				C(tagsDom, C("Button", "+"));
				
				C(seccion["DOM"], objeto["DOM"]);
			}
			C(seccion["DOM"], C("div", ["class", "objeto"], C("Button", "+ Objeto")));
		}
		C(almacen["DOM"], C("div", ["class", "seccion"], C("Button", "+ Sección")));
	}
	C(contenedor, C("div", ["class", "almacen"], C("Button", "+ Almacén")));
	return contenedor;
}

function GetMinimoAlert(json, className) {
	var hayMinimo = undefined !== json["minimo_alerta"]
	return hayMinimo && json["cantidad"] < json["minimo_alerta"] ?  className + " alerta" : className.split(" alerta").join("");
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


