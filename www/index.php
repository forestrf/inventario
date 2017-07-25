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
</style>




<pre>
Buscador por tags, nombre, sección y almancén

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda


<input id="Buscador" type="text" placeholder="Búsqueda"/>



<div class="clearer"/>

<div id="inventario"/>



<script>

var lista = [
	{
		"Nombre": "Principal",
		"Tags": [],
		"Secciones":
		[
			{
				"Nombre": "Sección 1",
				"Tags": [],
				"Objetos":
				[
					{
						"Nombre": "Carpetas colgantes",
						"Tags": [],
						"Cantidad": 3
					}
				]
			},
			{
				"Nombre": "Sección 2",
				"Tags": [],
				"Objetos":
				[
					{
						"Nombre": "Disco Duro",
						"Tags": [],
						"Cantidad": 8
					},
					{
						"Nombre": "Lápiz",
						"Tags": [],
						"Cantidad": 8
					}
				]
			}
		]
	}
];






var inventario = document.getElementById("inventario");

for (var i in lista) {
	var almacenJson = lista[i];
	almacenJson["DOM"] = C("div", ["class", "almacen"], C("div", ["class", "nombre"], almacenJson["Nombre"]));
	C(inventario, almacenJson["DOM"]);
	for (var j in almacenJson["Secciones"]) {
		var seccionJson = almacenJson["Secciones"][j];
		seccionJson["DOM"] = C("div", ["class", "seccion"], C("div", ["class", "nombre"], seccionJson["Nombre"]));
		C(almacenJson["DOM"], seccionJson["DOM"]);
		for (var k in seccionJson["Objetos"]) {
			var objetoJson = seccionJson["Objetos"][k];
			objetoJson["DOM"] = C("div", ["class", "objeto"],
				C("div", ["class", "nombre"], objetoJson["Nombre"]),
				C("div", ["class", "cantidad"], objetoJson["Cantidad"])
			);
			C(seccionJson["DOM"], objetoJson["DOM"]);
		}
	}
}

console.log(lista);



// Preparar buscador
var Buscador = document.getElementById("Buscador");
Buscador.onkeyup = function() {
	filterAndSort(Buscador.value);
};

function filterAndSort(text) {
	console.log(text);
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