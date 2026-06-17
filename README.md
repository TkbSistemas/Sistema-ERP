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
### 3. Administración RH
### 4. Comercial
### 5. Costos y Presupuestos
### 6. Licitaciones
### 7. Ejecución de Proyectos
### 8. Almacén

---

## 1. MÓDULO DE INICIO DE SESION

### 1.1 Obtener inicio de sesión

**Controller**: `AuthController`

**Request**:
- `username`: Nombre de usuario de la cuenta (Primer Nombre + Inicial Apelido Paterno + Inicial Apelido Materno)
- `password`: Contraseña de la cuenta.
  
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

### 1.2 Obtener cierre de sesión

**Controller**: `AuthController`
  
**Lógica Esperada**:
- Destruye la sesión actual.
- Redirecciona al Login del sistema.
- Registra movimiento en la Bitacora del Sistema.

### 1.3 Recuperación de contraseñas - No implementado

**Controller**: `AuthController`

---

## 2. MÓDULO DE INVENTARIO
## 3. MÓDULO DE ADMINISTTRACIÓN RH

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

## 4. MÓDULO DE COMERCIAL
## 5. MÓDULO DE COSTOS Y PRESUPUESTOS
## 6. MÓDULO DE LICITACIONES
## 7. MÓDULO DE EJECUCIÓN DE PROYECTOS
## 8. MÓDULO DE ALMACÉN
