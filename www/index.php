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
</style>




<pre>
Buscador por tags, nombre, sección y almancén

Clicar en un botón (como número o un tag) abre un popup que da funcionalidad (cambiar cantidad, quitar el tag, etc)

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda

</pre>


<input id="Buscador" type="text" placeholder="Búsqueda"/>



<div class="clearer"></div>

<div id="inventario"></div>



<script>


AJAX('php/ajax.php?action=getinventario', null, function(x) {
	var lista = JSON.parse(x.responseText);
	
	// Dibujar toda la lista en el DOM
	var inventario = document.getElementById("inventario");
	C(inventario, DrawInventory(lista));
	
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
		var almacenJson = lista[i];
		almacenJson["DOM"] = C("div", ["class", "almacen"], C("div",
			C("div", ["class", "nombre"], almacenJson["nombre"]),
			C("div", ["class", "descripcion"], almacenJson["descripcion"])
		));
		C(contenedor, almacenJson["DOM"]);
		for (var j in almacenJson["contenido"]) {
			var seccionJson = almacenJson["contenido"][j];
			seccionJson["DOM"] = C("div", ["class", "seccion"], C("div",
				C("div", ["class", "nombre"], seccionJson["nombre"]),
				C("div", ["class", "descripcion"], seccionJson["descripcion"])
			));
			C(almacenJson["DOM"], seccionJson["DOM"]);
			for (var k in seccionJson["contenido"]) {
				var objetoJson = seccionJson["contenido"][k];
				var objetoClass = "objeto";
				var hayMinimo = undefined !== objetoJson["minimo_alerta"];
				objetoClass = GetMinimoAlert(objetoJson, objetoClass);
				var tagsDom;
				objetoJson["DOM"] = C("div", ["class", objetoClass],
					C("div", ["class", "titulo"],
						C("div", ["class", "nombre"], objetoJson["nombre"]),
						C("div", ["class", "descripcion"], objetoJson["descripcion"])
					),
					C("div", ["class", "tags"], "Tags: ",
						tagsDom = C("span", ["class", "tags-list"])
					),
					C("div", ["class", "cantidad"],
						"Cantidad: ",
						objetoJson["DOM_CNT"] = C("Button", objetoJson["cantidad"])
					)
				);
				
				for (var l = 0; l < objetoJson["tags"].length; l++) {
					C(tagsDom, C("Button", objetoJson["tags"][j]));
				}
				C(tagsDom, C("Button", "+"));
				
				if (hayMinimo) {
					C(objetoJson["DOM"], 
						C("div", ["class", "minimo"],
							"Mínimo: ", C("Button", objetoJson["minimo_alerta"])
						)
					);
				}
				
				C(seccionJson["DOM"], objetoJson["DOM"]);
			}
		}
	}
	return contenedor;
}

function GetMinimoAlert(json, className) {
	var hayMinimo = undefined !== json["minimo_alerta"]
	return hayMinimo && json["cantidad"] < json["minimo_alerta"] ?  className + " alerta" : className.split(" alerta").join("");
}





</script>
