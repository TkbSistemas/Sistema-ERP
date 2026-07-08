# SITEMA ERP - TAKAB SYSTEMS

## Descripción General
Se busca craer un sitema ERP (Enterprise Resource Planning) con el fin de centralizar las operaciones de la empresa en un mismo sistema, usando un enfoque modular para cada area de trabajo, 
asigando roles con sus respectivas funcionalidades.

## Índice
1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Convenciones de Endpoints](#convenciones-de-endpoints)
3. [Módulos del Sistema](#módulos-del-sistema)
4.
5.
6.

---

## Arquitectura del Sistema

### Base de Datos Local para Desarrollo
- **erp_takab**: `Copia de la base de datos diseñada para pruebas en fase de desarrollo`

### Base de Datos Externa
- **vwEmpleadosTAKAB**: `Vista no editable de los empleados de TAKAB (No implementada aún)`
- **BDERPTAKAB**: `Base de datos maestra para el sistema alojada en el servidor`

---

## Convenciones de Endpoints

### Validación
- **Sesión Activa**: Toda página valida si existe una sesión valida activa, de no ser así redirige al Login del sistema.
- **Vista de Errores**: Toda página evita redireccionar a vistas de errores, se sustituye por una alerta de advertencia.

---

## Módulos del Sistema

### 1. Inicio de Sesión
### 2. Inventario
### 3. Almacén
### 4. Compras
### 5. Proyectos
### 6. Administración RH
---

## 1. MÓDULO DE INICIO DE SESIÓN

### 1.1 Obtener Inicio de Sesión

**Controller**: `AuthController`

**View**: `login`

**DB**: `usuarios`

**Request**:
- `username`: Nombre de usuario de la cuenta (Primer Nombre + Inicial Apelido Paterno + Inicial Apelido Materno)
- `password`: Contraseña de la cuenta.
  
**Lógica Esperada**:
- Valida las credenciales para el incio de sesión.
- Implementa Token de Seguridad.
- Registra movimiento en la Bitacora del Sistema.
- Extrae los datos de la cuenta del usuario desde la DB.
- Asigna y redirecciona al Dashboard según el rol del usuario.

**Response**:
```json
{
  "id": 101,
  "nombre": "Juan Perez Dominguez",
  "rol": "Administrador"
}
```

### 1.2 Obtener Cierre de Sesión

**Controller**: `AuthController`
  
**Lógica Esperada**:
- Destruye la sesión actual.
- Redirecciona al Login del sistema.
- Registra movimiento en la Bitacora del Sistema.

### 1.3 Recuperación de contraseñas - No implementado

**Controller**: `AuthController`

---

## 2. MÓDULO DE INVENTARIO

### 2.1 Obtener Dashboard del Inventario

**Controller**: `InventarioController`

**View**: `dashboard_inventario`

**DB**: `inventario`

**Request**:
- `role`: Rol de usuario que está ingresando.
  
**Lógica Esperada**:
- Muestra tarjetas de resumen (Productos Registrados,Stock Bajo).
- Muestra tarjeta que suma el valor total del inventario solo cuando el rol es Administrador.
- Despliega caja de búsqueda con filtros para el inventario.
- Lista de el inventario de acuerdo a los filtros elegidos, incluye paginación.
- Al momento de listar la tabla cuenta con el campo "valor" solo cuando el rol es Administrador.


### 2.2 Agregar al Catálogo de Productos

**Controller**: `InventarioController`

**View**: `catalogo_productos`

**DB**: `inventario`
  
**Lógica Esperada**:
- Despliega caja de búsqueda con filtros para el catalogo.
- Lista de el catalogo de acuerdo a los filtros elegidos, incluye paginación.
- Implementa acciones en la lista (Editar, Desactivar y Eliminar).
- Incluye sistema de importación y exportación a formato csv.
- Despliega formulario completo para registrar un nuevo tipo de producto (Información General, Dimensiones y Peso, Inventario y Costos, Imagenes y Archivos).


### 2.3 Obtener Rotación de Inventario

**Controller**: `InventarioController`

**View**: `reportes_rotacion`

**DB**: `inventario`

**Request**:
- `fecha_inicio`: Fecha a partir de la cual se toman los movimientos (Default: Inicio de mes)
- `fecha_fin`: Fecha hasta la cual se toman los movimientos (Default: Fin de mes).
  
**Lógica Esperada**:
- Despliega caja de búsqueda con filtros para los movimientos.
- Lista solo de los productos con movimiento de acuerdo a los filtros elegidos, incluye paginación.


### 2.4 Obtener Reportes de Inventario

**Controller**: `InventarioController`

**View**: `reportes_inventario`

**Request**:
- `role`: Rol de usuario (Esta función es exclusiva para Administrador).
- `fecha_inicio`: Fecha a partir de la cual se toman los movimientos (Default: Inicio de mes)
- `fecha_fin`: Fecha hasta la cual se toman los movimientos (Default: Día Actual).
  
**Lógica Esperada**:
- Despliega caja de filtros para las estadisticas.
- Muestra con graficas de barras un historico del valor del inventario.
- Muestra con grafica de pastel como se reparte el valor del inventario entre las distintas categorías.


### 2.5 Inventario de Merma

**Controller**: `InventarioController`

**View**: `inventario_merma`

**DB**: `inventario_merma`
  
**Lógica Esperada**:
- Muestra tarjetas de resumen (Productos Registrados).
- Despliega caja de búsqueda con filtros para el inventario.
- Lista de el inventario de acuerdo a los filtros elegidos, incluye paginación.


## 3. MÓDULO DE ALMACÉN

### 3.1 Obtener Dashboard del Almacén

**Controller**: `AlmacenController`

**View**: `dashboard_almacen`

**Request**:
- `role`: Rol de usuario que está ingresando.

**Lógica Esperada**:
- Muestra tarjetas de resumen (Solicitudes de Material Pendientes, Ordenes de Compra, Stock Bajo).
- Lista los ultimos n movimientos del inventario ya sea entradas o salidas.
- Despliega alertas del sistema, de momento solo las correspondientes al stock bajo y prestamos vencido.


### 3.2 Obtener Solicitudes de Material

**Controller**: `AlmacenController`

**View**: `solicitudes_material`

**DB**: `solicitudes_material`

**Request**:
- `fecha_inicio`: Fecha a partir de la cual se toman los movimientos (Default: Inicio de mes)
- `fecha_fin`: Fecha hasta la cual se toman los movimientos (Default: Día Actual).

**Lógica Esperada**:
- Muestra tarjetas de resumen (Solicitudes de Material Pendientes).
- Despliega caja de búsqueda con filtros para las solicitudes.
- Lista las últimas n solicitudes de material pendientes.
- Lista historial de todas las solicitudes con su estado (Entregadas/Rechazadas).

**Response**:
```json
{
  "num_pendientes": 4,
  "num_mes": 24,
  "pendientes" = [
    "nombre_solicitante": "Javier Lopez",
    "folio": "PBUAP-0215",
    "fecha_solicitud": "01-01-1999",
    "materiales" = [
       "nombre_producto": "Desarmador Plano",
        "cantidad": 1 
    ]
  ]
}
```


### 3.3 Crear Entrada de Material

**Controller**: `AlmacenController`

**View**: `registrar_entrada`

**Lógica Esperada**:
- Despliega formulario para ingresar folio de una orden de compra.
- Marca como completa o incompleta la lista por cada producto.
- Si la orden llegó completa se suma al inventario.
- Si la orden llegó incompleta no se registra y regresa a compras.


### 3.4 Crear Entrada Rápida

**Controller**: `AlmacenController`

**View**: `entrada_rapida`

**Lógica Esperada**:
- Despliega formulario para registrar lista de productos.
- NO necesita que exista orden de compra previa.
- Suma el listado al sock del inventario.

  
### 3.5 Obtener Préstamos de Herramientas

**Controller**: `AlmacenController`

**View**: `prestamos_herramientas`

**DB**: `prestamos_herramientas`

**Request**:
- `fecha_inicio`: Fecha a partir de la cual se toman los movimientos (Default: Inicio de mes)
- `fecha_fin`: Fecha hasta la cual se toman los movimientos (Default: Día Actual).

**Lógica Esperada**:
- Muestra tarjetas de resumen (Prestamos Activos, Pendientes y Vencidos).
- Despliega caja de búsqueda con filtros para las solicitudes.
- Lista los prestamos activos, con fecha vigente.
- Lista los prestamos pendientes de autorizar y entregar.
- Lista los prestamos vencidos, con fecha vencida y sin regresar.
- Lista historial de todas las solicitudes con su estado (Entrgada/Rechazadas).
- Incluye la acción de extender el plazo en el caso de los prestamos activos.


### 3.6 Obtener Cajas de Herramientas

**Controller**: `AlmacenController`

**View**: `cajas_herramientas`

**DB**: `cajas_herramientas`

**Lógica Esperada**:
- Muestra tarjetas de resumen (Cajas Incompletas, Cajas Disponibles, Ultimo Inventario).
- Lista todas las cajas de herramientas, muestra estatus, piezas faltantes, categoria.
- Genera un inventario de los elementos que contiene cada caja, dando opción de imprimir formato.
  

### 3.7 Crear Baja de Productos

**Controller**: `AlmacenController`

**View**: `baja_productos`

**Lógica Esperada**:
- Muestra formulario para buscar/seleccionar los materiales a dar de baja (Nombre, Cantidad, Razón, Fecha, etc).
- Genera formato de impresión con la lista de seleccionados (Campo de firma de autorización).

### 3.8 Solicitar Reabastecimiento

**Controller**: `AlmacenController`

**View**: `solicitar_stock`

**Lógica Esperada**:
- Muestra formulario para buscar/seleccionar los materiales de los que solicitar stock.
- Crea orden de compra para autorizar el reabastecimiento del almacen.

### 3.9 Crear Etiquetas

**Controller**: `AlmacenController`

**View**: `etiquetas`

**Lógica Esperada**:
- Muestra formulario para buscar/seleccionar los materiales a imprimir su etiqueta.
- Genera formato de impresión con la lista de etiquetas seleccionadas, con el formato según se necesite.


## 4. MÓDULO DE COMPRAS

### 4.1 Dasboard Compras

**Controller**: `ComprasController`

**View**: `dashboard_compras`

**Lógica Esperada**:
- Comprueba que el rol del usuario coincida con el módulo.
- Muestra Tarjetas Resumen de las actividades (Proyectos Activos, Ordenes de Compra Pendientes, Cotizaciones Pendientes)
- Lista brevemente las ultimas ordenes de compra generadas.

### 4.2 Crear Cotización

**Controller**: `ComprasController`

**View**: `crear_cotizacion`

**DB**: `cotizaciones`

**Lógica Esperada**:
- Despliega formulario para seleccionar la cotización, entre aquellas que se encuentran activas o bien crear una nueva.
- Muestra formulario en tabla estilo excel para llenar datos de cotizacion.
- Permite guardar con estado pendiente, terminada, cancelada o completa.
- Opcionalmente se puede exportar a formato de excel o imprimir los datos.

### 4.3 Listar Ordenes de Compra

**Controller**: `ComprasController`

**View**: `ordenes_compra`

**DB**: `ordenes_compra`

**Lógica Esperada**:
- Despliega formulario de busqueda por folio y por fechas.
- Muestra ordenes de compra pendientes de aprobacion.
- Lista historico de ordenes de compra SOLO aquellas realizadas por el usuario,
- Permite cancelar o editar aquellas con estatus pendiente.

### 4.4 Crear Orden de Compra

**Controller**: `ComprasController`

**View**: `crear_compra`

**DB**: `ordenes_compra`

**Lógica Esperada**:
- Permite asociar una cotizacion existente.
- Lista todos los elementos con los datos de la cotización.
- Permite quitar o añadir elementos a la lista.
- Guarda la orden de compra en espera de autoizacion
- Genera formato imprimible de la orden

### 4.3 Listar Ordenes de Compra

**Controller**: `ComprasController`

**View**: `ordenes_compra`

**Lógica Esperada**:
- Despliega formulario de busqueda por folio y por fechas.
- Muestra ordenes de compra pendientes de aprobacion.
- Lista historico de ordenes de compra SOLO aquellas realizadas por el usuario,
- Permite cancelar o editar aquellas con estatus pendiente.

### 4.5 Agregar al Catálogo de Productos

**Controller**: `InventarioController`

**View**: `catalogo_productos`

**DB**: `inventario`
  
**Lógica Esperada**:
- Despliega caja de búsqueda con filtros para el catalogo.
- Lista de el catalogo de acuerdo a los filtros elegidos, incluye paginación.
- Implementa acciones en la lista (Editar, Desactivar y Eliminar).
- Incluye sistema de importación y exportación a formato csv.
- Despliega formulario completo para registrar un nuevo tipo de producto (Información General, Dimensiones y Peso, Inventario y Costos, Imagenes y Archivos).



## 5. MÓDULO DE PROYECTOS

## 6. MÓDULO DE ADMINISTRACIÓN RH

### 3.1 Crear Proyecto Nuevo

**Controller**: `AdminController`

**Request**:
- `nom_proyecto`: Nombre de usuario de la cuenta (Primer Nombre + Inicial Apelido Paterno + Inicial Apelido Materno)
- `prioridad`: Contraseña de la cuenta.
  
**Lógica Esperada**:
- Valida las credenciales para el incio de sesión.
- Implementa Token de Seguridad.
- Registra movimiento en la Bitacora del Sistema.
- Extrae los Datos de la cuenta del usuario desde la DB.
- Asigna y redirecciona al Dashboard según el rol del usuario.

**Response**:
```json
{
  "id": 101,
  "nombre": "Juan Perez Dominguez",
  "rol": "Administrador"
}
```


