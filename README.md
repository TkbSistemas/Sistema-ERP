# SITEMA ERP - TAKAB SYSTEMS

## Descripción General
Se busca craer un sitema ERP (Enterprise Resource Planning) con el fin de centralizar las operaciones de la empresa en un mismo sistema, usando un enfoque modular para cada area de trabajo, 
asigando roles con sus respectivas funcionalidades.

## Índice
1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Convenciones de Endpoints](#convenciones-de-endpoints)
3. [Módulos del Sistema](#módulos-del-sistema)
4
5
6

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
### 2. Administrador
### 3. Ventas
### 3. Proyectos
### 4. Almacen
### 5. Inventario

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
## 3. MÓDULO DE COMPRAS
## 4. MÓDULO DE ALMACEN
## 5. MÓDULO DE CAPITAL HUMANO
## 6. MÓDULO DE PROYECTOS
