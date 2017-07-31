<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script>
crel2=function(){var c=arguments,b=c[0],g=c.length,b="string"===typeof b?document.createElement(b):b;if(1===g)return b;var a=c[1],e=2;if(a instanceof Array)for(var d=a.length,f;d;)switch(typeof(f=a[--d])){case "string":case "number":b.setAttribute(a[--d],f);break;default:b[a[--d]]=f}else--e;for(;g>e;)a=c[e++],"object"!==typeof a&&"function"!==typeof a&&(a=document.createTextNode(a)),b.appendChild(a);return b};

C = crel2;
</script>

<style>
.almacen, .seccion, .objeto {
	float: left;
	border: 1px solid;
	margin: 2px;
	padding: 1px;
	background-color: rgba(0, 0, 0, 0.1);
}
.nombre {
	display: block;
}
.clearer {
	clear: both;
}
.objeto.alerta {
	color: #F00;
}
</style>




<pre>
Buscador por tags, nombre, sección y almancén

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda

</pre>

<input id="Buscador" type="text" placeholder="Búsqueda"/>



<div class="clearer"></div>

<div id="inventario"></div>



<script>

var lista = [
	{
		"Nombre": "Principal",
		"contenido":
		[
			{
				"Nombre": "Papelería",
				"contenido":
				[
					{
						"Nombre": "Carpetas colgantes",
						"Tags": ["Carpetas", "colgantes"],
						"Cantidad": 3,
						"Minimo": 4
					}
				]
			},
			{
				"Nombre": "Sección 2",
				"contenido":
				[
					{
						"Nombre": "Disco Duro",
						"Tags": [],
						"Cantidad": 8
					},
					{
						"Nombre": "Lápiz",
						"Tags": [],
						"Cantidad": 8,
						"Minimo": 3
					}
				]
			}
		]
	}
];
console.log(lista);






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





function DrawInventory(lista) {
	var contenedor = C("div");
	for (var i in lista) {
		var almacenJson = lista[i];
		almacenJson["DOM"] = C("div", ["class", "almacen"], C("div", ["class", "nombre"], almacenJson["Nombre"]));
		C(contenedor, almacenJson["DOM"]);
		for (var j in almacenJson["contenido"]) {
			var seccionJson = almacenJson["contenido"][j];
			seccionJson["DOM"] = C("div", ["class", "seccion"], C("div", ["class", "nombre"], seccionJson["Nombre"]));
			C(almacenJson["DOM"], seccionJson["DOM"]);
			for (var k in seccionJson["contenido"]) {
				var objetoJson = seccionJson["contenido"][k];
				var objetoClass = "objeto";
				var hayMinimo = undefined !== objetoJson["Minimo"]
				if (hayMinimo && objetoJson["Cantidad"] < objetoJson["Minimo"]) {
					objetoClass += " alerta";
				}
				objetoJson["DOM"] = C("div", ["class", objetoClass],
					C("div", ["class", "nombre"], objetoJson["Nombre"]),
					C("div", ["class", "cantidad"],
						"Cantidad: ",
						objetoJson["DOM_CNT"] = C("span", objetoJson["Cantidad"]),
						C("br"),
						objetoJson["CNT"] = C("input", ["type", "text", "placeholder", "0", "size", "3"]),
						objetoJson["ADD"] = C("button", "Aumentar"),
						objetoJson["SUB"] = C("button", "Reducir")
					)
				);
				if (hayMinimo) {
					C(objetoJson["DOM"], 
						C("div", ["class", "minimo"],
							"Mínimo: " + objetoJson["Minimo"]
						)
					);
				}
				
				// Acción botones
				objetoJson["ADD"].onclick = (function(objetoJson) { return function() {
					AlterQuantity(objetoJson, parseInt(objetoJson.CNT.value));
				}})(objetoJson);
				objetoJson["SUB"].onclick = (function(objetoJson) { return function() {
					AlterQuantity(objetoJson, -parseInt(objetoJson.CNT.value));
				}})(objetoJson);
				
				C(seccionJson["DOM"], objetoJson["DOM"]);
			}
		}
	}
	return contenedor;
}



function AlterQuantity(json, delta) {
	json.Cantidad += delta;
	json.DOM_CNT.innerHTML = json.Cantidad;
	// Refrescar alerta y guardar en base de datos
}





</script>
<!--
<div class="almacen">
	<div class="nombre">Almacén</div>
	
	<div class="seccion">
	<div class="nombre">Sección</div>
	
		<div class="objeto">
			<div class="nombre">Nombre objeto</div>
			<div class="imagen">Imagen</div>
			<div class="cantidad">Cantidad</div>
		</div>
	</div>
</div>
-->