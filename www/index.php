<pre>
Buscador por tags, nombre, sección y almancén

hueco para poner la clave, y quitarla si está puesta

listado de almacenes, con listado de secciones, con listado de objetos. Filtrar con el cuadro de búsqueda
</pre>


<input type="text" placeholder="Búsqueda"/>

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

<div class="clearer"/>

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